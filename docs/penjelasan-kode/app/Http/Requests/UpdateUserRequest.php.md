# app/Http/Requests/UpdateUserRequest.php

> **Path:** `app/Http/Requests/UpdateUserRequest.php`  
> **Jenis:** Form Request  
> **Dipakai oleh:** `UserController@update`  
> **Memanggil:** `Illuminate\Validation\Rule`

## Ringkasan

Validasi update user dengan otorisasi admin. Email unique kecuali user target, password nullable, role dibatasi.

## Peran dalam Sistem

Validasi + otorisasi update pengguna.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `authorize(): bool` | `13-16` | Hanya admin |
| `rules(): array` | `23-34` | Aturan update (unique ignore, password nullable) |

## Alur Data

```text
PUT/PATCH /users/{user} -> authorize + rules -> controller
```

## Relasi

- Controller: UserController
- Model: User

## Catatan Teknis

- `email` unique ignore `$this->route('user')->id`
- `password` nullable|min:8|confirmed
- Proteksi admin terakhir dicek di controller

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan password diupdate?  
**Jawab:** Hanya jika diisi (nullable di validasi, controller cek `filled()`). (`UpdateUserRequest.php:31`, `UserController.php:68-70`)

## File Terkait

- [UserController](../Controllers/UserController.php.md)
- [StoreUserRequest](StoreUserRequest.php.md)
- [EnsureUserIsAdmin](../Middleware/EnsureUserIsAdmin.php.md)
