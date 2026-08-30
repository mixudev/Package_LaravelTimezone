<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Resolvers;

use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Contracts\TimezoneResolverInterface;

class UtcFallbackResolver implements TimezoneResolverInterface
{
    public function resolve(?Request $request = null): ?string
    {
        return 'UTC';
    }

    public function shouldRun(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'utc_fallback';
    }
}
