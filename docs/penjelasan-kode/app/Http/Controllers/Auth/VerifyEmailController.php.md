# app/Http/Controllers/Auth/VerifyEmailController.php

> **Path:** `app/Http/Controllers/Auth/VerifyEmailController.php`  
> **Jenis:** Controller (Auth)  
> **Dipakai oleh:** Route verification.verify  
> **Memanggil:** `App\Http\Controllers\Controller`, `Illuminate\Auth\Events\Verified`, `Illuminate\Foundation\Auth\EmailVerificationRequest`

## Ringkasan

Menandai email terverifikasi via signed link. Jika sudah verified redirect dengan `?verified=1`, else `markEmailAsVerified()` fire event `Verified`, redirect dengan `?verified=1`.

## Peran dalam Sistem

Bagian verifikasi email Laravel Breeze.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `__invoke(EmailVerificationRequest $request): RedirectResponse` | `15-26` | Verifikasi email, fire event jika baru, redirect |

## Alur Data

```text
GET signed verification link -> EmailVerificationRequest validasi -> jika verified redirect else mark + event -> redirect
```

## Relasi

- Route: verification.verify
- Request: EmailVerificationRequest
- Event: Verified

## Catatan Teknis

- Menggunakan signed URL validation.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan event Verified di-fire?  
**Jawab:** Hanya saat `markEmailAsVerified()` mengembalikan true (pertama kali diverifikasi). (`VerifyEmailController.php:21-23`)

## File Terkait

- [EmailVerificationPromptController](EmailVerificationPromptController.php.md)
- [EmailVerificationNotificationController](EmailVerificationNotificationController.php.md)
