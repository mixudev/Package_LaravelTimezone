<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests\Feature;

use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Facades\Timezone;
use Mixudev\LaravelTimezone\Tests\TestCase;
use Mixudev\LaravelTimezone\TimezoneManager;

class ScopedContextAndOctaneTest extends TestCase
{
    public function test_request_isolation_and_scoped_lifecycle(): void
    {
        // Request 1: User from Asia/Jakarta
        $request1 = Request::create('/order/1', 'GET', server: [
            'HTTP_X_TIMEZONE' => 'Asia/Jakarta',
        ]);
        $this->app->instance('request', $request1);

        $tz1 = Timezone::resolve($request1);
        $this->assertSame('Asia/Jakarta', $tz1);
        $this->assertSame('Asia/Jakarta', Timezone::current());

        // Simulate Octane request termination / container scope reset
        $this->app->forgetInstance(TimezoneManager::class);
        $this->app->forgetInstance('timezone');

        // Request 2: User from Europe/London
        $request2 = Request::create('/order/2', 'GET', server: [
            'HTTP_X_TIMEZONE' => 'Europe/London',
        ]);
        $this->app->instance('request', $request2);

        $tz2 = Timezone::resolve($request2);
        $this->assertSame('Europe/London', $tz2);
        $this->assertSame('Europe/London', Timezone::current());

        // Ensure Request 1's timezone did not persist or leak into Request 2
        $this->assertNotSame($tz1, $tz2);
    }
}
