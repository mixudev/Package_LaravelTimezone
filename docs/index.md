# Dokumentasi mixudev/laravel-timezone

Selamat datang di dokumentasi resmi **`mixudev/laravel-timezone`**. Package ini menyediakan sistem resolusi dan penanganan zona waktu (timezone) otomatis, akurat, modular, dan hemat sumber daya untuk aplikasi Laravel modern.

---

## Daftar Isi Dokumentasi

1. [Instalasi dan Konfigurasi](01-instalasi-dan-konfigurasi.md)
   - Persyaratan sistem
   - Instalasi Composer
   - Publikasi aset dan konfigurasi
   - Penjelasan struktur file konfigurasi

2. [Arsitektur dan Pipeline Resolver](02-arsitektur-dan-resolver.md)
   - Alur kerja resolusi bertingkat
   - Hierarki prioritas resolver
   - Pembuatan resolver kustom
   - Validasi IANA dan memoization

3. [Integrasi Blade dan Komponen View](03-blade-dan-komponen.md)
   - Penggunaan komponen `<x-local-time>`
   - Direktif `@localtime` dan `@timezone`
   - Progressive enhancement dan fallback server-side

4. [Penggunaan Helper dan Facade API](04-helper-dan-facade.md)
   - Fungsi global `local_time()`, `local_timezone()`
   - Facade `Timezone::current()`, `convert()`, `for()`, `format()`
   - Eksekusi aman dalam konteks dengan `Timezone::in()`

5. [Integrasi Frontend (JavaScript, Axios, Inertia, Livewire)](05-integrasi-frontend.md)
   - Skrip browser `laravel-timezone.js`
   - Sinkronisasi otomatis `X-Timezone` pada Fetch dan Axios
   - Penanganan pada Inertia.js (Vue / React)
   - Integrasi navigasi Livewire

6. [Provider User dan Fallback Session](06-user-dan-session-provider.md)
   - Registrasi dynamic user provider
   - Implementasi `UserTimezoneProviderInterface`
   - Konfigurasi fallback session opsional

7. [Panduan Laravel Octane, Queue Worker, dan CLI](07-octane-queue-dan-cli.md)
   - Pencegahan state leakage pada Octane, FrankenPHP, RoadRunner, Swoole
   - Pola aman penanganan tanggal pada antrean (Queue Jobs)
   - Perintah diagnostik Artisan

8. [Keamanan, Validasi, dan Optimasi Performa](08-keamanan-dan-performa.md)
   - Mitigasi injeksi string dan spoofing header
   - Cache lookup O(1) in-memory
   - Penanganan DateTimeInterface dan operasi tanggal immutable

---

## Filosofi Desain

* **Zero Required Database Modification:** Tidak mewajibkan migrasi tabel atau perubahan skema database bawaan aplikasi Anda.
* **Zero Required External API:** Tidak bergantung pada layanan eksternal (seperti lookup Geo-IP pihak ketiga) secara default untuk setiap siklus HTTP request.
* **Client-First Precision:** Memprioritaskan zona waktu aktual perangkat pengguna melalui API standar browser `Intl.DateTimeFormat()`.
* **Server-Light Fallback:** Memiliki fallback berjenjang yang aman hingga `config('app.timezone')` dan `UTC`.
* **Framework Native:** Terintegrasi penuh dengan ekosistem Carbon, Blade, Middleware, Service Provider, dan Artisan Laravel.
