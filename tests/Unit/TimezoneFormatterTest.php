<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests\Unit;

use Carbon\CarbonImmutable;
use Mixudev\LaravelTimezone\Support\TimezoneFormatter;
use PHPUnit\Framework\TestCase;

class TimezoneFormatterTest extends TestCase
{
    protected TimezoneFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new TimezoneFormatter();
    }

    public function test_formats_standard_presets(): void
    {
        $date = CarbonImmutable::create(2026, 8, 30, 10, 30, 45, 'UTC');

        // Target: Asia/Jakarta (UTC+7) -> 17:30:45
        $this->assertSame(
            '2026-08-30 17:30:45',
            $this->formatter->format($date, 'datetime', 'Asia/Jakarta')
        );

        $this->assertSame(
            '2026-08-30',
            $this->formatter->format($date, 'date', 'Asia/Jakarta')
        );

        $this->assertSame(
            '17:30:45',
            $this->formatter->format($date, 'time', 'Asia/Jakarta')
        );

        $this->assertSame(
            'Aug 30, 2026 5:30 PM',
            $this->formatter->format($date, 'human', 'Asia/Jakarta')
        );
    }

    public function test_formats_iso_and_timestamp(): void
    {
        $date = CarbonImmutable::create(2026, 8, 30, 10, 0, 0, 'UTC');

        $this->assertStringContainsString('+07:00', $this->formatter->format($date, 'iso', 'Asia/Jakarta'));
        $this->assertSame((string) $date->getTimestamp(), $this->formatter->format($date, 'timestamp', 'Asia/Jakarta'));
    }

    public function test_formats_custom_php_date_pattern(): void
    {
        $date = CarbonImmutable::create(2026, 8, 30, 10, 0, 0, 'UTC');

        $this->assertSame(
            '30/08/2026',
            $this->formatter->format($date, 'd/m/Y', 'Asia/Jakarta')
        );
    }
}
