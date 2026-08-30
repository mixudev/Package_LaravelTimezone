<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone;

use Carbon\CarbonInterface;
use Closure;
use DateTimeZone;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Contracts\IpTimezoneResolverInterface;
use Mixudev\LaravelTimezone\Contracts\TimezoneFormatterInterface;
use Mixudev\LaravelTimezone\Contracts\TimezoneResolverInterface;
use Mixudev\LaravelTimezone\Contracts\UserTimezoneProviderInterface;
use Mixudev\LaravelTimezone\DTO\ResolvedTimezone;
use Mixudev\LaravelTimezone\Resolvers\ConfigTimezoneResolver;
use Mixudev\LaravelTimezone\Resolvers\CookieTimezoneResolver;
use Mixudev\LaravelTimezone\Resolvers\ExplicitTimezoneResolver;
use Mixudev\LaravelTimezone\Resolvers\HeaderTimezoneResolver;
use Mixudev\LaravelTimezone\Resolvers\IpTimezoneResolver;
use Mixudev\LaravelTimezone\Resolvers\SessionTimezoneResolver;
use Mixudev\LaravelTimezone\Resolvers\UserTimezoneResolver;
use Mixudev\LaravelTimezone\Resolvers\UtcFallbackResolver;
use Mixudev\LaravelTimezone\Support\DateNormalizer;
use Mixudev\LaravelTimezone\Support\TimezoneFormatter;
use Mixudev\LaravelTimezone\Support\TimezoneValidator;
use Throwable;

class TimezoneManager
{
    /**
     * Memoized resolved timezone for the current request/execution lifecycle.
     */
    protected ?string $resolvedTimezone = null;

    /**
     * Source resolver name that provided the resolved timezone.
     */
    protected ?string $resolvedSource = null;

    /**
     * Registered resolvers in priority sequence.
     *
     * @var array<int, TimezoneResolverInterface>
     */
    protected array $resolvers = [];

    /**
     * Reference to explicit override resolver.
     */
    protected ExplicitTimezoneResolver $explicitResolver;

    /**
     * Reference to user model resolver.
     */
    protected UserTimezoneResolver $userResolver;

    /**
     * Reference to IP geolocation resolver.
     */
    protected IpTimezoneResolver $ipResolver;

    /**
     * Date formatter instance.
     */
    protected TimezoneFormatterInterface $formatter;

    /**
     * Container instance.
     */
    protected ?Container $container = null;

    /**
     * Enabled flag.
     */
    protected bool $enabled = true;

    /**
     * @param array<string, mixed> $config
     * @param Container|null $container
     * @param TimezoneFormatterInterface|null $formatter
     */
    public function __construct(
        array $config = [],
        ?Container $container = null,
        ?TimezoneFormatterInterface $formatter = null
    ) {
        $this->container = $container;
        $this->enabled = (bool) ($config['enabled'] ?? true);
        $this->formatter = $formatter ?? new TimezoneFormatter($config['formats'] ?? []);

        $this->explicitResolver = new ExplicitTimezoneResolver();

        $clientConfig = $config['client'] ?? [];
        $headerResolver = new HeaderTimezoneResolver(
            headerName: (string) ($clientConfig['header'] ?? 'X-Timezone'),
            enabled: (bool) ($clientConfig['enabled'] ?? true)
        );

        $cookieResolver = new CookieTimezoneResolver(
            cookieName: (string) ($clientConfig['cookie'] ?? 'timezone'),
            enabled: (bool) ($clientConfig['enabled'] ?? true)
        );

        $userConfig = $config['user'] ?? [];
        $this->userResolver = new UserTimezoneResolver(
            enabled: (bool) ($userConfig['enabled'] ?? false),
            attribute: (string) ($userConfig['attribute'] ?? 'timezone')
        );

        $sessionConfig = $config['session'] ?? [];
        $sessionResolver = new SessionTimezoneResolver(
            enabled: (bool) ($sessionConfig['enabled'] ?? false),
            sessionKey: (string) ($sessionConfig['key'] ?? 'timezone')
        );

        $ipConfig = $config['ip'] ?? [];
        $this->ipResolver = new IpTimezoneResolver(
            container: $container,
            enabled: (bool) ($ipConfig['enabled'] ?? false),
            resolver: $ipConfig['resolver'] ?? null
        );

        $configResolver = new ConfigTimezoneResolver(
            defaultTimezone: isset($config['default']) && is_string($config['default']) ? $config['default'] : null
        );

        $utcResolver = new UtcFallbackResolver();

        // Register default resolver pipeline ordered by strict priority
        $this->resolvers = [
            $this->explicitResolver, // 1. Explicit override
            $headerResolver,         // 2. Client Header
            $cookieResolver,         // 3. Client Cookie
            $this->userResolver,     // 4. Authenticated User
            $sessionResolver,        // 5. Session
            $this->ipResolver,           // 6. IP Geolocation
            $configResolver,         // 7. Config / App default
            $utcResolver,            // 8. UTC final guarantee
        ];
    }

