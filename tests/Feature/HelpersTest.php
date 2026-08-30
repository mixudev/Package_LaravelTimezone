<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests\Feature;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Mixudev\LaravelTimezone\Facades\Timezone;
use Mixudev\LaravelTimezone\Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_local_timezone_and_local_timezone_name(): void
    {
        Timezone::setExplicit('Asia/Jakarta');

        $this->assertSame('Asia/Jakarta', local_timezone());
        $this->assertSame('Asia/Jakarta', local_timezone_name());
    }

    public function test_local_time_helper_returns_formatted_string(): void
    {
        Timezone::setExplicit('Asia/Jakarta');

        $date = CarbonImmutable::create(2026, 8, 30, 10, 0, 0, 'UTC');

        $this->assertSame('2026-08-30 17:00:00', local_time($date, 'datetime'));
        $this->assertSame('2026-08-30', local_time($date, 'date'));
    }

    public function test_local_time_helper_returns_carbon_instance_when_format_is_null(): void
    {
        Timezone::setExplicit('Asia/Jakarta');

        $date = CarbonImmutable::create(2026, 8, 30, 10, 0, 0, 'UTC');
        $result = local_time($date, null);

        $this->assertInstanceOf(CarbonInterface::class, $result);
        $this->assertSame('Asia/Jakarta', $result->getTimezone()->getName());
        $this->assertSame(17, $result->hour);
    }
}
