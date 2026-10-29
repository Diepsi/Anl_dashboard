# resources/views/components/auth-session-status.blade.php

> **Path:** `resources/views/components/auth-session-status.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** `auth/confirm-password.blade.php`, `auth/forgot-password.blade.php`, `auth/login.blade.php`, `profile/partials/update-password-form.blade.php`, `profile/partials/update-profile-information-form.blade.php`  
> **Memanggil:** Tidak ada

## Ringkasan

Komponen untuk menampilkan status sesi (flash message) dengan styling hijau. Hanya muncul jika `$status` tidak kosong.

## Peran dalam Sistem

Komponen utilitas presentasi untuk pesan sukses/status dari session.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Props | `resources/views/components/auth-session-status.blade.php:1` | `@props(['status'])` menerima prop status. |
| Kondisional | `resources/views/components/auth-session-status.blade.php:3-7` | Jika `$status` ada, render div dengan class hijau dan merge atribut via `$attributes->merge()`. |
| Isi | `resources/views/components/auth-session-status.blade.php:5` | Menampilkan `$status`. |

## Alur Data

```text
Controller set session('status', '...') -> 
view memanggil <x-auth-session-status :status="session('status')" /> -> 
jika ada, komponen merender pesan.
```

- **Props:** `status` (wajib saat dipanggil).
- **State JS:** Tidak ada.

## Relasi

- **Dipakai oleh:** Berbagai form auth dan profile (status sukses seperti link terkirim, password diperbarui, profil diperbarui).
- **Terkait:** Session flash Laravel.

## Catatan Teknis

- Menggunakan `$attributes->merge()` sehingga pemanggil bisa menambah class tambahan (mis. `class="mb-4"`).
- Styling: `font-medium text-sm text-green-600`.
- Hanya render jika ada status (mencegah div kosong).

## Aksesibilitas

- Pesan status dibaca sebagai teks.

## Edge Case

- Status kosong/null -> tidak dirender sama sekali.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan komponen ini digunakan?  
**Jawab:** Untuk menampilkan pesan sukses dari session, seperti "verification link sent", "password updated", dll.

**Tanya:** Bagaimana cara menambah margin?  
**Jawab:** Lewat atribut, contoh `:status="session('status')" class="mb-4"` karena `$attributes->merge()` digunakan.

**Tanya:** Apa yang terjadi jika status kosong?  
**Jawab:** Komponen tidak merender apa pun.

## File Terkait

- [auth/forgot-password.blade.php](../auth/forgot-password.blade.php.md)
- [auth/login.blade.php](../auth/login.blade.php.md)
- [profile/partials/update-password-form.blade.php](../profile/partials/update-password-form.blade.php.md)
- [profile/partials/update-profile-information-form.blade.php](../profile/partials/update-profile-information-form.blade.php.md)
- [auth/verify-email.blade.php](../auth/verify-email.blade.php.md)
