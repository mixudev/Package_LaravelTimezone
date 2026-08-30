# Laravel Timezone (`mixudev/laravel-timezone`)

[![Versi Terbaru di Packagist](https://img.shields.io/packagist/v/mixudev/laravel-timezone.svg?style=flat-square)](https://packagist.org/packages/mixudev/laravel-timezone)
[![Status Pengujian GitHub](https://img.shields.io/github/actions/workflow/status/mixudev/Package_LaravelTimezone/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mixudev/Package_LaravelTimezone/actions)
[![Total Unduhan](https://img.shields.io/packagist/dt/mixudev/laravel-timezone.svg?style=flat-square)](https://packagist.org/packages/mixudev/laravel-timezone)
[![Lisensi](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

Sistem resolusi dan konversi timezone otomatis, akurat, ringan, dan universal untuk aplikasi Laravel.

Dirancang dengan prinsip **client-first, tanpa migrasi database, tanpa dependensi API eksternal, dan hemat beban server**. Mendukung penuh aplikasi **Monolith (Blade), REST API, SPA, Inertia.js (Vue/React), Livewire, Queue Worker, hingga Laravel Octane**.

---

## Fitur Utama

* **Tanpa Perubahan Database:** Tidak memerlukan migrasi atau penambahan kolom pada tabel database.
* **Tanpa Request Eksternal:** Tidak melakukan lookup Geo-IP atau panggilan API jaringan pada siklus HTTP standar.
* **Deteksi Akurat Berbasis Browser:** Menggunakan `Intl.DateTimeFormat()` pada sisi klien dan mengirimkannya secara efisien melalui HTTP header.
* **Aman untuk Octane & Queue Worker:** Menggunakan lifecycle container `scoped` untuk menjamin isolasi state antar-request tanpa risiko kebocoran memori.
* **Operasi Tanggal Immutable:** Mendukung `Carbon`, `CarbonImmutable`, `DateTimeInterface`, format string ISO-8601, serta timestamp Unix tanpa mengubah objek tanggal asli.
* **Validasi IANA Ketat:** Memvalidasi seluruh identifier timezone terhadap standar resmi IANA menggunakan cache in-memory berkecepatan tinggi.
* **Integrasi Universal:** Kompatibel langsung dengan Blade, Fetch API, Axios, Inertia.js, dan Livewire.

---

## Instalasi

Pasang package melalui Composer:

```bash
composer require mixudev/laravel-timezone
```

Publikasikan konfigurasi, view Blade, dan aset JavaScript (opsional):

```bash
php artisan timezone:install
```

Atau publikasikan file tertentu saja:

```bash
php artisan vendor:publish --tag=timezone-config
php artisan vendor:publish --tag=timezone-views
php artisan vendor:publish --tag=timezone-assets
```

---

## Konfigurasi Ringkas

File konfigurasi `config/timezone.php` menyediakan pengaturan yang bersih dan siap pakai:

```php
return [
    // Status aktif package
    'enabled' => env('TIMEZONE_ENABLED', true),

    // Fallback default server jika timezone klien belum terdeteksi
    'default' => env('TIMEZONE_DEFAULT', config('app.timezone', 'UTC')),

    // Pengaturan header dan cookie dari klien
    'client' => [
        'enabled' => true,
        'header' => 'X-Timezone',
        'cookie' => 'timezone',
    ],

    // Fallback session (dinonaktifkan secara default)
    'session' => [
        'enabled' => false,
        'key' => 'timezone',
    ],

    // Provider timezone model user (dinonaktifkan secara default)
    'user' => [
        'enabled' => false,
        'attribute' => 'timezone',
    ],

    // Resolver IP opsional (dinonaktifkan secara default)
    'ip' => [
        'enabled' => false,
        'resolver' => null,
    ],

    // Preset format tanggal
    'formats' => [
        'datetime' => 'Y-m-d H:i:s',
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
        'human' => 'M j, Y g:i A',
    ],
];
```

---

## Urutan Resolusi Timezone

Resolver bekerja berdasarkan hierarki prioritas berurutan:

1. **Explicit Override:** Ditentukan langsung oleh developer melalui `Timezone::in()` atau `Timezone::setExplicit()`.
2. **Client HTTP Header:** Header `X-Timezone` yang dikirim dari browser atau API client.
3. **Client Cookie:** Cookie `timezone` yang tersimpan pada browser pengguna.
4. **Authenticated User:** Provider timezone pengguna via `Timezone::useUserProvider()` atau atribut `$user->timezone`.
5. **Session Storage:** Data timezone pada session `session('timezone')` (jika diaktifkan).
6. **IP Geolocation:** Resolver kustom IP (jika diaktifkan).
7. **Config Default:** Nilai dari `config('timezone.default')` atau `config('app.timezone')`.
8. **UTC Fallback:** `UTC` sebagai jaminan akhir server.

---

## Integrasi Sisi Browser (JavaScript Client)

Muat skrip klien berukuran kurang dari 2KB tanpa dependensi eksternal di layout utama Anda (misal `resources/views/layouts/app.blade.php`):

```html
<script src="{{ asset('vendor/timezone/laravel-timezone.js') }}" defer></script>
```

Atau impor ke dalam file bundle JavaScript (Vite / Webpack):

```javascript
import './vendor/timezone/laravel-timezone.js';
```

### Mekanisme Otomatis Script Klien:
1. Membaca timezone browser lokal menggunakan `Intl.DateTimeFormat().resolvedOptions().timeZone`.
2. Menyimpan timezone di `localStorage` dan cookie berstatus `SameSite=Lax`.
3. Menambahkan header `X-Timezone` pada setiap request `fetch()` dan `axios`.
4. Mendukung auto-header pada event visit Inertia.js dan melakukan hidrasi elemen DOM pada navigasi Livewire.

---

## Penggunaan pada Blade

### 1. Komponen Progressive Enhancement

Gunakan komponen `<x-local-time>` untuk merender elemen HTML `<time>`. Komponen ini menampilkan teks server-side sebagai fallback dan langsung terhidrasi ke waktu lokal browser saat JavaScript aktif:

```blade
{{-- Format standar (Y-m-d H:i:s) --}}
<x-local-time :date="$post->created_at" />

{{-- Format preset atau pola kustom --}}
<x-local-time :date="$post->created_at" format="human" class="text-sm text-gray-500" />
<x-local-time :date="$post->created_at" format="relative" />
<x-local-time :date="$post->created_at" format="d/m/Y H:i" />

{{-- Live Real-Time Clock (Jam berdetik otomatis setiap detik tanpa refresh) --}}
<x-local-time live format="time" />
<x-local-time live format="d/m/Y H:i:s" />
<x-local-time live format="relative" />
```

### 2. Direktif Blade

```blade
{{-- Format tanggal menggunakan timezone aktif --}}
@localtime($post->created_at)
@localtime($post->created_at, 'date')
@localtime($post->created_at, 'relative')
@localtime($post->created_at, 'd/m/Y')

{{-- Menampilkan nama identifier timezone aktif --}}
@timezone
```

---

## Global Helper

```php
// Format tanggal ke timezone lokal
echo local_time($post->created_at); // "2026-08-30 17:00:00"
echo local_time($post->created_at, 'human'); // "Aug 30, 2026 5:00 PM"
echo local_time($post->created_at, 'relative'); // "5 minutes ago"

// Mengambil objek CarbonImmutable dalam timezone lokal (format diisi null)
$localCarbon = local_time($post->created_at, null);

// Mengambil identifier timezone aktif saat ini
$tz = local_timezone(); // "Asia/Jakarta"
```

---

## Facade API

```php
use Mixudev\LaravelTimezone\Facades\Timezone;

// Mengambil timezone aktif pada siklus request saat ini
$tz = Timezone::current(); // "Asia/Jakarta"

// Resolusi timezone untuk instance Request tertentu
$tz = Timezone::resolve($request);

// Konversi tanggal ke timezone lokal atau timezone spesifik (mengembalikan CarbonInterface)
$date = Timezone::convert($user->created_at);
$date = Timezone::convert($user->created_at, 'America/New_York');
$date = Timezone::convert('2026-08-30 10:00:00', 'Asia/Tokyo', 'UTC');

// Alias untuk konversi
$date = Timezone::for($post->published_at);

// Format tanggal
$formatted = Timezone::format($post->created_at, 'datetime');
$relative  = Timezone::format($post->created_at, 'relative');
$custom    = Timezone::format($post->created_at, 'd/m/Y H:i');

// Eksekusi callback dalam konteks timezone sementara (Aman untuk Queue & Octane)
$report = Timezone::in('America/New_York', function () use ($order) {
    return [
        'timezone' => Timezone::current(),
        'placed_at' => Timezone::format($order->created_at, 'human'),
    ];
});

// Validasi identifier IANA
Timezone::isValid('Asia/Jakarta'); // true
Timezone::isValid('Invalid/Zone');  // false

// Daftar seluruh identifier IANA resmi
$timezones = Timezone::list();
```

---

## Provider Timezone Pengguna

Jika aplikasi Anda menyimpan preferensi timezone pada model User, daftarkan provider kustom di dalam method `boot()` pada `AppServiceProvider`:

```php
use Mixudev\LaravelTimezone\Facades\Timezone;

public function boot(): void
{
    // Menggunakan Closure
    Timezone::useUserProvider(function ($user) {
        return $user->timezone_preference;
    });

    // Atau menggunakan class provider berantarmuka UserTimezoneProviderInterface
    // Timezone::useUserProvider(new CustomUserTimezoneProvider());
}
```

---

## Middleware

Untuk memastikan timezone diinisialisasi sebelum kontroler atau middleware lain dijalankan:

```php
// Pada bootstrap/app.php (Laravel 11, 12, 13+)
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\Mixudev\LaravelTimezone\Middleware\DetectTimezone::class);
})

// Atau pada routes/api.php
Route::middleware('timezone')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
});
```

---

## Keamanan untuk Octane, Queue, dan CLI

Aplikasi dengan worker jangka panjang (Laravel Octane, RoadRunner, FrankenPHP, Swoole, dan Queue Worker) menggunakan memori yang persisten lintas request.

Package ini didaftarkan dengan **`scoped` container lifecycle**:
* Setiap request HTTP baru mendapatkan instance `TimezoneManager` yang terisolasi.
* Bebas dari kebocoran state antar-request.
* Untuk task pada Queue Worker atau Command CLI yang memproses data lintas zona waktu, gunakan method `Timezone::in()`:

```php
public function handle(): void
{
    Timezone::in($this->user->timezone, function () {
        Mail::to($this->user)->send(new DailySummaryMail());
    });
}
```

---

## Perintah Artisan

Perintah CLI diagnostik dan pemeliharaan:

```bash
# Menampilkan ringkasan status diagnostik resolusi timezone
php artisan timezone:detect

# Validasi identifier timezone tertentu
php artisan timezone:detect --tz=Asia/Jakarta
php artisan timezone:detect --tz=Invalid/Zone

# Membersihkan cache memori dan context memoized
php artisan timezone:clear-cache
```

---

## Dokumentasi Lengkap

Panduan mendalam tersedia pada folder [`docs/`](docs/):

* [1. Panduan Instalasi dan Konfigurasi](docs/01-instalasi-dan-konfigurasi.md)
* [2. Arsitektur dan Pipeline Resolver](docs/02-arsitektur-dan-resolver.md)
* [3. Integrasi Blade dan Komponen View](docs/03-blade-dan-komponen.md)
* [4. Penggunaan Helper dan Facade API](docs/04-helper-dan-facade.md)
* [5. Integrasi Frontend (JS, Axios, Inertia, Livewire)](docs/05-integrasi-frontend.md)
* [6. Provider User dan Fallback Session](docs/06-user-dan-session-provider.md)
* [7. Panduan Octane, Queue Worker, dan CLI](docs/07-octane-queue-dan-cli.md)
* [8. Keamanan, Validasi, dan Optimasi Performa](docs/08-keamanan-dan-performa.md)

---

## Pengujian

Jalankan rangkaian unit & feature test:

```bash
vendor/bin/phpunit
```

Jalankan analisis statis PHPStan:

```bash
vendor/bin/phpstan analyse
```

---

## Lisensi

Lisensi MIT. Silakan merujuk ke file [LICENSE](LICENSE) untuk informasi lebih lanjut.
