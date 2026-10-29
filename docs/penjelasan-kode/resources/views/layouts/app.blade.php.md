# resources/views/layouts/app.blade.php

> **Path:** `resources/views/layouts/app.blade.php`  
> **Jenis:** View/Layout  
> **Dipakai oleh:** Hampir semua halaman yang butuh autentikasi (dashboard, shipments, clustering, users, profile)  
> **Memanggil:** `@vite`, `@include('layouts.navigation')`, `$slot`, `@stack('scripts')`

## Ringkasan

Layout utama untuk halaman yang memerlukan autentikasi. Menggunakan struktur dengan sidebar navigation, area konten utama dengan padding/offset untuk sidebar, memuat assets via Vite, dan menyediakan stack untuk script tambahan.

## Peran dalam Sistem

Layout aplikasi (authenticated layout). Merupakan kerangka halaman operasional ANL Dashboard.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| DOCTYPE/HTML | `resources/views/layouts/app.blade.php:1-17` | HTML5, locale, meta tags, CSRF, judul dinamis `$title ?? config('app.name','ANL Dashboard')`, font Figtree, `@vite(['resources/css/app.css','resources/js/app.js'])`, body bg-gray-100. |
| Wrapper | `resources/views/layouts/app.blade.php:18` | `<div class="min-h-screen">`. |
| Navigation | `resources/views/layouts/app.blade.php:19` | `@include('layouts.navigation')` menampilkan sidebar + mobile bar. |
| Main content | `resources/views/layouts/app.blade.php:22-24` | `<main class="pt-16 md:pt-0 md:pl-64">` — offset untuk mobile top bar (pt-16) dan sidebar desktop (md:pl-64), merender `$slot`. |
| Scripts stack | `resources/views/layouts/app.blade.php:27` | `@stack('scripts')` untuk menambahkan script dari view. |

## Alur Data

```text
View menggunakan <x-app-layout> (component layout) -> 
Blade merender app.blade.php -> 
navigation di-include -> 
konten dari $slot ditampilkan di main dengan offset sidebar -> 
assets dari Vite dimuat.
```

- **Slot:** `$slot` (anonymous component slot).
- **Variabel:** `$title` (opsional).
- **State JS:** Tergantung pada `app.js` (Alpine) dan script di stack.
- **Layout approach:** Menggunakan anonymous component `<x-app-layout>` (konvensi Breeze), file ini adalah definisi layout.

## Relasi

- **Dipakai oleh:** `dashboard.blade.php` (tidak dibaca tapi dipakai), `shipments.blade.php`, `clustering.blade.php`, `users/index.blade.php`, `users/form.blade.php`, `profile/edit.blade.php`, dan lainnya.
- **Include:** `layouts/navigation.blade.php`.
- **Terkait:** `layouts/guest.blade.php` (untuk halaman auth tamu).

## Catatan Teknis

- **Responsive layout:** Mobile top bar + sidebar overlay di navigation, desktop sidebar tetap, konten bergeser `md:pl-64`.
- **Vite:** Memuat CSS dan JS.
- **CSRF token:** Tersedia di meta tag.
- **Judul dinamis:** Mendukung override via `$title`.
- **Script stack:** Fleksibel untuk menambahkan Chart.js, Alpine logic, dll dari halaman tertentu.

## Aksesibilitas

- Struktur semantik (html, body, nav, main).
- Locale diatur.

## Edge Case

- Jika navigation tidak di-include, layout tetap berfungsi tapi tanpa menu.
- Padding top untuk menghindari konten tertutup mobile bar.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana layout ini menangani responsive?  
**Jawab:** Ada mobile top bar dan sidebar overlay (di navigation), konten utama punya `pt-16` untuk mobile bar dan `md:pl-64` untuk offset sidebar desktop.

**Tanya:** Apa perbedaan dengan guest layout?  
**Jawab:** `app.blade.php` untuk halaman terautentikasi dengan navigation/sidebar, `guest.blade.php` untuk halaman auth tanpa sidebar.

**Tanya:** Bagaimana menambahkan script khusus halaman?  
**Jawab:** Menggunakan `@push('scripts') ... @endpush` karena ada `@stack('scripts')`.

## File Terkait

- [layouts/navigation.blade.php](navigation.blade.php.md)
- [layouts/guest.blade.php](guest.blade.php.md)
- [components/application-logo.blade.php](../components/application-logo.blade.php.md)
- [resources/js/app.js](../../js/app.js.md)
- [resources/css/app.css](../../css/app.css.md)
