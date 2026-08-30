<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Resolvers;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Contracts\IpTimezoneResolverInterface;
use Mixudev\LaravelTimezone\Contracts\TimezoneResolverInterface;
use Mixudev\LaravelTimezone\Support\TimezoneValidator;
use Throwable;

class IpTimezoneResolver implements TimezoneResolverInterface
{
    /**
     * @var (Closure(string|null): ?string)|IpTimezoneResolverInterface|class-string<IpTimezoneResolverInterface>|null
     */
    protected Closure|IpTimezoneResolverInterface|string|null $resolver = null;

    protected bool $enabled = false;

    /**
     * @param Container|null $container
     * @param bool $enabled
     * @param (Closure(string|null): ?string)|IpTimezoneResolverInterface|class-string<IpTimezoneResolverInterface>|null $resolver
     */
    public function __construct(
        protected ?Container $container = null,
        bool $enabled = false,
        Closure|IpTimezoneResolverInterface|string|null $resolver = null
    ) {
        $this->enabled = $enabled;
        $this->resolver = $resolver;
    }

    /**
     * Set IP resolver instance, class name, or closure.
     *
     * @param (Closure(string|null): ?string)|IpTimezoneResolverInterface|class-string<IpTimezoneResolverInterface>|null $resolver
     */
    public function setResolver(Closure|IpTimezoneResolverInterface|string|null $resolver): void
    {
        $this->resolver = $resolver;
        $this->enabled = true;
    }

    public function resolve(?Request $request = null): ?string
    {
        if ($request === null || $this->resolver === null) {
            return null;
        }

        $ip = $request->ip();

        try {
            if ($this->resolver instanceof Closure) {
                $tz = ($this->resolver)($ip);
                return is_string($tz) ? TimezoneValidator::normalize($tz) : null;
            }

            if ($this->resolver instanceof IpTimezoneResolverInterface) {
                $tz = $this->resolver->resolveIp($ip);
                return is_string($tz) ? TimezoneValidator::normalize($tz) : null;
            }

            if ($this->container !== null) {
                /** @var IpTimezoneResolverInterface $instance */
                $instance = $this->container->make($this->resolver);
                $tz = $instance->resolveIp($ip);
                return is_string($tz) ? TimezoneValidator::normalize($tz) : null;
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    public function shouldRun(): bool
    {
        return $this->enabled && $this->resolver !== null;
    }

    public function name(): string
    {
        return 'ip';
    }
}
