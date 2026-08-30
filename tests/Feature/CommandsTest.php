<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests\Feature;

use Mixudev\LaravelTimezone\Tests\TestCase;

class CommandsTest extends TestCase
{
    public function test_detect_command_runs_successfully(): void
    {
        $this->artisan('timezone:detect')
            ->assertSuccessful();
    }

    public function test_detect_command_validates_timezone_flag(): void
    {
        $this->artisan('timezone:detect', ['--tz' => 'Asia/Jakarta'])
            ->expectsOutputToContain('Valid IANA timezone: Yes')
            ->expectsOutputToContain('Canonical name: Asia/Jakarta')
            ->assertSuccessful();

        $this->artisan('timezone:detect', ['--tz' => 'Fake/Zone'])
            ->expectsOutputToContain('Valid IANA timezone: No')
            ->assertSuccessful();
    }

    public function test_clear_cache_command_runs_successfully(): void
    {
        $this->artisan('timezone:clear-cache')
            ->expectsOutputToContain('Timezone cache and memoized context cleared successfully.')
            ->assertSuccessful();
    }

    public function test_install_command_runs_successfully(): void
    {
        $this->artisan('timezone:install', ['--force' => true])
            ->assertSuccessful();
    }
}
