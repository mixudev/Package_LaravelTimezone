<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Resolvers;

use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Contracts\TimezoneResolverInterface;
use Mixudev\LaravelTimezone\Support\TimezoneValidator;

class ConfigTimezoneResolver implements TimezoneResolverInterface
{
    public function __construct(
        protected ?string $defaultTimezone = null
    ) {
    }

    public function setDefaultTimezone(?string $timezone): void
    {
        $this->defaultTimezone = $timezone;
    }

    public function resolve(?Request $request = null): ?string
    {
        if ($this->defaultTimezone !== null) {
            return TimezoneValidator::normalize($this->defaultTimezone);
        }

        return TimezoneValidator::normalize(config('app.timezone', 'UTC'));
    }

    public function shouldRun(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'config';
    }
}
