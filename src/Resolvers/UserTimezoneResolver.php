<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Resolvers;

use Closure;
use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Contracts\TimezoneResolverInterface;
use Mixudev\LaravelTimezone\Contracts\UserTimezoneProviderInterface;
use Mixudev\LaravelTimezone\Support\TimezoneValidator;
use Throwable;

class UserTimezoneResolver implements TimezoneResolverInterface
{
    /**
     * @var (Closure(mixed): ?string)|UserTimezoneProviderInterface|null
     */
    protected Closure|UserTimezoneProviderInterface|null $provider = null;

    public function __construct(
        protected bool $enabled = false,
        protected string $attribute = 'timezone'
    ) {
    }

    /**
     * Set dynamic user provider callback or interface.
     *
     * @param (Closure(mixed): ?string)|UserTimezoneProviderInterface|null $provider
     */
    public function setProvider(Closure|UserTimezoneProviderInterface|null $provider): void
    {
        $this->provider = $provider;
        $this->enabled = true;
    }

    public function resolve(?Request $request = null): ?string
    {
        $user = $request?->user();

        if ($user === null) {
            return null;
        }

        try {
            if ($this->provider instanceof Closure) {
                $tz = ($this->provider)($user);
                return TimezoneValidator::normalize(is_string($tz) ? $tz : null);
            }

            if ($this->provider instanceof UserTimezoneProviderInterface) {
                $tz = $this->provider->getTimezone($user);
                return TimezoneValidator::normalize($tz);
            }

            // Fallback to checking attribute or method on user object
            if (isset($user->{$this->attribute}) && is_string($user->{$this->attribute})) {
                return TimezoneValidator::normalize($user->{$this->attribute});
            }

            $method = 'get' . ucfirst($this->attribute);
            if (method_exists($user, $method)) {
                $tz = $user->{$method}();
                return is_string($tz) ? TimezoneValidator::normalize($tz) : null;
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }

    public function shouldRun(): bool
    {
        return $this->enabled;
    }

    public function name(): string
    {
        return 'user';
    }
}
