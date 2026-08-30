# 3. Integrasi Blade dan Komponen View

Package ini menyediakan Blade Directive dan Blade Component yang dirancang untuk mendukung **Progressive Enhancement**.

---

## 1. Blade Component `<x-local-time>`

Komponen `<x-local-time>` adalah pendekatan terbaik untuk merender tanggal pada aplikasi web berbasis Blade.

### Cara Penggunaan Dasar

```blade
<x-local-time :date="$post->created_at" />
```

Output HTML yang dihasilkan:
```html
<time
    data-local-time
    datetime="2026-08-30T10:00:00+00:00"
    data-format="datetime"
    class="local-time"
>2026-08-30 17:00:00</time>
```

### Opsi dan Properti Komponen

| Properti | Tipe Data | Nilai Default | Keterangan |
| :--- | :--- | :--- | :--- |
| `date` | `mixed` | `null` (waktu sekarang) | Mendukung `Carbon`, `CarbonImmutable`, `DateTimeInterface`, string ISO, timestamp Unix. |
| `format` | `string` | `'datetime'` | Preset (`datetime`, `date`, `time`, `human`, `relative`) atau pola format PHP (`d/m/Y H:i`). |
| `timezone` | `string|null` | `null` | Menentukan timezone target secara eksplisit (jika ingin mengabaikan timezone aktif). |

### Contoh Berbagai Format

```blade
{{-- Format tanggal saja (2026-08-30) --}}
<x-local-time :date="$user->created_at" format="date" />

{{-- Format jam saja (17:00:00) --}}
<x-local-time :date="$transaction->paid_at" format="time" />

{{-- Format ramah pengguna (Aug 30, 2026 5:00 PM) --}}
<x-local-time :date="$article->published_at" format="human" />

{{-- Format relatif (5 minutes ago / 2 hours ago) --}}
<x-local-time :date="$comment->created_at" format="relative" />

{{-- Format kustom pola PHP --}}
<x-local-time :date="$invoice->due_date" format="d F Y - H:i" />

{{-- Menambahkan atribut HTML / class styling --}}
<x-local-time
    :date="$order->created_at"
    format="human"
    class="text-sm font-medium text-gray-600 dark:text-gray-300"
    id="order-time-{{ $order->id }}"
/>
```

### Mengapa Progressive Enhancement?
1. **SEO & Server-Side Rendering:** Mesin pencari dan browser tanpa JavaScript langsung menerima tanggal yang terformat rapi dari server (menggunakan fallback timezone server).
2. **Hidrasi Instan Tanpa Kedip:** Saat JavaScript klien aktif di browser, skrip membaca atribut `datetime` (UTC ISO string) dan langsung memperbarui teks secara lokal sesuai timezone perangkat pengguna tanpa mengirim request AJAX tambahan.

---

## 2. Direktif Blade `@localtime`

Jika Anda hanya ingin mencetak teks tanggal tanpa membungkusnya dalam tag `<time>`, gunakan direktif `@localtime`:

```blade
{{-- Format default --}}
<p>Dibuat pada: @localtime($post->created_at)</p>

{{-- Menggunakan preset --}}
<p>Tanggal: @localtime($post->created_at, 'date')</p>
<p>Waktu: @localtime($post->created_at, 'human')</p>
<p>Relatif: @localtime($post->created_at, 'relative')</p>

{{-- Menggunakan pola kustom --}}
<p>Kustom: @localtime($post->created_at, 'd/m/Y H:i')</p>

{{-- Tanpa argumen (menampilkan waktu saat ini) --}}
<footer>Waktu Server Saat Ini: @localtime</footer>
```

---

## 3. Direktif Blade `@timezone`

Menampilkan nama identifier IANA timezone yang sedang aktif pada siklus request saat ini:

```blade
<div class="user-info">
    <span>Zona Waktu Anda: <strong>@timezone</strong></span>
</div>
```

Output:
```html
<div class="user-info">
    <span>Zona Waktu Anda: <strong>Asia/Jakarta</strong></span>
</div>
```
