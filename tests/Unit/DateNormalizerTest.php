<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests\Unit;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Mixudev\LaravelTimezone\Support\DateNormalizer;
use PHPUnit\Framework\TestCase;

class DateNormalizerTest extends TestCase
{
    public function test_normalizes_null_to_now(): void
    {
        $now = DateNormalizer::normalize(null);
        $this->assertInstanceOf(CarbonImmutable::class, $now);
        $this->assertSame('UTC', $now->getTimezone()->getName());
    }

    public function test_normalizes_carbon_without_mutating_original(): void
    {
        $original = Carbon::create(2026, 8, 30, 10, 0, 0, 'UTC');
        $converted = DateNormalizer::convert($original, 'Asia/Jakarta');

        // Original carbon must remain intact
        $this->assertSame('UTC', $original->getTimezone()->getName());
        $this->assertSame(10, $original->hour);

        // Converted should be in target timezone (+7 hours)
        $this->assertSame('Asia/Jakarta', $converted->getTimezone()->getName());
        $this->assertSame(17, $converted->hour);
    }

    public function test_normalizes_carbon_immutable(): void
    {
        $immutable = CarbonImmutable::create(2026, 8, 30, 10, 0, 0, 'UTC');
        $converted = DateNormalizer::convert($immutable, 'Asia/Jakarta');

        $this->assertSame('Asia/Jakarta', $converted->getTimezone()->getName());
        $this->assertSame(17, $converted->hour);
    }

    public function test_normalizes_native_datetime_instances(): void
    {
        $dt = new DateTime('2026-08-30 10:00:00', new \DateTimeZone('UTC'));
        $converted = DateNormalizer::convert($dt, 'Asia/Tokyo');

        $this->assertSame('Asia/Tokyo', $converted->getTimezone()->getName());
        $this->assertSame(19, $converted->hour);

        $dtImmutable = new DateTimeImmutable('2026-08-30 10:00:00', new \DateTimeZone('UTC'));
        $convertedImmutable = DateNormalizer::convert($dtImmutable, 'Asia/Tokyo');
        $this->assertSame('Asia/Tokyo', $convertedImmutable->getTimezone()->getName());
        $this->assertSame(19, $convertedImmutable->hour);
    }

    public function test_normalizes_timestamp(): void
    {
        $timestamp = 1756548000; // 2025-08-30 10:00:00 UTC
        $converted = DateNormalizer::convert($timestamp, 'UTC');

        $this->assertSame(1756548000, $converted->getTimestamp());
        $this->assertSame('UTC', $converted->getTimezone()->getName());
    }

    public function test_normalizes_iso_and_formatted_strings(): void
    {
        $iso = '2026-08-30T10:00:00Z';
        $converted = DateNormalizer::convert($iso, 'Asia/Jakarta');

        $this->assertSame('Asia/Jakarta', $converted->getTimezone()->getName());
        $this->assertSame(17, $converted->hour);
    }

    public function test_throws_exception_on_invalid_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DateNormalizer::normalize('not-a-valid-date-string-xyz');
    }
}
