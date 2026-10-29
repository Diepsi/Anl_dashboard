# routes/auth.php

> **Path:** `routes/auth.php`  
> **Jenis:** Route  
> **Dipakai oleh:** Aplikasi web (autentikasi)  
> **Memanggil:** Controller Auth Breeze (`AuthenticatedSessionController`, `PasswordResetLinkController`, `NewPasswordController`, `EmailVerificationPromptController`, `VerifyEmailController`, `EmailVerificationNotificationController`, `ConfirmablePasswordController`, `PasswordController`)

## Ringkasan

Route autentikasi Laravel Breeze: login/logout, reset password, verifikasi email, konfirmasi password, update password. Registrasi publik tidak didaftarkan di file ini.

## Peran dalam Sistem

Lapisan routing HTTP untuk autentikasi. Memisahkan route guest dan auth.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Guest group | `routes/auth.php:14-31` | Route untuk belum login. |
| Login | `:15-18` | `GET login` (create), `POST login` (store). |
| Forgot/reset password | `:20-30` | Request link, form reset dengan token, simpan password baru. |
| Auth group | `:33-53` | Route untuk user login. |
| Email verification | `:34-43` | Notice, verify (signed + throttle 6,1), kirim ulang notifikasi (throttle 6,1). |
| Confirm password | `:45-48` | Konfirmasi password untuk aksi sensitif. |
| Update password | `:50` | `PUT /password`. |
| Logout | `:52-53` | `POST /logout`. |

## Alur Data

```text
Guest: GET /login -> form, POST /login -> proses
Auth: verifikasi email, konfirmasi password, update password, logout
```

## Relasi

- Route: -
- Model/tabel: `users`, `password_reset_tokens`, `sessions`
- View: auth/*
- Service: -
- Test: -

## Catatan Teknis

- Tidak ada route registrasi publik (akun dibuat oleh admin via `/users`).
- Verifikasi email menggunakan middleware `signed` dan `throttle`.
- Rate limiting diterapkan pada verifikasi email.
- Logout via POST.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah ada registrasi publik?  
**Jawab:** Tidak. Sesuai catatan di 03-peta-route-dan-halaman, akun operasional dibuat oleh admin melalui route `/users`.

**Tanya:** Mengapa ada throttle pada verifikasi?  
**Jawab:** Untuk mencegah abuse pengiriman ulang email verifikasi.

## File Terkait

- [03-peta-route-dan-halaman.md](../03-peta-route-dan-halaman.md)
- [routes/web.php](web.php.md)
- [database/migrations/0001_01_01_000000_create_users_table.php.md](../database/migrations/0001_01_01_000000_create_users_table.php.md)
- [00-ringkasan-arsitektur.md](../00-ringkasan-arsitektur.md)
