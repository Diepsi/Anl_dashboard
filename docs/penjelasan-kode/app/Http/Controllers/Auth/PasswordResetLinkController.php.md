# app/Http/Controllers/Auth/PasswordResetLinkController.php

> **Path:** `app/Http/Controllers/Auth/PasswordResetLinkController.php`  
> **Jenis:** Controller (Auth)  
> **Dipakai oleh:** Route forgot-password  
> **Memanggil:** `App\Http\Controllers\Controller`, `Illuminate\Support\Facades\Password`

## Ringkasan

Menampilkan form permintaan link reset dan mengirim link via `Password::sendResetLink()`. Kembali dengan status atau error.

## Peran dalam Sistem

Bagian reset password Laravel Breeze.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `create(): View` | `17-20` | Form forgot-password |
| `store(Request $request): RedirectResponse` | `27-44` | Validasi email, kirim link, redirect dengan status/error |

## Alur Data

```text
GET/POST forgot-password -> validasi -> sendResetLink() -> back with status atau errors
```

## Relasi

- Route: password.request/email
- Broker: password

## Catatan Teknis

- Pesan menggunakan `__($status)` dari broker.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana respons jika email tidak ditemukan?  
**Jawab:** Broker akan mengembalikan status error dan controller mengarahkan kembali dengan input email dan error. (`PasswordResetLinkController.php:40-43`)

## File Terkait

- [NewPasswordController](NewPasswordController.php.md)
