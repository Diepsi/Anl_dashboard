# app/Http/Controllers/Auth/NewPasswordController.php

> **Path:** `app/Http/Controllers/Auth/NewPasswordController.php`  
> **Jenis:** Controller (Auth)  
> **Dipakai oleh:** Route password.reset  
> **Memanggil:** `App\Http\Controllers\Controller`, `App\Models\User`, `Illuminate\Support\Facades\Hash`, `Illuminate\Support\Facades\Password`, `Illuminate\Support\Str`, `Illuminate\Validation\Rules`

## Ringkasan

Menampilkan form reset password dengan token dan menangani reset via `Password::reset()`, update password ter-hash, regenerate remember token, fire event `PasswordReset`.

## Peran dalam Sistem

Bagian reset password Laravel Breeze.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `create(Request $request): View` | `22-25` | Tampilkan form |
| `store(Request $request): RedirectResponse` | `32-62` | Validasi, reset broker, update user di callback, redirect |

## Alur Data

```text
GET/POST reset password -> validasi -> Password::reset() -> callback hash+token+event -> redirect login atau back dengan error
```

## Relasi

- Route: password.reset/password.update
- Broker: password
- Model: `User`

## Catatan Teknis

- Menggunakan `forceFill()` dan `Hash::make()`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa yang terjadi setelah reset sukses?  
**Jawab:** Redirect ke route login dengan status dari broker. (`NewPasswordController.php:58-61`)

## File Terkait

- [PasswordResetLinkController](PasswordResetLinkController.php.md)
