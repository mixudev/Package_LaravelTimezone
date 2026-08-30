<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Support;

use DateTimeZone;
use Throwable;

final class TimezoneValidator
{
    /**
     * In-memory cache of valid IANA timezone identifiers as a fast hash map.
     *
     * @var array<string, bool>|null
     */
    private static ?array $validTimezones = null;

    /**
     * Determine if the given timezone string is a valid IANA timezone identifier.
     */
    public static function isValid(?string $timezone): bool
    {
        if ($timezone === null || $timezone === '') {
            return false;
        }

        // Fast length and character sanity check to avoid regex or overhead
        if (strlen($timezone) > 100 || preg_match('/[^a-zA-Z0-9_\/\+\-\.]/', $timezone)) {
            return false;
        }

        self::initializeCache();

        if (isset(self::$validTimezones[$timezone])) {
            return true;
        }

        // Case-insensitive fallback check
        $lower = strtolower($timezone);
        if (isset(self::$validTimezones[$lower])) {
            return true;
        }

        // Fallback to native PHP DateTimeZone constructor
        try {
            new DateTimeZone($timezone);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Normalize and return valid canonical IANA timezone, or null if invalid.
     */
    public static function normalize(?string $timezone): ?string
    {
        if (!self::isValid($timezone)) {
            return null;
        }

        try {
            $tz = new DateTimeZone((string) $timezone);
            return $tz->getName();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Get all recognized IANA timezone identifiers.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    /**
     * Populate internal lookup hash table.
     */
    private static function initializeCache(): void
    {
        if (self::$validTimezones !== null) {
            return;
        }

        self::$validTimezones = [];
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            self::$validTimezones[$tz] = true;
            self::$validTimezones[strtolower($tz)] = true;
        }

        // Include UTC explicitly
        self::$validTimezones['UTC'] = true;
        self::$validTimezones['utc'] = true;
    }

    /**
     * Reset internal cache if needed (useful for testing).
     */
    public static function flushCache(): void
    {
        self::$validTimezones = null;
    }
}
