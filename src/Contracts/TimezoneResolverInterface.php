<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Contracts;

use Illuminate\Http\Request;

interface TimezoneResolverInterface
{
    /**
     * Resolve timezone identifier. Returns null if unresolved or invalid.
     */
    public function resolve(?Request $request = null): ?string;

    /**
     * Determine if this resolver is enabled and should execute.
     */
    public function shouldRun(): bool;

    /**
     * Get the human-readable identifier of the resolver.
     */
    public function name(): string;
}
