<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Resolvers;

use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Contracts\TimezoneResolverInterface;
use Mixudev\LaravelTimezone\Support\TimezoneValidator;

class ExplicitTimezoneResolver implements TimezoneResolverInterface
{
    protected ?string $explicitTimezone = null;

    /**
     * Set explicit timezone override for current context.
     */
    public function setExplicit(?string $timezone): void
    {
        $this->explicitTimezone = TimezoneValidator::normalize($timezone);
    }

    /**
     * Get currently set explicit timezone.
     */
    public function getExplicit(): ?string
    {
        return $this->explicitTimezone;
    }

    /**
     * Reset explicit timezone override.
     */
    public function reset(): void
    {
        $this->explicitTimezone = null;
    }

    public function resolve(?Request $request = null): ?string
    {
        return $this->explicitTimezone;
    }

    public function shouldRun(): bool
    {
        return $this->explicitTimezone !== null;
    }

    public function name(): string
    {
        return 'explicit';
    }
}
