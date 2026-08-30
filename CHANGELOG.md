# Changelog

All notable changes to `mixudev/laravel-timezone` will be documented in this file.

## [1.0.0] - 2026-08-30

### Added
- Initial release of `mixudev/laravel-timezone`.
- Multi-tier priority `TimezoneResolver` pipeline:
  1. Explicit developer override (`Timezone::in()`, `Timezone::setExplicit()`).
  2. Client Request Header (`X-Timezone`).
  3. Client Cookie (`timezone`).
  4. Dynamic Authenticated User provider (`Timezone::useUserProvider()`).
  5. Optional Session lookup (`config('timezone.session.enabled')`).
  6. Optional IP Geolocation provider (`Timezone::useIpResolver()`).
  7. Config default fallback (`config('timezone.default')` or `config('app.timezone')`).
  8. UTC guaranteed fallback.
- Client-first vanilla JavaScript browser synchronization (`resources/js/laravel-timezone.js`):
  - Automatic `Intl.DateTimeFormat()` resolution.
  - Zero external HTTP calls.
  - Automatic headers synchronization with Fetch, Axios, Inertia, and Livewire.
  - Progressive enhancement & hydration for `<time data-local-time>`.
- Blade integration:
  - `@localtime($date, $format)` directive.
  - `@timezone` directive.
  - `<x-local-time :date="$date" />` component with server-side rendered fallback.
- Global helper functions:
  - `local_time($date, $format, $timezone)`
  - `local_timezone()`
  - `local_timezone_name()`
- Robust `DateNormalizer` supporting `Carbon`, `CarbonImmutable`, `DateTimeInterface`, ISO strings, UNIX timestamps without mutating original objects.
- Artisan diagnostics and maintenance commands:
  - `timezone:install`
  - `timezone:detect`
  - `timezone:clear-cache`
- Request-scoped state isolation for Laravel Octane, RoadRunner, Swoole, Queue Workers, and CLI.
