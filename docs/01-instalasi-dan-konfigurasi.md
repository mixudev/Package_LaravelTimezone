# 1. Panduan Instalasi dan Konfigurasi

Halaman ini memandu proses pemasangan package `mixudev/laravel-timezone` pada aplikasi Laravel beserta penjelasan lengkap opsi konfigurasinya.

---

## Persyaratan Sistem

* **PHP:** 8.2 atau versi lebih baru
* **Laravel Framework:** 10.x, 11.x, 12.x, atau 13.x
* **Ekstensi PHP:** `intl` (direkomendasikan), `date`, `pcre`

---

## Langkah Pemasangan

### 1. Pemasangan via Composer

Jalankan perintah berikut di root direktori project Laravel Anda:

```bash
composer require mixudev/laravel-timezone
```

Package ini mendukung fitur Laravel Package Discovery, sehingga Service Provider (`TimezoneServiceProvider`) dan Facade (`Timezone`) akan terdaftar secara otomatis.

### 2. Publikasi Aset (Opsional)

Untuk mempublikasikan file konfigurasi, view Blade, dan skrip JavaScript klien:

```bash
php artisan timezone:install
```

Atau publikasikan komponen tertentu menggunakan tag:

```bash
# Publikasi file konfigurasi ke config/timezone.php
php artisan vendor:publish --tag=timezone-config

# Publikasi template Blade ke resources/views/vendor/timezone/
php artisan vendor:publish --tag=timezone-views

# Publikasi skrip JS ke public/vendor/timezone/
php artisan vendor:publish --tag=timezone-assets
```

---

## Struktur File Konfigurasi

File konfigurasi berada pada `config/timezone.php`:

```php
<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Status Aktif Package
    |--------------------------------------------------------------------------
    |
    | Menentukan apakah sistem timezone diaktifkan. Jika bernilai false,
    | package akan langsung mengembalikan timezone default aplikasi.
    |
    */
    'enabled' => env('TIMEZONE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Timezone Fallback Server
    |--------------------------------------------------------------------------
    |
    | Timezone yang digunakan ketika klien tidak mengirimkan header/cookie
    | dan tidak ada provider lain yang berhasil mengembalikan timezone valid.
    |
    */
    'default' => env('TIMEZONE_DEFAULT', config('app.timezone', 'UTC')),

    /*
    |--------------------------------------------------------------------------
    | Deteksi Sisi Klien (Header & Cookie)
    |--------------------------------------------------------------------------
    |
    | Pengaturan nama header HTTP dan cookie untuk membaca timezone
    | dari perangkat browser atau API client.
    |
    */
    'client' => [
        'enabled' => true,
        'header' => 'X-Timezone',
        'cookie' => 'timezone',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resolusi Berbasis Session
    |--------------------------------------------------------------------------
    |
    | Membaca timezone dari session pengguna jika diaktifkan.
    | Dinonaktifkan secara default agar package tidak terikat pada session.
    |
    */
    'session' => [
        'enabled' => false,
        'key' => 'timezone',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model User Provider
    |--------------------------------------------------------------------------
    |
    | Mengambil timezone dari atribut atau method pada model User yang sedang
    | terautentikasi ($request->user()).
    |
    */
    'user' => [
        'enabled' => false,
        'attribute' => 'timezone',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resolver IP Geolocation Opsional
    |--------------------------------------------------------------------------
    |
    | Dinonaktifkan secara default untuk menghindari latensi jaringan.
    | Anda dapat menentukan class resolver kustom di sini jika diperlukan.
    |
    */
    'ip' => [
        'enabled' => false,
        'resolver' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Preset Format Tanggal
    |--------------------------------------------------------------------------
    |
    | Format bawaan yang dapat dipanggil melalui Timezone::format($date, 'nama_preset')
    | atau direktif @localtime($date, 'nama_preset').
    |
    */
    'formats' => [
        'datetime' => 'Y-m-d H:i:s',
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
        'human' => 'M j, Y g:i A',
    ],
];
```

---

## Variabel Environment (.env)

Anda dapat mengontrol pengaturan dasar melalui file `.env`:

```ini
# Menonaktifkan atau mengaktifkan resolusi timezone
TIMEZONE_ENABLED=true

# Menentukan timezone fallback khusus package
TIMEZONE_DEFAULT=Asia/Jakarta
```
