<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mixudev\LaravelTimezone\Facades\Timezone;
use Mixudev\LaravelTimezone\Middleware\DetectTimezone;
use Mixudev\LaravelTimezone\Tests\TestCase;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(DetectTimezone::class)->get('/api/current-time', function () {
            return response()->json([
                'timezone' => Timezone::current(),
                'local_time' => Timezone::format('2026-08-30 10:00:00', 'datetime'),
            ]);
        });
    }

    public function test_middleware_resolves_header_and_sets_context(): void
    {
        $response = $this->withHeaders([
            'X-Timezone' => 'Asia/Jakarta',
        ])->getJson('/api/current-time');

        $response->assertOk();
        $response->assertJson([
            'timezone' => 'Asia/Jakarta',
            'local_time' => '2026-08-30 17:00:00',
        ]);
    }
}
