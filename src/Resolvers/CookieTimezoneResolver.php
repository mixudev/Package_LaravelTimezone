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
            // Fallback check global $_COOKIE array if available
            $rawCookie = $_COOKIE[$this->cookieName] ?? null;
            if (is_string($rawCookie) && trim($rawCookie) !== '' && strlen($rawCookie) <= 64) {
                return TimezoneValidator::normalize($rawCookie);
            }
            return null;
        }

        // 1. Try raw Symfony cookie bag first (handles plain-text JavaScript cookies unencrypted)
        $cookie = $request->cookies->get($this->cookieName);

        // 2. Fallback to Laravel decrypted cookie
        if (!is_string($cookie) || trim($cookie) === '') {
            $cookie = $request->cookie($this->cookieName);
        }

        // 3. Fallback to superglobal $_COOKIE
        if (!is_string($cookie) || trim($cookie) === '') {
            $cookie = $_COOKIE[$this->cookieName] ?? null;
        }

        if (!is_string($cookie) || trim($cookie) === '' || strlen($cookie) > 64) {
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
