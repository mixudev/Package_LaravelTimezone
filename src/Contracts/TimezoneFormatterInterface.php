<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Contracts;

use Carbon\CarbonInterface;

interface TimezoneFormatterInterface
{
    /**
     * Format a carbon/datetime instance in the specified timezone and format.
     */
    public function format(CarbonInterface $date, string $format, string $timezone): string;
}
