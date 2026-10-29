# resources/views/profile/edit.blade.php

> **Path:** `resources/views/profile/edit.blade.php`  
> **Jenis:** View  
> **Dipakai oleh:** Route `profile.edit` (GET)  
> **Memanggil:** `<x-app-layout>`, `@include` partials profile, route `profile.*`

## Ringkasan

Halaman edit profil pengguna. Menampilkan tiga bagian: update informasi profil, update password, dan hapus akun. Menggunakan layout aplikasi.

## Peran dalam Sistem

Halaman pengaturan profil untuk pengguna terautentikasi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Layout | `resources/views/profile/edit.blade.php:1` | Menggunakan `<x-app-layout>`. |
| Header slot | `resources/views/profile/edit.blade.php:2-6` | Slot header dengan judul "Profile". |
| Container | `resources/views/profile/edit.blade.php:8-28` | Container dengan padding dan max-width, space-y antar card. |
| Card profil | `resources/views/profile/edit.blade.php:10-14` | Card berisi `@include('profile.partials.update-profile-information-form')`. |
| Card password | `resources/views/profile/edit.blade.php:16-20` | Card berisi `@include('profile.partials.update-password-form')`. |
| Card hapus akun | `resources/views/profile/edit.blade.php:22-26` | Card berisi `@include('profile.partials.delete-user-form')`. |

## Alur Data

```text
User mengakses /profile -> ProfileController@edit -> 
render view dengan data user (biasanya tersedia via auth()) -> 
partials menggunakan $user untuk menampilkan data saat ini.
```

- **Data dari controller:** `$user` (umum dikirim Breeze; bisa juga diakses via `auth()->user()`).
- **Layout:** `app.blade.php`.
- **State JS:** Tergantung partials (Alpine untuk modal di delete form, dan Alpine untuk notifikasi sementara).

## Relasi

- **Route:** `GET /profile` (`profile.edit`), `PATCH /profile` (`profile.update`), `DELETE /profile` (`profile.destroy`), `PUT /password` (`password.update`).
- **Controller:** `ProfileController`.
- **Partials:** `update-profile-information-form.blade.php`, `update-password-form.blade.php`, `delete-user-form.blade.php`.
- **Middleware:** `auth`.

## Catatan Teknis

- Struktur card terpisah untuk tiap aksi (UX jelas).
- Menggunakan partials untuk modularitas.
- Header diatur via slot layout.

## Aksesibilitas

- Heading terstruktur, form terpisah per section.

## Edge Case

- User harus login untuk mengakses halaman ini.
- Hapus akun memerlukan konfirmasi password (di modal).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa saja bagian yang ada di halaman profil?  
**Jawab:** Update informasi profil, update password, dan hapus akun.

**Tanya:** Form mana yang memakai modal?  
**Jawab:** Form hapus akun menggunakan `<x-modal>` untuk konfirmasi.

**Tanya:** Route apa yang digunakan untuk update profil?  
**Jawab:** `PATCH` ke `route('profile.update')`.

## File Terkait

- [layouts/app.blade.php](../layouts/app.blade.php.md)
- [profile/partials/update-profile-information-form.blade.php](partials/update-profile-information-form.blade.php.md)
- [profile/partials/update-password-form.blade.php](partials/update-password-form.blade.php.md)
- [profile/partials/delete-user-form.blade.php](partials/delete-user-form.blade.php.md)
- [03-peta-route-dan-halaman.md](../../../03-peta-route-dan-halaman.md)