    /**
     * Get the resolved timezone for the current context.
     */
    public function current(): string
    {
        if ($this->resolvedTimezone !== null) {
            return $this->resolvedTimezone;
        }

        return $this->resolve();
    }

    /**
     * Resolve timezone from the resolver pipeline with request-scoped memoization.
     */
    public function resolve(?Request $request = null): string
    {
        // Check if explicit override is active
        if ($this->explicitResolver->shouldRun()) {
            $explicit = $this->explicitResolver->resolve($request);
            if ($explicit !== null) {
                $this->resolvedTimezone = $explicit;
                $this->resolvedSource = $this->explicitResolver->name();
                return $explicit;
            }
        }

        // If package is disabled, immediately return config fallback
        if (!$this->enabled) {
            $default = TimezoneValidator::normalize(config('timezone.default') ?? config('app.timezone') ?? 'UTC') ?? 'UTC';
            $this->resolvedTimezone = $default;
            $this->resolvedSource = 'disabled_fallback';
            return $default;
        }

        // Obtain request if not passed
        if ($request === null && $this->container !== null && $this->container->bound('request')) {
            try {
                $req = $this->container->make('request');
                if ($req instanceof Request) {
                    $request = $req;
                }
            } catch (Throwable) {
                $request = null;
            }
        }

        // Iterate through resolver chain
        foreach ($this->resolvers as $resolver) {
            if (!$resolver->shouldRun()) {
                continue;
            }

            $resolved = $resolver->resolve($request);

            if ($resolved !== null && TimezoneValidator::isValid($resolved)) {
                $this->resolvedTimezone = $resolved;
                $this->resolvedSource = $resolver->name();
                return $resolved;
            }
        }

        // Guaranteed fallback
        $this->resolvedTimezone = 'UTC';
        $this->resolvedSource = 'utc_fallback';
        return 'UTC';
    }

    /**
     * Get diagnostic info about the resolved timezone.
     */
    public function getResolvedInfo(?Request $request = null): ResolvedTimezone
    {
        $timezone = $this->resolve($request);
        $source = $this->resolvedSource ?? 'unknown';
        $isFallback = in_array($source, ['config', 'utc_fallback', 'disabled_fallback'], true);

        return new ResolvedTimezone(
            timezone: $timezone,
            source: $source,
            isFallback: $isFallback
        );
    }

    /**
     * Get the source identifier of the current resolved timezone.
     */
    public function getResolvedSource(): ?string
    {
        if ($this->resolvedSource === null) {
            $this->resolve();
        }

        return $this->resolvedSource;
    }

    /**
     * Convert any date into a target timezone (defaults to current resolved timezone).
     */
    public function convert(mixed $date, ?string $to = null, ?string $from = null): CarbonInterface
    {
        $targetTimezone = $to !== null ? (TimezoneValidator::normalize($to) ?? $this->current()) : $this->current();

        return DateNormalizer::convert($date, $targetTimezone, $from);
    }

    /**
     * Fluent alias for convert($date, $timezone).
     */
    public function for(mixed $date, ?string $timezone = null): CarbonInterface
    {
        return $this->convert($date, $timezone);
    }

