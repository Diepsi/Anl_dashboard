# resources/views/auth/login.blade.php

> **Path:** `resources/views/auth/login.blade.php`  
> **Jenis:** View  
> **Dipakai oleh:** Route `login` (GET/POST)  
> **Memanggil:** `@vite`, komponen `<x-auth-session-status>`, `<x-input-label>`, `<x-text-input>`, `<x-input-error>`, `<x-primary-button>`, route `login`

## Ringkasan

Halaman login kustom untuk ANL Dashboard (berbeda dari template guest standar). Menampilkan branding, form login email/password, opsi "Remember me", dan catatan bahwa akun dibuat oleh admin/seeder.

## Peran dalam Sistem

Lapisan presentasi autentikasi utama. Tidak menggunakan `<x-guest-layout>` — merender layout HTML sendiri (full HTML dengan head/body, `@vite`, styling Tailwind).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| DOCTYPE/HTML | `resources/views/auth/login.blade.php:1-16` | HTML5, locale, meta tags, CSRF, judul "Login — ANL Dashboard", font Figtree, `@vite(['resources/css/app.css','resources/js/app.js'])`, body slate-100. |
| Container | `resources/views/auth/login.blade.php:17-18` | Centering dengan `min-h-screen flex items-center justify-center`. |
| Branding | `resources/views/auth/login.blade.php:20-28` | Logo ikon bangunan, judul "ANL Dashboard", subjudul "Operasional Logistik & Instalasi Sekolah". |
| Card form | `resources/views/auth/login.blade.php:30` | Card putih dengan border/slate shadow. |
| Session status | `resources/views/auth/login.blade.php:32` | `<x-auth-session-status :status="session('status')" />`. |
| Form login | `resources/views/auth/login.blade.php:34-69` | POST ke `route('login')`, `@csrf`. |
| Email | `resources/views/auth/login.blade.php:38-42` | Input email `autofocus`, `autocomplete="username"`, `old('email')`, placeholder `admin@anl.com`, error `errors->get('email')`. |
| Password | `resources/views/auth/login.blade.php:45-54` | Input password `required`, `autocomplete="current-password"`, placeholder `password`, error `errors->get('password')`. |
| Remember me | `resources/views/auth/login.blade.php:57-62` | Checkbox `remember`. |
| Tombol login | `resources/views/auth/login.blade.php:64-68` | `<x-primary-button>` lebar penuh, label `Login`. |
| Catatan | `resources/views/auth/login.blade.php:72-74` | Menyatakan akun dibuat melalui seeder atau manajemen user oleh administrator. |

## Alur Data

```text
Guest mengakses /login -> 
view kustom dirender (tanpa guest-layout) -> 
submit form email/password/remember -> 
POST login -> 
Auth controller validasi (termasuk is_active) -> 
jika berhasil redirect ke intended/dashboard -> 
jika gagal kembali dengan error dan old('email').
```

- **Data dari controller:** `old('email')`, `$errors` bag default, `session('status')`.
- **Form:** `method="POST"`, `action="{{ route('login') }}"`, `@csrf`.
- **State JS:** Tidak ada Alpine kustom; bergantung pada JS yang dimuat via `@vite`.

## Relasi

- **Route:** `GET /login` (`login`), `POST /login` — lihat `routes/auth.php`.
- **Controller:** AuthenticatedSessionController (Breeze).
- **View/Layout:** Tidak menggunakan `layouts/guest.blade.php`; layout inline.
- **Middleware:** `guest` untuk akses form.
- **Catatan aplikasi:** Sesuai `03-peta-route-dan-halaman.md`, registrasi publik tidak dipakai di navigasi utama (akun dibuat admin/seeder).

## Catatan Teknis

- **Kustomisasi tampilan:** Berbeda dari Breeze default; menyesuaikan branding ANL Dashboard.
- **CSRF:** Ada meta tag `csrf-token` dan `@csrf` pada form.
- **Vite:** Memuat CSS/JS aplikasi.
- **Pesan error:** Menggunakan komponen `<x-input-error>` per field.
- **Remember me:** Mendukung checkbox remember.

## Aksesibilitas

- Label terhubung ke input.
- `autofocus` pada email.
- Tombol submit dengan teks jelas.

## Edge Case

- Akun nonaktif ditolak saat login (sesuai catatan arsitektur: "Akun nonaktif ditolak saat login").
- Kredensial salah -> error pada email/password sesuai validasi Breeze.
- Sudah login -> middleware guest akan mencegah akses form.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa login.blade.php tidak menggunakan `<x-guest-layout>`?  
**Jawab:** Merupakan tampilan kustom dengan branding dan styling sendiri (layout inline HTML) sesuai kebutuhan aplikasi.

**Tanya:** Di mana pesan status sesi ditampilkan?  
**Jawab:** Di card form via `<x-auth-session-status>`.

**Tanya:** Siapa yang bisa membuat akun untuk login?  
**Jawab:** Melalui seeder atau manajemen user oleh administrator (`/users`), karena registrasi publik tidak dipakai di navigasi utama.

## File Terkait

- [components/auth-session-status.blade.php](../components/auth-session-status.blade.php.md)
- [components/input-label.blade.php](../components/input-label.blade.php.md)
- [components/text-input.blade.php](../components/text-input.blade.php.md)
- [components/input-error.blade.php](../components/input-error.blade.php.md)
- [components/primary-button.blade.php](../components/primary-button.blade.php.md)
- [routes/auth.php (peta route)](../../../03-peta-route-dan-halaman.md)
- [03-peta-route-dan-halaman.md](../../../03-peta-route-dan-halaman.md)
