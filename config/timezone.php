<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Package Enabled
    |--------------------------------------------------------------------------
    |
    | Setting this to false disables all automatic timezone resolution, falling
    | back directly to the application default timezone.
    |
    */
    'enabled' => env('TIMEZONE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Fallback Timezone
    |--------------------------------------------------------------------------
    |
    | When no client, user, session, or IP timezone can be resolved, this
    | timezone will be used as the server fallback before UTC.
    | Set to null to use config('app.timezone').
    |
    */
    'default' => env('TIMEZONE_DEFAULT', config('app.timezone', 'UTC')),

    /*
    |--------------------------------------------------------------------------
    | Client Header & Cookie Detection
    |--------------------------------------------------------------------------
    |
    | Settings for extracting timezone transmitted from the browser/client.
    | The header takes precedence over the cookie.
    |
    */
    'client' => [
        'enabled' => true,
        'header' => 'X-Timezone',
        'cookie' => 'timezone',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Timezone Resolution
    |--------------------------------------------------------------------------
    |
    | If enabled, the resolver checks the session for a stored timezone identifier.
    | This is completely optional and disabled by default.
    |
    */
    'session' => [
        'enabled' => false,
        'key' => 'timezone',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model Timezone Provider
    |--------------------------------------------------------------------------
    |
    | If enabled and an authenticated user exists, the resolver can extract
    | the timezone using a custom attribute, method, or registered provider.
    |
    */
    'user' => [
        'enabled' => false,
        'attribute' => 'timezone', // Attribute or method name on the User model
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional IP Geolocation Resolver
    |--------------------------------------------------------------------------
    |
    | Disabled by default. Zero external network calls are performed unless
    | an explicit custom resolver implementing IpTimezoneResolverInterface
    | is configured here.
    |
    */
    'ip' => [
        'enabled' => false,
        'resolver' => null, // e.g., \App\Services\GeoIpTimezoneResolver::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Formatting Presets
    |--------------------------------------------------------------------------
    |
    | Format presets used when calling Timezone::format($date, 'preset') or
    | using the @localtime Blade directive.
    |
    */
    'formats' => [
        'datetime' => 'Y-m-d H:i:s',
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
        'human' => 'M j, Y g:i A',
    ],
];
