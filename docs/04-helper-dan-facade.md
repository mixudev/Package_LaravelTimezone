# 4. Penggunaan Helper dan Facade API

Bagian ini membahas seluruh fungsi helper global dan method yang tersedia pada Facade `Timezone`.

---

## 1. Global Helper Functions

Package ini mendaftarkan 3 helper global yang dapat dipanggil di mana saja (Controller, Service, Repository, Blade, dsb.):

### `local_time()`

```php
function local_time(
    mixed $date = null,
    ?string $format = 'datetime',
    ?string $timezone = null
): string|\Carbon\CarbonInterface
```

* **Mengambil Tanggal Terformat:**
  ```php
  $formatted = local_time($post->created_at); // "2026-08-30 17:00:00"
  $human     = local_time($post->created_at, 'human'); // "Aug 30, 2026 5:00 PM"
  $relative  = local_time($post->created_at, 'relative'); // "10 minutes ago"
  $custom    = local_time($post->created_at, 'd/m/Y'); // "30/08/2026"
  ```

* **Mengambil Objek Carbon (Tanpa String Formatting):**
  Kirimkan argumen `$format = null` untuk mendapatkan instance `CarbonInterface` / `CarbonImmutable` yang sudah disetel ke timezone lokal:
  ```php
  $carbonLocal = local_time($post->created_at, null);

  // Akses method Carbon secara leluasa
  echo $carbonLocal->hour; // 17
  echo $carbonLocal->startOfDay()->toDateTimeString();
  ```

### `local_timezone()` dan `local_timezone_name()`

Mengembalikan string nama identifier IANA timezone yang aktif:

```php
$tz = local_timezone(); // "Asia/Jakarta"
$name = local_timezone_name(); // "Asia/Jakarta"
```

---

## 2. Facade `Timezone` API

Import Facade:
```php
use Mixudev\LaravelTimezone\Facades\Timezone;
```

### Ringkasan Method Utama

| Method | Return Type | Deskripsi |
| :--- | :--- | :--- |
| `Timezone::current()` | `string` | Mengambil identifier timezone aktif pada request ini. |
| `Timezone::resolve(?Request $request)` | `string` | Menjalankan resolusi timezone pada instance request tertentu. |
| `Timezone::convert($date, $to, $from)` | `CarbonInterface` | Mengonversi tanggal ke timezone target (default: timezone aktif). |
| `Timezone::for($date, $timezone)` | `CarbonInterface` | Alias ringkas untuk `Timezone::convert()`. |
| `Timezone::format($date, $format, $timezone)` | `string` | Mengonversi dan memformat tanggal sekaligus. |
| `Timezone::in($timezone, Closure $callback)` | `mixed` | Mengeksekusi callback dalam konteks timezone sementara. |
| `Timezone::setExplicit(?string $timezone)` | `TimezoneManager` | Mengatur override eksplisit secara manual. |
| `Timezone::isValid(?string $timezone)` | `bool` | Memvalidasi apakah string merupakan identifier IANA yang sah. |
| `Timezone::list()` | `array<int, string>` | Mengambil daftar seluruh timezone resmi IANA. |
| `Timezone::getResolvedInfo(?Request $request)` | `ResolvedTimezone` | Mengambil DTO berisi informasi sumber resolver yang memenangkan resolusi. |
| `Timezone::getResolvedSource()` | `?string` | Mengambil nama resolver pemenang (`'header'`, `'user'`, `'config'`, dll). |
| `Timezone::flush()` | `void` | Mereset memoization request saat ini. |
| `Timezone::clearCache()` | `void` | Membersihkan memoization dan cache validasi statis. |

---

## 3. Contoh Penggunaan Lengkap

### Konversi Tanggal yang Aman (Immutable)

Package ini tidak akan pernah memutasi objek `Carbon` asli Anda secara tidak sengaja:

```php
$utcCarbon = now('UTC'); // 10:00:00 UTC

// Konversi ke timezone lokal (Asia/Jakarta -> 17:00:00)
$jakartaCarbon = Timezone::convert($utcCarbon);

// Objek asli tetap UTC
echo $utcCarbon->timezoneName; // "UTC"
echo $jakartaCarbon->timezoneName; // "Asia/Jakarta"
```

### Eksekusi Terisolasi dengan `Timezone::in()`

Method `in()` sangat berguna ketika Anda perlu memproses data dalam timezone tertentu tanpa memengaruhi state aplikasi di luar closure tersebut:

```php
public function generateReportForStore(Store $store)
{
    return Timezone::in($store->timezone, function () use ($store) {
        $today = now(); // Berada di timezone toko

        return [
            'store' => $store->name,
            'report_date' => Timezone::format($today, 'date'),
            'orders' => Order::whereBetween('created_at', [
                $today->startOfDay()->toIso8601String(),
                $today->endOfDay()->toIso8601String(),
            ])->get(),
        ];
    });
}
```

State sebelumnya dijamin akan dipulihkan secara sempurna bahkan jika terjadi `Exception` di dalam closure.

### Validasi Timezone

Gunakan sebelum menyimpan input preferensi timezone dari form:

```php
public function updateProfile(Request $request)
{
    $request->validate([
        'timezone' => ['required', 'string', function ($attribute, $value, $fail) {
            if (!Timezone::isValid($value)) {
                $fail("Zona waktu [{$value}] tidak valid.");
            }
        }],
    ]);

    $request->user()->update([
        'timezone' => $request->input('timezone'),
    ]);
}
```
