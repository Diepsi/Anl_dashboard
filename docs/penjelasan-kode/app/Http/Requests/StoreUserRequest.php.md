# app/Http/Requests/StoreUserRequest.php

> **Path:** `app/Http/Requests/StoreUserRequest.php`  
> **Jenis:** Form Request  
> **Dipakai oleh:** `UserController@store`  
> **Memanggil:** `Illuminate\Foundation\Http\FormRequest`

## Ringkasan

Validasi pembuatan user baru dengan otorisasi admin. Aturan: name, email unique lowercase, role in admin|user, password min 8 confirmed, is_active boolean opsional.

## Peran dalam Sistem

Validasi + otorisasi untuk manajemen user.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `authorize(): bool` | `StoreUserRequest.php:13-16` | Hanya admin yang diizinkan (`$this->user()?->isAdmin() ?? false`) |
| `rules(): array` | `StoreUserRequest.php:22-31` | Aturan validasi pembuatan user |

## Alur Data

```text
POST /users -> StoreUserRequest authorize() (admin check) + rules -> UserController store()
```

## Relasi

- Controller: `UserController`
- Model: `User`
- Middleware: `EnsureUserIsAdmin` (tambahan proteksi)

## Catatan Teknis

- **Otorisasi**: dilakukan di Form Request (return false -> 403). Ada juga middleware `EnsureUserIsAdmin`.
- **Role**: dibatasi ke `admin` atau `user`.
- **Password**: wajib, min 8, dikonfirmasi.
- **Email**: unique di tabel `users`, lowercase.
- **is_active**: `sometimes|boolean` (bisa tidak dikirim, model bisa pakai default).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Siapa yang bisa membuat user?  
**Jawab:** Hanya user dengan role admin (dicek di `authorize()`). (`StoreUserRequest.php:13-16`)

**Tanya:** Bagaimana validasi password?  
**Jawab:** Wajib, minimal 8 karakter, harus dikonfirmasi (`confirmed`). (`StoreUserRequest.php:28`)

## File Terkait

- [UserController](../Controllers/UserController.php.md)
- [UpdateUserRequest](UpdateUserRequest.php.md)
- [EnsureUserIsAdmin](../Middleware/EnsureUserIsAdmin.php.md)
- [User Model](../../Models/User.php.md)
