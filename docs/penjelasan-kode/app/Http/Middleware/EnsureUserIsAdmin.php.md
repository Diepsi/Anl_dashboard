# app/Http/Middleware/EnsureUserIsAdmin.php

> **Path:** `app/Http/Middleware/EnsureUserIsAdmin.php`  
> **Jenis:** Middleware  
> **Dipakai oleh:** Route grup admin (manajemen user, sync manual), route yang dilindungi admin  
> **Memanggil:** Tidak ada model/service khusus (hanya `$request->user()`)

## Ringkasan

Middleware yang memastikan user terautentikasi dan memiliki role admin. Jika bukan admin, mengembalikan abort 403 dengan pesan Indonesia.

## Peran dalam Sistem

Lapisan otorisasi untuk route admin. Melengkapi pengecekan di Form Request `StoreUserRequest`/`UpdateUserRequest`.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `handle(Request $request, Closure $next): Response` | `EnsureUserIsAdmin.php:14-21` | Cek `$request->user()?->isAdmin()` jika false abort 403 dengan pesan custom, else lanjut ke next |

## Alur Data

```text
Request -> auth middleware -> EnsureUserIsAdmin -> jika bukan admin abort 403 else lanjut ke controller
```

## Relasi

- Route: grup admin (users resource, sync manual)
- Model: `App\Models\User` (method `isAdmin()`)
- Controller: `UserController`, route sync

## Catatan Teknis

- Menggunakan null-safe operator (`?->`) untuk mencegah error jika user belum terautentikasi (meski biasanya dipasang setelah `auth`).
- Pesan error dalam Bahasa Indonesia: "Akses ditolak. Hanya admin yang dapat mengelola user."
- Proteksi berlapis dengan Form Request di UserController.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan middleware ini dijalankan?  
**Jawab:** Pada route yang membutuhkan akses admin (manajemen user dan tombol sync manual). Biasanya setelah middleware `auth`. (`EnsureUserIsAdmin.php:16-19`)

**Tanya:** Apa yang terjadi jika bukan admin?  
**Jawab:** `abort(403, 'Akses ditolak. Hanya admin yang dapat mengelola user.')`. (`EnsureUserIsAdmin.php:17`)

**Tanya:** Apakah Form Request juga mengecek admin?  
**Jawab:** Ya, `StoreUserRequest` dan `UpdateUserRequest` memiliki `authorize()` yang memanggil `$this->user()->isAdmin()` juga mengembalikan 403 jika false. Ini proteksi berlapis. (`StoreUserRequest.php:13-16`, `UpdateUserRequest.php:13-16`)

## File Terkait

- [UserController](../Controllers/UserController.php.md)
- [StoreUserRequest](../Requests/StoreUserRequest.php.md)
- [UpdateUserRequest](../Requests/UpdateUserRequest.php.md)
- [AuthenticatedSessionController](../Controllers/Auth/AuthenticatedSessionController.php.md)
- [User Model](../../Models/User.php.md)
