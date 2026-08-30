<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use InvalidArgumentException;
use Throwable;

final class DateNormalizer
{
    /**
     * Maximum allowed string length for date parsing to prevent ReDoS / memory exhaustion attacks.
     */
    private const MAX_DATE_STRING_LENGTH = 128;

    /**
     * Normalize mixed input into a CarbonInterface instance.
     *
     * @param mixed $date
     * @param string|null $fromTimezone
     * @return CarbonInterface
     *
     * @throws InvalidArgumentException
     */
    public static function normalize(mixed $date = null, ?string $fromTimezone = null): CarbonInterface
    {
        if ($date === null) {
            return $fromTimezone !== null
                ? CarbonImmutable::now($fromTimezone)
                : CarbonImmutable::now('UTC');
        }

        if ($date instanceof CarbonImmutable) {
            return $fromTimezone !== null
                ? $date->setTimezone($fromTimezone)
                : $date;
        }

        if ($date instanceof Carbon) {
            // Clone/copy to prevent accidental mutation of the original mutable instance
            $copy = $date->copy();
            return $fromTimezone !== null
                ? $copy->setTimezone($fromTimezone)
                : $copy;
        }

        if ($date instanceof DateTimeInterface) {
            return CarbonImmutable::instance($date);
        }

        if (is_int($date) || (is_string($date) && ctype_digit($date))) {
            $timestamp = (int) $date;
            return CarbonImmutable::createFromTimestamp($timestamp, $fromTimezone ?? 'UTC');
        }

        if (is_string($date)) {
            $trimmed = trim($date);
            if ($trimmed === '') {
                throw new InvalidArgumentException('Cannot parse empty date string.');
            }

            // Security guard: prevent ReDoS on huge payload
            if (strlen($trimmed) > self::MAX_DATE_STRING_LENGTH) {
                throw new InvalidArgumentException(
                    sprintf('Date string exceeds maximum allowable length of %d characters.', self::MAX_DATE_STRING_LENGTH)
                );
            }

            try {
                return $fromTimezone !== null
                    ? CarbonImmutable::parse($trimmed, $fromTimezone)
                    : CarbonImmutable::parse($trimmed);
            } catch (Throwable $e) {
                throw new InvalidArgumentException(
                    "Unable to parse date string [{$trimmed}]: " . $e->getMessage(),
                    previous: $e
                );
            }
        }

        throw new InvalidArgumentException(
            sprintf('Unsupported date type [%s] provided.', get_debug_type($date))
        );
    }

    /**
     * Convert any date input into the target timezone safely.
     *
     * @param mixed $date
     * @param string $toTimezone
     * @param string|null $fromTimezone
     * @return CarbonInterface
     */
    public static function convert(mixed $date, string $toTimezone, ?string $fromTimezone = null): CarbonInterface
    {
        $normalized = self::normalize($date, $fromTimezone);

        return $normalized->setTimezone($toTimezone);
    }
}
