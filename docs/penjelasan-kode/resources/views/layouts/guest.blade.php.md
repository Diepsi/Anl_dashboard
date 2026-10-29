# resources/views/layouts/guest.blade.php

> **Path:** `resources/views/layouts/guest.blade.php`  
> **Jenis:** View/Layout  
> **Dipakai oleh:** Form auth Breeze standar (`confirm-password`, `forgot-password`, `register`, `reset-password`, `verify-email`)  
> **Memanggil:** `@vite`, `<x-application-logo>`, `$slot`

## Ringkasan

Layout tamu untuk halaman autentikasi. Menampilkan logo aplikasi yang dilingkupi link ke `/`, kartu form terpusat, styling sederhana.

## Peran dalam Sistem

Layout untuk halaman guest/auth (kecuali `login.blade.php` yang menggunakan layout inline sendiri).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| DOCTYPE/HTML | `resources/views/layouts/guest.blade.php:1-17` | HTML5, locale, meta, CSRF, judul config app, font Figtree, `@vite(['resources/css/app.css','resources/js/app.js'])`, body antialiased. |
| Container | `resources/views/layouts/guest.blade.php:18` | Centering vertical/horizontal, bg-gray-100. |
| Logo | `resources/views/layouts/guest.blade.php:19-23` | Link ke `/` berisi `<x-application-logo class="w-20 h-20 fill-current text-gray-500" />`. |
| Card | `resources/views/layouts/guest.blade.php:25-27` | Kartu putih shadow dengan `sm:max-w-md`, padding, rounded, berisi `$slot`. |

## Alur Data

```text
View auth menggunakan <x-guest-layout> -> 
merender guest.blade.php -> 
logo + kartu dengan slot form.
```

- **Slot:** `$slot`.
- **State JS:** Tidak ada khusus.

## Relasi

- **Dipakai oleh:** `auth/confirm-password.blade.php`, `auth/forgot-password.blade.php`, `auth/register.blade.php`, `auth/reset-password.blade.php`, `auth/verify-email.blade.php`.
- **Tidak dipakai oleh:** `auth/login.blade.php` (layout inline kustom).
- **Komponen:** `components/application-logo.blade.php`.

## Catatan Teknis

- Logo link ke home `/`.
- Responsive: padding berubah di small breakpoint.
- Styling konsisten dengan Breeze default.

## Aksesibilitas

- Link logo menuju home.
- Struktur terpusat.

## Edge Case

- Jika logo gagal dimuat, tetap ada area logo.
- Link ke `/` bisa redirect tergantung auth middleware (jika sudah login bisa diarahkan).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan guest layout digunakan?  
**Jawab:** Untuk halaman auth tamu seperti forgot password, register, reset password, dll.

**Tanya:** Mengapa login tidak pakai guest layout?  
**Jawab:** `login.blade.php` menggunakan layout HTML inline kustom dengan branding khusus ANL Dashboard.

**Tanya:** Logo mengarah ke mana?  
**Jawab:** Link `<a href="/">` ke root aplikasi.

## File Terkait

- [layouts/app.blade.php](app.blade.php.md)
- [components/application-logo.blade.php](../components/application-logo.blade.php.md)
- [auth/login.blade.php](../auth/login.blade.php.md)
- [auth/forgot-password.blade.php](../auth/forgot-password.blade.php.md)
- [auth/register.blade.php](../auth/register.blade.php.md)
