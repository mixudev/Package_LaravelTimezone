# 8. Keamanan, Validasi, dan Optimasi Performa

Halaman ini mendokumentasikan langkah-langkah mitigasi keamanan serta optimasi performa berkecepatan tinggi yang diimplementasikan di dalam `mixudev/laravel-timezone`.

---

## 1. Perlindungan Keamanan (Security)

Header HTTP seperti `X-Timezone` maupun cookie dari klien merupakan data masukan dari luar (*user input*) yang tidak boleh dipercaya secara mentah-mentah (*untrusted input*).

### Mitigasi Serangan String Arbitrer & Injeksi
`TimezoneValidator` menerapkan batasan validasi ketat sebelum string diteruskan ke engine PHP atau Carbon:

1. **Pemeriksaan Panjang & Karakter Karakter Khusus:**
   * Batas maksimal panjang string adalah 100 karakter.
   * Hanya mengizinkan karakter alfanumerik dan simbol path timezone standar (`a-zA-Z0-9_/+-`).
   * Menolak langsung string yang mengandung tag HTML, skrip JavaScript (`<script>`), karakter kontrol, atau karakter SQL injection.
2. **Validasi Identifier IANA Resmi:**
   * String wajib terdaftar dalam daftar resmi IANA database (`timezone_identifiers_list()`).
   * String yang tidak dikenal (misal `"Invalid/Timezone"`, `"Asia/FakeCity"`, dsb.) akan langsung ditolak dan resolver akan beralih ke fallback berikutnya.

```php
// Contoh penolakan otomatis
TimezoneValidator::isValid("<script>alert('xss')</script>"); // false
TimezoneValidator::isValid("Asia/Jakarta; DROP TABLE users;"); // false
TimezoneValidator::isValid("Asia/Jakarta"); // true
```

---

## 2. Optimasi Performa (Performance)

### O(1) In-Memory Lookup Hash Table
Memanggil fungsi native PHP `DateTimeZone::listIdentifiers()` atau melakukan instansiasi `new DateTimeZone($string)` berulang kali pada setiap siklus request dapat menimbulkan overhead CPU.

`TimezoneValidator` mengompilasi seluruh identifier timezone resmi ke dalam **struktur data Hash Map (Array Key Lookup)** pada saat pertama kali diakses:

```php
// Lookup berkecepatan instan O(1)
if (isset(self::$validTimezones[$timezone])) {
    return true;
}
```

Hasil: Validasi timezone selesai dalam hitungan **mikrodetik** dengan beban memori yang sangat minimal.

### Zero Database Query Overhead
Secara default, package bekerja sepenuhnya di layer memori aplikasi dan HTTP request header. **Tidak ada satupun query SQL/database** yang dieksekusi hanya untuk meresolusi zona waktu pengguna.

### Zero Latency Network Call
Package tidak melakukan panggilan REST API eksternal (seperti IP Geolocation pihak ketiga) pada siklus HTTP request standar, sehingga waktu respon (*Time to First Byte / TTFB*) aplikasi Anda tetap optimal.

### Operasi Tanggal Immutable
Fungsi konversi menggunakan class `CarbonImmutable` atau melakukan cloning (`$carbon->copy()`) sebelum manipulasi zona waktu dilakukan. Hal ini mencegah bug mutasi state tanggal di bagian lain aplikasi.
