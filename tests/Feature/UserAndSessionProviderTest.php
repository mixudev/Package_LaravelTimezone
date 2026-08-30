<?php

declare(strict_types=1);

namespace Mixudev\LaravelTimezone\Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Mixudev\LaravelTimezone\Contracts\UserTimezoneProviderInterface;
use Mixudev\LaravelTimezone\Facades\Timezone;
use Mixudev\LaravelTimezone\Tests\TestCase;

class UserAndSessionProviderTest extends TestCase
{
    public function test_custom_user_provider_closure(): void
    {
        $dummyUser = new class {
            public string $preferredTimezone = 'Asia/Tokyo';
        };

        Timezone::useUserProvider(fn ($user) => $user->preferredTimezone ?? null);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $dummyUser);

        $this->assertSame('Asia/Tokyo', Timezone::resolve($request));
        $this->assertSame('user', Timezone::getResolvedSource());
    }

    public function test_custom_user_provider_interface(): void
    {
        $provider = new class implements UserTimezoneProviderInterface {
            public function getTimezone(mixed $user): ?string
            {
                return $user->custom_tz ?? null;
            }
        };

        Timezone::useUserProvider($provider);

        $dummyUser = new class {
            public string $custom_tz = 'America/Chicago';
        };

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $dummyUser);

        $this->assertSame('America/Chicago', Timezone::resolve($request));
        $this->assertSame('user', Timezone::getResolvedSource());
    }

    public function test_session_timezone_resolution_when_enabled(): void
    {
        $this->app['config']->set('timezone.session.enabled', true);

        // Recreate manager with updated config
        $this->app->forgetInstance(\Mixudev\LaravelTimezone\TimezoneManager::class);

        $session = new Store('test', new ArraySessionHandler(10));
        $session->put('timezone', 'Europe/Paris');

        $request = Request::create('/', 'GET');
        $request->setLaravelSession($session);

        $this->assertSame('Europe/Paris', Timezone::resolve($request));
        $this->assertSame('session', Timezone::getResolvedSource());
    }
}
