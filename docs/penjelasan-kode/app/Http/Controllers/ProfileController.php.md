# app/Http/Controllers/ProfileController.php

> **Path:** `app/Http/Controllers/ProfileController.php`  
> **Jenis:** Controller  
> **Dipakai oleh:** Route profile (Breeze), view `profile.edit`, middleware `auth`  
> **Memanggil:** `App\Http\Requests\ProfileUpdateRequest`, `Illuminate\Support\Facades\Auth`, `Illuminate\Support\Facades\Redirect`, model `User` (via `$request->user()`)

## Ringkasan

Mengelola profil user terautentikasi: tampilkan form edit, update profil (name/email), dan hapus akun. Saat email diubah, `email_verified_at` di-null-kan. Penghapusan akun me-logout, menghapus user, invalidate session, regenerate token.

## Peran dalam Sistem

Bagian dari Laravel Breeze untuk manajemen profil. Berada di lapisan HTTP, memanfaatkan Form Request untuk validasi update.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `edit(Request $request): View` | `ProfileController.php:17-22` | Tampilkan form dengan data user saat ini |
| `update(ProfileUpdateRequest $request): RedirectResponse` | `ProfileController.php:27-38` | Validasi via request, isi field, reset verifikasi jika email berubah, simpan, redirect dengan status |
| `destroy(Request $request): RedirectResponse` | `ProfileController.php:43-59` | Validasi password saat ini via `validateWithBag('userDeletion')`, logout, hapus user, invalidate+regenerate token, redirect ke `/` |

## Alur Data

```text
GET /profile -> edit() -> view profile.edit(user)
PATCH/PUT /profile -> ProfileUpdateRequest validasi -> fill() -> jika email berubah set email_verified_at null -> save() -> redirect profile.edit status profile-updated
DELETE /profile -> validasi password current_password -> logout -> delete user -> session invalidate/regenerate -> redirect /
```

## Relasi

- Route: profile routes (Breeze)
- Model/tabel: `App\Models\User` -> `users`
- View: `resources/views/profile/edit.blade.php`
- Request: `App\Http\Requests\ProfileUpdateRequest`
- Test: -

## Catatan Teknis

- **Reset verifikasi**: mengikuti perilaku Breeze - mengubah email membatalkan status verifikasi.
- **Hapus akun**: urutan logout -> delete -> invalidate session -> regenerate token mencegah reuse sesi.
- **Validasi penghapusan**: memakai `validateWithBag('userDeletion')` agar error tampil di bag terpisah di form.
- **Tidak ada proteksi khusus** selain auth (user hanya bisa mengelola akunnya sendiri).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa yang terjadi saat email diubah?  
**Jawab:** `email_verified_at` diset `null` karena user perlu verifikasi ulang. (`ProfileController.php:31-34`)

**Tanya:** Bagaimana keamanan saat menghapus akun?  
**Jawab:** Wajib konfirmasi password saat ini, lalu logout, hapus user, invalidate session, regenerate CSRF token, redirect ke beranda. (`ProfileController.php:45-59`)

## File Terkait

- [ProfileUpdateRequest](../Requests/ProfileUpdateRequest.php.md)
- [AuthenticatedSessionController](Auth/AuthenticatedSessionController.php.md)
- [User Model](../../Models/User.php.md)
