<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Resolvers;

use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Contracts\TimezoneResolverInterface;
use Mixudev\LaravelTimezone\Support\TimezoneValidator;

class HeaderTimezoneResolver implements TimezoneResolverInterface
{
    public function __construct(
        protected string $headerName = 'X-Timezone',
        protected bool $enabled = true
    ) {
    }

    public function resolve(?Request $request = null): ?string
    {
        if ($request === null) {
            return null;
        }

        $header = $request->header($this->headerName);

        if (!is_string($header) || trim($header) === '') {
            return null;
        }

        return TimezoneValidator::normalize($header);
    }

    public function shouldRun(): bool
    {
        return $this->enabled;
    }

    public function name(): string
    {
        return 'header';
    }
}
