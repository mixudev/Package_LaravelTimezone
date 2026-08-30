<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Support;

use DateTimeZone;

final class TimezoneValidator
{
    /**
     * In-memory cache of valid IANA timezone identifiers as a fast hash map.
     *
     * @var array<string, string>|null
     */
    private static ?array $validTimezones = null;

    /**
     * Determine if the given timezone string is a valid IANA timezone identifier.
     * Guaranteed zero exception throwing for maximum DoS protection.
     */
    public static function isValid(?string $timezone): bool
    {
        if ($timezone === null || $timezone === '') {
            return false;
        }

        // Fast length & ASCII character boundary check (reject immediately if > 64 chars or invalid chars)
        if (strlen($timezone) > 64 || preg_match('/[^a-zA-Z0-9_\/\+\-\.]/', $timezone)) {
            return false;
        }

        self::initializeCache();

        if (isset(self::$validTimezones[$timezone])) {
            return true;
        }

        $lower = strtolower($timezone);
        return isset(self::$validTimezones[$lower]);
    }

    /**
     * Normalize and return valid canonical IANA timezone, or null if invalid.
     */
    public static function normalize(?string $timezone): ?string
    {
        if ($timezone === null || $timezone === '') {
            return null;
        }

        // Handle proxy multi-value header (e.g., "Asia/Jakarta, UTC" -> "Asia/Jakarta")
        if (str_contains($timezone, ',')) {
            $parts = explode(',', $timezone, 2);
            $timezone = trim($parts[0]);
        }

        if (strlen($timezone) > 64 || preg_match('/[^a-zA-Z0-9_\/\+\-\.]/', $timezone)) {
            return null;
        }

        self::initializeCache();

        if (isset(self::$validTimezones[$timezone])) {
            return self::$validTimezones[$timezone];
        }

        $lower = strtolower($timezone);
        return self::$validTimezones[$lower] ?? null;
    }

    /**
     * Get all recognized IANA timezone identifiers.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        self::initializeCache();
        return array_values(array_unique(self::$validTimezones ?? []));
    }

    /**
     * Populate internal lookup hash table including all IANA & backward compatible identifiers.
     */
    private static function initializeCache(): void
    {
        if (self::$validTimezones !== null) {
            return;
        }

        self::$validTimezones = [];
        $identifiers = DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC);

        foreach ($identifiers as $tz) {
            self::$validTimezones[$tz] = $tz;
            self::$validTimezones[strtolower($tz)] = $tz;
        }

        // Include UTC explicit mappings
        self::$validTimezones['UTC'] = 'UTC';
        self::$validTimezones['utc'] = 'UTC';
    }

    /**
     * Reset internal cache if needed (useful for testing).
     */
    public static function flushCache(): void
    {
        self::$validTimezones = null;
    }
}
