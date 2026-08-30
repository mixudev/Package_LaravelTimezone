<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests;

use Mixudev\LaravelTimezone\Facades\Timezone;
use Mixudev\LaravelTimezone\TimezoneServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            TimezoneServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Timezone' => Timezone::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.timezone', 'UTC');
        $app['config']->set('timezone.enabled', true);
        $app['config']->set('timezone.default', 'UTC');
    }
}
