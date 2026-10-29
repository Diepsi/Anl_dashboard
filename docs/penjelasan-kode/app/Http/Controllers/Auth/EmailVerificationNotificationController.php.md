# app/Http/Controllers/Auth/EmailVerificationNotificationController.php

> **Path:** `app/Http/Controllers/Auth/EmailVerificationNotificationController.php`  
> **Jenis:** Controller (Auth)  
> **Dipakai oleh:** Route email verification notification  
> **Memanggil:** `App\Http\Controllers\Controller`

## Ringkasan

Mengirim ulang notifikasi verifikasi email. Jika sudah terverifikasi, redirect ke dashboard. Jika belum, kirim notifikasi via `sendEmailVerificationNotification()` dan kembali dengan status `verification-link-sent`.

## Peran dalam Sistem

Bagian verifikasi email Laravel Breeze.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `store(Request $request): RedirectResponse` | `EmailVerificationNotificationController.php:14-23` | Cek `hasVerifiedEmail()`, jika ya redirect dashboard; jika tidak kirim notifikasi, kembali dengan status `verification-link-sent` |

## Alur Data

```text
POST /email/verification-notification -> jika verified redirect dashboard -> else sendEmailVerificationNotification() -> back() with status
```

## Relasi

- Route: verification.send
- Middleware: `auth`, `throttle`
- Model: `App\Models\User`

## Catatan Teknis

- Mencegah pengiriman ulang jika sudah terverifikasi.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa yang terjadi jika sudah terverifikasi?  
**Jawab:** Langsung redirect ke dashboard intended. (`EmailVerificationNotificationController.php:16-18`)

## File Terkait

- [EmailVerificationPromptController](EmailVerificationPromptController.php.md)
- [VerifyEmailController](VerifyEmailController.php.md)