    /**
     * Format a date into the resolved timezone using preset or custom format string.
     */
    public function format(mixed $date, string $format = 'datetime', ?string $timezone = null): string
    {
        $targetTimezone = $timezone !== null
            ? (TimezoneValidator::normalize($timezone) ?? $this->current())
            : $this->current();

        $carbon = DateNormalizer::convert($date, $targetTimezone);

        return $this->formatter->format($carbon, $format, $targetTimezone);
    }

    /**
     * Execute a callback in the context of a temporary explicit timezone.
     * Guaranteed to restore previous explicit state even on exception.
     *
     * @template TReturn
     * @param string $timezone
     * @param Closure(): TReturn $callback
     * @return TReturn
     */
    public function in(string $timezone, Closure $callback): mixed
    {
        $previousExplicit = $this->explicitResolver->getExplicit();
        $previousResolved = $this->resolvedTimezone;
        $previousSource = $this->resolvedSource;

        $normalized = TimezoneValidator::normalize($timezone) ?? $this->current();

        $this->explicitResolver->setExplicit($normalized);
        $this->resolvedTimezone = $normalized;
        $this->resolvedSource = 'explicit';

        try {
            return $callback();
        } finally {
            $this->explicitResolver->setExplicit($previousExplicit);
            $this->resolvedTimezone = $previousResolved;
            $this->resolvedSource = $previousSource;
        }
    }

    /**
     * Set explicit timezone override on the current request lifecycle.
     */
    public function setExplicit(?string $timezone): self
    {
        $this->explicitResolver->setExplicit($timezone);
        $this->resolvedTimezone = $this->explicitResolver->getExplicit();
        $this->resolvedSource = $this->resolvedTimezone !== null ? 'explicit' : null;

        return $this;
    }

    /**
     * Register a custom UserTimezoneProvider callback or class.
     *
     * @param (Closure(mixed): ?string)|UserTimezoneProviderInterface $provider
     */
    public function useUserProvider(Closure|UserTimezoneProviderInterface $provider): self
    {
        $this->userResolver->setProvider($provider);
        $this->flush();

        return $this;
    }

    /**
     * Register a custom IpTimezoneResolver callback, class name, or instance.
     *
     * @param (Closure(string|null): ?string)|IpTimezoneResolverInterface|class-string<IpTimezoneResolverInterface> $resolver
     */
    public function useIpResolver(Closure|IpTimezoneResolverInterface|string $resolver): self
    {
        $this->ipResolver->setResolver($resolver);
        $this->flush();

        return $this;
    }

    /**
     * Check if a timezone identifier is valid.
     */
    public function isValid(?string $timezone): bool
    {
        return TimezoneValidator::isValid($timezone);
    }

    /**
     * Get all available IANA timezone identifiers.
     *
     * @return array<int, string>
     */
    public function list(): array
    {
        return TimezoneValidator::all();
    }

    /**
     * Add a custom resolver to the pipeline.
     */
    public function addResolver(TimezoneResolverInterface $resolver, ?int $index = null): self
    {
        if ($index === null) {
            $this->resolvers[] = $resolver;
        } else {
            array_splice($this->resolvers, $index, 0, [$resolver]);
        }

        $this->flush();
        return $this;
    }

    /**
     * Replace all resolvers.
     *
     * @param array<int, TimezoneResolverInterface> $resolvers
     */
    public function setResolvers(array $resolvers): self
    {
        $this->resolvers = $resolvers;
        $this->flush();
        return $this;
    }

    /**
     * Get registered resolvers.
     *
     * @return array<int, TimezoneResolverInterface>
     */
    public function getResolvers(): array
    {
        return $this->resolvers;
    }

    /**
     * Get the formatter instance.
     */
    public function getFormatter(): TimezoneFormatterInterface
    {
        return $this->formatter;
    }

    /**
     * Set the formatter instance.
     */
    public function setFormatter(TimezoneFormatterInterface $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }

    /**
     * Flush request-scoped memoized values.
     */
    public function flush(): void
    {
        $this->resolvedTimezone = null;
        $this->resolvedSource = null;
        $this->explicitResolver->reset();
    }

    /**
     * Clear all caches including static validator lookup cache.
     */
    public function clearCache(): void
    {
        $this->flush();
        TimezoneValidator::flushCache();
    }
}
