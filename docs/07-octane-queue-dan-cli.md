# 7. Panduan Laravel Octane, Queue Worker, dan CLI

Aplikasi dengan arsitektur modern sering kali menjalankan worker dalam proses jangka panjang (*long-running processes*) seperti **Laravel Octane (FrankenPHP, RoadRunner, Swoole)**, **Queue Workers (`queue:work`)**, dan **Artisan CLI Commands**.

---

## 1. Keamanan pada Laravel Octane

### Masalah pada Aplikasi Long-Running
Pada server PHP tradisional (PHP-FPM), setiap request HTTP membersihkan seluruh memori dari nol. Namun pada Laravel Octane, server tetap menyala dan satu proses worker menangani ratusan request berturut-turut. Jika package menyimpan zona waktu pengguna dalam *singleton static*, zona waktu dari Pengguna A bisa **bocor (state leakage)** ke Pengguna B.

### Solusi Scoped Binding
`mixudev/laravel-timezone` mendaftarkan `TimezoneManager` menggunakan **`scoped` container lifecycle** bawaan Laravel:

```php
$this->app->scoped(TimezoneManager::class, function (Container $app) {
    return new TimezoneManager(...);
});
```

* Setiap kali request HTTP baru masuk ke Octane worker, container Laravel otomatis menyediakan instance `TimezoneManager` yang bersih dan baru.
* Saat request selesai (`RequestTerminated`), seluruh context memoized dibuang secara otomatis.
* Hasil: **100% aman dari kebocoran state lintas pengguna.**

---

## 2. Praktik Terbaik pada Queue Workers

Queue Worker tidak memiliki konteks browser atau header HTTP dari pengguna. Oleh karena itu:

### Jangan Mengubah Timezone Global Aplikasi
Hindari memanggil `date_default_timezone_set()` di dalam job antrean karena akan memengaruhi seluruh job berikutnya yang dijalankan oleh worker tersebut.

### Gunakan `Timezone::in()` untuk Eksekusi Terisolasi

```php
namespace App\Jobs;

use App\Models\User;
use App\Mail\MonthlyReportMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Mixudev\LaravelTimezone\Facades\Timezone;

class SendMonthlyReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user)
    {
    }

    public function handle(): void
    {
        // Tentukan timezone target (fallback ke UTC jika user tidak punya preferensi)
        $targetTimezone = $this->user->timezone ?? 'UTC';

        // Jalankan logika email di dalam konteks timezone user tersebut
        Timezone::in($targetTimezone, function () {
            // Waktu lokal terformat sesuai zona waktu pengguna
            $greetingTime = local_time(now(), 'human');

            Mail::to($this->user)->send(new MonthlyReportMail(
                user: $this->user,
                renderedTime: $greetingTime
            ));
        });
        
        // Setelah closure selesai, timezone kembali ke state semula secara otomatis
    }
}
```

---

## 3. Perintah Diagnostik Artisan CLI

Package menyertakan perintah Artisan untuk membantu proses debugging, deployment, dan pemeliharaan:

### `php artisan timezone:detect`

Menampilkan tabel status resolusi timezone aktif beserta informasi detail sistem:

```bash
php artisan timezone:detect
```

Output:
```text
Laravel Timezone Diagnostic
+--------------------------------+--------------------+
| Metric                         | Value              |
+--------------------------------+--------------------+
| Current Resolved Timezone      | Asia/Jakarta       |
| Resolution Source              | config             |
| Is Fallback Active             | No                 |
| Application Default (app.tz)   | UTC                |
| Package Default (timezone.def) | Asia/Jakarta       |
| System Time (UTC)              | 2026-08-30 04:00:00|
| Formatted Local Time           | 2026-08-30 11:00:00|
| Total Available IANA Timezones | 425                |
+--------------------------------+--------------------+
```

### Validasi Identifier Tertentu

```bash
php artisan timezone:detect --tz=Asia/Jakarta
# Valid IANA timezone: Yes
# Canonical name: Asia/Jakarta
# Current local time: 2026-08-30 11:00:00

php artisan timezone:detect --tz=Invalid/Zone
# Valid IANA timezone: No
```

### `php artisan timezone:clear-cache`

Membersihkan seluruh cache internal in-memory validator dan instance memoization:

```bash
php artisan timezone:clear-cache
```
