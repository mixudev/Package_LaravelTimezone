<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Resolvers;

use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Contracts\TimezoneResolverInterface;
use Mixudev\LaravelTimezone\Support\TimezoneValidator;
use Throwable;

class SessionTimezoneResolver implements TimezoneResolverInterface
{
    public function __construct(
        protected bool $enabled = false,
        protected string $sessionKey = 'timezone'
    ) {
    }

    public function resolve(?Request $request = null): ?string
    {
        if ($request === null) {
            return null;
        }

        try {
            if (!$request->hasSession()) {
                return null;
            }

            $tz = $request->session()->get($this->sessionKey);

            if (!is_string($tz) || trim($tz) === '') {
                return null;
            }

            return TimezoneValidator::normalize($tz);
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
        return 'session';
    }
}
