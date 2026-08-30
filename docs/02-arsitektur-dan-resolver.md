# 2. Arsitektur dan Pipeline Resolver

Bagian ini menguraikan arsitektur internal `TimezoneManager` serta mekanisme rantai resolver (*Resolver Pipeline*) yang digunakan untuk menentukan zona waktu aktif.

---

## Diagram Alur Resolusi

```text
Request Masuk
     │
     ▼
┌───────────────────────────────────────────────┐
│ 1. Explicit Override (Timezone::in / explicit)│ ──► Jika ada & valid ──► Kembalikan
└───────────────────────────────────────────────┘
     │ Tidak ada
     ▼
┌───────────────────────────────────────────────┐
│ 2. Client HTTP Header (X-Timezone)            │ ──► Jika ada & valid ──► Kembalikan
└───────────────────────────────────────────────┘
     │ Tidak ada
     ▼
┌───────────────────────────────────────────────┐
│ 3. Client Cookie (timezone)                   │ ──► Jika ada & valid ──► Kembalikan
└───────────────────────────────────────────────┘
     │ Tidak ada
     ▼
┌───────────────────────────────────────────────┐
│ 4. Authenticated User Provider                │ ──► Jika ada & valid ──► Kembalikan
└───────────────────────────────────────────────┘
     │ Tidak ada
     ▼
┌───────────────────────────────────────────────┐
│ 5. Session Storage (session('timezone'))      │ ──► Jika ada & valid ──► Kembalikan
└───────────────────────────────────────────────┘
     │ Tidak ada
     ▼
┌───────────────────────────────────────────────┐
│ 6. IP Geolocation Resolver                    │ ──► Jika ada & valid ──► Kembalikan
└───────────────────────────────────────────────┘
     │ Tidak ada
     ▼
┌───────────────────────────────────────────────┐
│ 7. Config Default (config('timezone.default'))│ ──► Jika valid ────────► Kembalikan
└───────────────────────────────────────────────┘
     │ Tidak ada
     ▼
┌───────────────────────────────────────────────┐
│ 8. UTC Baseline Guarantee                     │ ──► Selalu 'UTC'
└───────────────────────────────────────────────┘
```

---

## Rincian Setiap Resolver

| Resolver | Deskripsi | Standar Default |
| :--- | :--- | :--- |
| **ExplicitTimezoneResolver** | Override manual oleh developer melalui kode backend. Memiliki prioritas absolut. | Dinamis via `Timezone::setExplicit()` atau `Timezone::in()`. |
| **HeaderTimezoneResolver** | Membaca header HTTP yang dikirimkan oleh browser atau HTTP client API. | Header: `X-Timezone`. |
| **CookieTimezoneResolver** | Membaca cookie yang tersimpan pada browser klien. | Cookie: `timezone`. |
| **UserTimezoneResolver** | Mengambil timezone dari user yang sedang login via callback atau atribut model. | Dinonaktifkan default (`timezone.user.enabled = false`). |
| **SessionTimezoneResolver** | Membaca nilai dari Laravel Session. | Dinonaktifkan default (`timezone.session.enabled = false`). |
| **IpTimezoneResolver** | Memanggil resolver geolocation IP pihak ketiga secara opsional. | Dinonaktifkan default (`timezone.ip.enabled = false`). |
| **ConfigTimezoneResolver** | Membaca konfigurasi aplikasi lokal. | `config('timezone.default')` atau `config('app.timezone')`. |
| **UtcFallbackResolver** | Jaminan baseline akhir jika semua langkah di atas tidak menghasilkan nilai valid. | `UTC`. |

---

## Antarmuka TimezoneResolverInterface

Setiap resolver pada rantai wajib mengimplementasikan interface:

```php
namespace Mixudev\LaravelTimezone\Contracts;

use Illuminate\Http\Request;

interface TimezoneResolverInterface
{
    /**
     * Resolusi identifier timezone. Mengembalikan null jika tidak ditemukan/tidak valid.
     */
    public function resolve(?Request $request = null): ?string;

    /**
     * Menentukan apakah resolver ini aktif dan perlu dieksekusi.
     */
    public function shouldRun(): bool;

    /**
     * Nama unik pengenal resolver.
     */
    public function name(): string;
}
```

---

## Membuat Resolver Kustom

Anda dapat menambahkan resolver sendiri ke dalam pipeline. Contoh resolver yang membaca header khusus subdomain:

```php
namespace App\Resolvers;

use Illuminate\Http\Request;
use Mixudev\LaravelTimezone\Contracts\TimezoneResolverInterface;
use Mixudev\LaravelTimezone\Support\TimezoneValidator;

class SubdomainTimezoneResolver implements TimezoneResolverInterface
{
    public function resolve(?Request $request = null): ?string
    {
        if ($request === null) {
            return null;
        }

        $host = $request->getHost();

        // Contoh: id.example.com -> Asia/Jakarta, jp.example.com -> Asia/Tokyo
        if (str_starts_with($host, 'id.')) {
            return 'Asia/Jakarta';
        }

        if (str_starts_with($host, 'jp.')) {
            return 'Asia/Tokyo';
        }

        return null;
    }

    public function shouldRun(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'subdomain';
    }
}
```

Daftarkan resolver tersebut pada `AppServiceProvider::boot()`:

```php
use App\Resolvers\SubdomainTimezoneResolver;
use Mixudev\LaravelTimezone\Facades\Timezone;

public function boot(): void
{
    // Sisipkan resolver baru pada urutan kedua (setelah explicit override)
    Timezone::addResolver(new SubdomainTimezoneResolver(), 1);
}
```

---

## Request-Scoped Memoization

Untuk efisiensi maksimal, pemanggilan berulang `Timezone::current()` atau `Timezone::resolve()` pada request yang sama **tidak akan mengevaluasi ulang seluruh rantai resolver**. Nilai yang telah diresolusi disimpan di dalam instance `TimezoneManager` untuk siklus request tersebut.
