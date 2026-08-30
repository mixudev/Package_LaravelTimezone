# 5. Integrasi Frontend (JavaScript, Axios, Inertia, Livewire)

Package `mixudev/laravel-timezone` menyertakan skrip JavaScript sisi klien berukuran sangat kecil (<2KB), tanpa dependensi eksternal (pure vanilla JS), yang bertugas menyinkronkan zona waktu perangkat ke server Laravel.

---

## 1. Pemasangan Skrip Browser

### Menggunakan Tag HTML Script

Publikasikan aset terlebih dahulu:
```bash
php artisan vendor:publish --tag=timezone-assets
```

Lalu muat pada file template induk Anda (misal `resources/views/layouts/app.blade.php`):

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aplikasi Saya</title>
    <!-- Muat skrip timezone dengan atribut defer -->
    <script src="{{ asset('vendor/timezone/laravel-timezone.js') }}" defer></script>
</head>
<body>
    <!-- Konten halaman -->
</body>
</html>
```

### Menggunakan Vite / Webpack (NPM Bundle)

Jika Anda menggunakan bundler modern (seperti Vite pada Laravel 10/11/12):

```javascript
// resources/js/app.js
import './../../public/vendor/timezone/laravel-timezone.js';
```

---

## 2. Cara Kerja Skrip Browser

Saat halaman dimuat, skrip secara otomatis:

1. **Mendeteksi Timezone:** Membaca timezone native browser melalui:
   ```javascript
   Intl.DateTimeFormat().resolvedOptions().timeZone; // Contoh: "Asia/Jakarta"
   ```
2. **Menyimpan Cache Klien:** Menyimpan nilai ke `localStorage` dan cookie `timezone` (berdurasi 1 tahun, `SameSite=Lax`).
3. **Mengatur Interceptor HTTP:**
   * **Fetch API:** Membungkus `window.fetch()` untuk menyisipkan header `X-Timezone: Asia/Jakarta` pada setiap request yang belum memiliki header tersebut.
   * **Axios:** Mengatur `window.axios.defaults.headers.common['X-Timezone'] = tz` jika library Axios tersedia.
   * **Inertia.js:** Mendengarkan event `inertia:start` untuk menyisipkan header pada payload request Inertia, serta mendengarkan `inertia:finish` untuk menghidrasi ulang elemen waktu.
   * **Livewire:** Mendengarkan event `livewire:navigated` untuk merender ulang elemen tanggal secara dinamis.
4. **Hidrasi DOM Otomatis:** Mencari semua tag `<time data-local-time>` dan mengonversi waktu UTC ISO-8601 ke string waktu lokal perangkat menggunakan `Intl.DateTimeFormat` atau format relatif.

---

## 3. Integrasi SPA / Inertia.js (Vue & React)

### Vue 3 / React Component Helper

Jika Anda menggunakan Inertia.js dengan Vue atau React dan ingin memformat tanggal langsung pada komponen frontend:

```vue
<!-- Contoh Komponen Vue 3: LocalTime.vue -->
<template>
  <time :datetime="datetime">{{ formattedText }}</time>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  datetime: { type: String, required: true }, // Kirimkan ISO-8601 UTC dari Laravel
  format: { type: String, default: 'datetime' }
});

const formattedText = computed(() => {
  if (window.LaravelTimezone) {
    return window.LaravelTimezone.formatDate(props.datetime, props.format);
  }
  return props.datetime;
});
</script>
```

---

## 4. API JavaScript Global (`window.LaravelTimezone`)

Objek global `window.LaravelTimezone` diekspos untuk kebutuhan integrasi tingkat lanjut:

```javascript
// 1. Mengambil timezone perangkat saat ini
const tz = window.LaravelTimezone.get(); // "Asia/Jakarta"

// 2. Format string ISO ke format lokal
const formatted = window.LaravelTimezone.formatDate('2026-08-30T10:00:00Z', 'human');
// Hasil: "Aug 30, 2026, 5:00 PM"

// 3. Format relatif
const relative = window.LaravelTimezone.formatRelative(new Date('2026-08-30T09:50:00Z'));
// Hasil: "10 min ago"

// 4. Memicu hidrasi manual pada kontainer DOM tertentu
const modalElement = document.getElementById('my-modal');
window.LaravelTimezone.hydrate(modalElement);
```
