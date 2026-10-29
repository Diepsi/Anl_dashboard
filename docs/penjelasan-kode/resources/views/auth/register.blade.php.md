# resources/views/auth/register.blade.php

> **Path:** `resources/views/auth/register.blade.php`  
> **Jenis:** View  
> **Dipakai oleh:** Route `register` (GET/POST) — tersedia via Breeze, namun tidak digunakan di alur utama aplikasi  
> **Memanggil:** Komponen `<x-guest-layout>`, `<x-input-label>`, `<x-text-input>`, `<x-input-error>`, `<x-primary-button>`, route `register`, `login`

## Ringkasan

Form registrasi standar Laravel Breeze (nama, email, password, konfirmasi password). File ini ada di codebase, tetapi sesuai dokumentasi aplikasi registrasi publik tidak dipakai di navigasi utama dan akun operasional dibuat oleh admin melalui `/users`.

## Peran dalam Sistem

Lapisan presentasi untuk registrasi pengguna. Secara fungsional tersedia, namun tidak menjadi alur pembuatan akun di aplikasi ini.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Layout tamu | `resources/views/auth/register.blade.php:1` | Menggunakan `<x-guest-layout>`. |
| Form registrasi | `resources/views/auth/register.blade.php:2-51` | POST ke `route('register')`, `@csrf`. |
| Name | `resources/views/auth/register.blade.php:6-10` | Input nama wajib, `autofocus`, `old('name')`. |
| Email | `resources/views/auth/register.blade.php:13-17` | Input email wajib, `old('email')`. |
| Password | `resources/views/auth/register.blade.php:20-29` | Password wajib, `autocomplete="new-password"`. |
| Confirm password | `resources/views/auth/register.blade.php:32-40` | Konfirmasi password wajib, `autocomplete="new-password"`. |
| Link ke login | `resources/views/auth/register.blade.php:43-45` | Link `route('login')` bertuliskan "Already registered?". |
| Tombol register | `resources/views/auth/register.blade.php:47-49` | `<x-primary-button>` berlabel `Register`. |

## Alur Data

```text
Jika route register diakses (guest) -> 
render view -> 
submit form -> 
POST register -> 
controller validasi dan buat user -> 
login/redirect sesuai Breeze.
```

- **Data dari controller:** `old('name')`, `old('email')`, `$errors`.
- **Form:** `method="POST"`, `action="{{ route('register') }}"`, `@csrf`.
- **State JS:** Tidak ada.

## Relasi

- **Route:** `GET/POST /register` (Breeze) — mungkin aktif di `routes/auth.php` tergantung konfigurasi.
- **Controller:** RegisteredUserController (Breeze).
- **View:** `layouts/guest.blade.php`.
- **Middleware:** `guest`.
- **Aplikasi:** Sesuai `03-peta-route-dan-halaman.md` baris 56-57: "registrasi publik tidak dipakai di navigasi utama; akun operasional dibuat oleh admin melalui `/users`".

## Catatan Teknis

- Menggunakan komponen form Breeze standar.
- Validasi error per field.
- Password dan konfirmasi password terpisah.
- Link kembali ke login disediakan.

## Aksesibilitas

- Label terhubung, autocomplete sesuai konteks (new-password).

## Edge Case

- Route bisa dinonaktifkan di aplikasi ini (tidak terlihat di navigasi). Perlu dicek `routes/auth.php` jika ingin memastikan apakah benar-benar tidak digunakan.
- Jika tetap aktif, siapa pun bisa mendaftar kecuali dibatasi.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah registrasi publik digunakan di ANL Dashboard?  
**Jawab:** Tidak. Sesuai dokumentasi, akun operasional dibuat oleh admin melalui manajemen user (`/users`); navigasi utama tidak menampilkan link register.

**Tanya:** Form ini mengirim ke route mana?  
**Jawab:** `POST` ke `route('register')`.

**Tanya:** Field apa saja yang diminta?  
**Jawab:** Nama, email, password, konfirmasi password.

## File Terkait

- [layouts/guest.blade.php](../layouts/guest.blade.php.md)
- [components/input-label.blade.php](../components/input-label.blade.php.md)
- [components/text-input.blade.php](../components/text-input.blade.php.md)
- [components/input-error.blade.php](../components/input-error.blade.php.md)
- [components/primary-button.blade.php](../components/primary-button.blade.php.md)
- [users/index.blade.php](../users/index.blade.php.md)
- [users/form.blade.php](../users/form.blade.php.md)
- [routes/auth.php (peta route)](../../../03-peta-route-dan-halaman.md)
