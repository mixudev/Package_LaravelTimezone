# 6. Provider User dan Fallback Session

Package ini tidak mengasumsikan struktur tabel pengguna (`users`) Anda atau mewajibkan penggunaan Session. Namun, package menyediakan antarmuka modular jika Anda ingin mengaitkan zona waktu dengan akun pengguna atau session.

---

## 1. Provider Timezone Pengguna (User Timezone Provider)

Jika pengguna di aplikasi Anda dapat memilih zona waktu favorit pada menu pengaturan akun, Anda dapat mendaftarkan logika pengambilan timezone tersebut ke package.

### Pendekatan A: Menggunakan Closure (Paling Sederhana)

Di dalam method `boot()` pada `app/Providers/AppServiceProvider.php`:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Mixudev\LaravelTimezone\Facades\Timezone;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Timezone::useUserProvider(function ($user) {
            // $user adalah instance user yang sedang login ($request->user())
            return $user->timezone_preference ?? null;
        });
    }
}
```

### Pendekatan B: Menggunakan Interface `UserTimezoneProviderInterface`

Jika Anda memiliki logika yang lebih kompleks (misal mengambil timezone dari relasi profil, organisasi, atau cache):

```php
namespace App\Services;

use Mixudev\LaravelTimezone\Contracts\UserTimezoneProviderInterface;

class CustomUserTimezoneProvider implements UserTimezoneProviderInterface
{
    public function getTimezone(mixed $user): ?string
    {
        // Contoh: Ambil dari preferensi user, jika kosong ambil dari organisasi
        if (!empty($user->timezone)) {
            return $user->timezone;
        }

        if ($user->organization && !empty($user->organization->timezone)) {
            return $user->organization->timezone;
        }

        return null;
    }
}
```

Lalu daftarkan class tersebut pada `AppServiceProvider`:

```php
use App\Services\CustomUserTimezoneProvider;
use Mixudev\LaravelTimezone\Facades\Timezone;

public function boot(): void
{
    Timezone::useUserProvider(new CustomUserTimezoneProvider());
}
```

### Pendekatan C: Konfigurasi Otomatis via `config/timezone.php`

Jika atribut pada model `User` bernama standar (misal kolom `$user->timezone`), Anda cukup mengaktifkannya di config:

```php
// config/timezone.php
'user' => [
    'enabled' => true,
    'attribute' => 'timezone', // nama kolom atau nama method (misal getTimezone())
],
```

---

## 2. Fallback Berbasis Session

Secara default, resolusi berbasis session dinonaktifkan untuk menjaga package tetap stateless dan cepat. Jika Anda ingin menggunakan session sebagai penyimpanan sementara zona waktu:

### 1. Aktifkan pada Konfigurasi

```php
// config/timezone.php
'session' => [
    'enabled' => true,
    'key' => 'timezone',
],
```

### 2. Menyimpan Nilai ke Session

Di dalam Controller saat pengguna mengganti zona waktu secara manual dari dropdown selector:

```php
public function changeTimezone(Request $request)
{
    $request->validate([
        'timezone' => ['required', 'string'],
    ]);

    if (Timezone::isValid($request->timezone)) {
        session(['timezone' => $request->timezone]);
    }

    return back()->with('status', 'Zona waktu berhasil diperbarui.');
}
```

Resolver akan otomatis membaca nilai `session('timezone')` jika header dari browser belum tersedia.
