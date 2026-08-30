<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use Mixudev\LaravelTimezone\Facades\Timezone;

if (!function_exists('local_time')) {
    /**
     * Convert and format a date in the current resolved or given timezone.
     * If format is null, returns a CarbonInterface instance.
     *
     * @param mixed $date
     * @param string|null $format
     * @param string|null $timezone
     * @return string|CarbonInterface
     */
    function local_time(mixed $date = null, ?string $format = 'datetime', ?string $timezone = null): string|CarbonInterface
    {
        if ($format === null) {
            return Timezone::convert($date, $timezone);
        }

        return Timezone::format($date, $format, $timezone);
    }
}

if (!function_exists('local_timezone')) {
    /**
     * Get the current resolved timezone identifier.
     *
     * @return string
     */
    function local_timezone(): string
    {
        return Timezone::current();
    }
}

if (!function_exists('local_timezone_name')) {
    /**
     * Get the current resolved timezone identifier name.
     *
     * @return string
     */
    function local_timezone_name(): string
    {
        return Timezone::current();
    }
}
