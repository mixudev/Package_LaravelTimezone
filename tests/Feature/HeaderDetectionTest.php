<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests\Feature;

use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Facades\Timezone;
use Mixudev\LaravelTimezone\Tests\TestCase;

class HeaderDetectionTest extends TestCase
{
    public function test_resolves_valid_header(): void
    {
        $request = Request::create('/api/test', 'GET', server: [
            'HTTP_X_TIMEZONE' => 'Asia/Jakarta',
        ]);

        $this->assertSame('Asia/Jakarta', Timezone::resolve($request));
        $this->assertSame('header', Timezone::getResolvedSource());
    }

    public function test_ignores_invalid_header_and_falls_back(): void
    {
        $request = Request::create('/api/test', 'GET', server: [
            'HTTP_X_TIMEZONE' => 'Invalid/Fake_Zone',
        ]);

        $this->assertSame('UTC', Timezone::resolve($request));
        $this->assertSame('config', Timezone::getResolvedSource());
    }

    public function test_cookie_fallback_when_header_is_missing(): void
    {
        $request = Request::create('/test', 'GET', cookies: [
            'timezone' => 'Australia/Sydney',
        ]);

        $this->assertSame('Australia/Sydney', Timezone::resolve($request));
        $this->assertSame('cookie', Timezone::getResolvedSource());
    }
}
