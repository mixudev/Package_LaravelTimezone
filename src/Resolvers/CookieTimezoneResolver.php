<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Resolvers;

use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Contracts\TimezoneResolverInterface;
use Mixudev\LaravelTimezone\Support\TimezoneValidator;

class CookieTimezoneResolver implements TimezoneResolverInterface
{
    public function __construct(
        protected string $cookieName = 'timezone',
        protected bool $enabled = true
    ) {
    }

    public function resolve(?Request $request = null): ?string
    {
        if ($request === null) {
            return null;
        }

        $cookie = $request->cookie($this->cookieName);

        if (!is_string($cookie) || trim($cookie) === '') {
            return null;
        }

        return TimezoneValidator::normalize($cookie);
    }

    public function shouldRun(): bool
    {
        return $this->enabled;
    }

    public function name(): string
    {
        return 'cookie';
    }
}
