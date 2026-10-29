# app/Http/Controllers/Auth/PasswordController.php

> **Path:** `app/Http/Controllers/Auth/PasswordController.php`  
> **Jenis:** Controller (Auth)  
> **Dipakai oleh:** Route update password (profile/settings), middleware `auth`  
> **Memanggil:** `App\Http\Controllers\Controller`, `Illuminate\Support\Facades\Hash`, `Illuminate\Validation\Rules\Password`

## Ringkasan

Update password user terautentikasi. Validasi password saat ini dan password baru terkonfirmasi dengan aturan default, lalu update password ter-hash.

## Peran dalam Sistem

Bagian manajemen password Breeze.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `update(Request $request): RedirectResponse` | `PasswordController.php:16-28` | Validasi dengan bag `updatePassword`, hash password baru, update user, kembali dengan status `password-updated` |

## Alur Data

```text
PUT/PATCH /password -> validateWithBag updatePassword (current_password, password confirmed + defaults) -> Hash::make -> user->update -> back with status
```

## Relasi

- Route: password.update
- Middleware: `auth`
- Model: `App\Models\User`

## Catatan Teknis

- Menggunakan `validateWithBag` agar error terpisah di form.
- Validasi `current_password` menggunakan rule Laravel bawaan.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana verifikasi password lama?  
**Jawab:** Menggunakan rule `current_password` pada field `current_password`. (`PasswordController.php:19`)

## File Terkait

- [ProfileController](../ProfileController.php.md)
- [AuthenticatedSessionController](AuthenticatedSessionController.php.md)
