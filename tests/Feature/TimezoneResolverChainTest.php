<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests\Feature;

use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Facades\Timezone;
use Mixudev\LaravelTimezone\Tests\TestCase;

class TimezoneResolverChainTest extends TestCase
{
    public function test_default_fallback_to_config_or_utc(): void
    {
        $this->assertSame('UTC', Timezone::current());
        $this->assertSame('config', Timezone::getResolvedSource());
    }

    public function test_explicit_override_has_highest_priority(): void
    {
        // Simulate a request with a header
        $request = Request::create('/', 'GET', server: [
            'HTTP_X_TIMEZONE' => 'America/New_York',
        ]);
        $this->app->instance('request', $request);

        $this->assertSame('America/New_York', Timezone::resolve($request));
        $this->assertSame('header', Timezone::getResolvedSource());

        // Now set explicit override
        Timezone::setExplicit('Asia/Tokyo');
        $this->assertSame('Asia/Tokyo', Timezone::current());
        $this->assertSame('explicit', Timezone::getResolvedSource());

        // Reset explicit
        Timezone::setExplicit(null);
        Timezone::flush();
        $this->assertSame('America/New_York', Timezone::resolve($request));
    }

    public function test_in_method_temporarily_switches_timezone_and_reverts(): void
    {
        Timezone::setExplicit('Europe/London');
        $this->assertSame('Europe/London', Timezone::current());

        $nestedResult = Timezone::in('Asia/Jakarta', function () {
            $this->assertSame('Asia/Jakarta', Timezone::current());
            return Timezone::format('2026-08-30 10:00:00', 'H:i');
        });

        // Converted 10:00 UTC to Asia/Jakarta (17:00)
        $this->assertSame('17:00', $nestedResult);

        // Outside 'in()', reverted to previous explicit
        $this->assertSame('Europe/London', Timezone::current());
    }

    public function test_in_method_reverts_even_when_exception_is_thrown(): void
    {
        Timezone::setExplicit('Europe/London');

        try {
            Timezone::in('Asia/Tokyo', function () {
                $this->assertSame('Asia/Tokyo', Timezone::current());
                throw new \RuntimeException('Test error');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('Test error', $e->getMessage());
        }

        $this->assertSame('Europe/London', Timezone::current());
    }
}
