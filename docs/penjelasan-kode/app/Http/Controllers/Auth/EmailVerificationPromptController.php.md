# app/Http/Controllers/Auth/EmailVerificationPromptController.php

> **Path:** `app/Http/Controllers/Auth/EmailVerificationPromptController.php`  
> **Jenis:** Controller (Auth)  
> **Dipakai oleh:** Route verification.notice  
> **Memanggil:** `App\Http\Controllers\Controller`

## Ringkasan

Menampilkan prompt verifikasi email atau redirect jika sudah terverifikasi. Single-action controller (`__invoke()`).

## Peran dalam Sistem

Bagian verifikasi email Laravel Breeze.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `__invoke(Request $request): RedirectResponse\|View` | `EmailVerificationPromptController.php:15-20` | Jika terverifikasi redirect dashboard, else view `auth.verify-email` |

## Alur Data

```text
GET /email/verify -> hasVerifiedEmail() ? redirect : view auth.verify-email
```

## Relasi

- Route: verification.notice
- Middleware: `auth`
- View: `auth/verify-email.blade.php`

## Catatan Teknis

- Single-action controller.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa perbedaan dengan EmailVerificationNotificationController?  
**Jawab:** Prompt menampilkan halaman, notification mengirim ulang email. (`EmailVerificationPromptController.php:15-20`)

## File Terkait

- [EmailVerificationNotificationController](EmailVerificationNotificationController.php.md)
- [VerifyEmailController](VerifyEmailController.php.md)
