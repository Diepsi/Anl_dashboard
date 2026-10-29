# resources/views/profile/partials/update-password-form.blade.php

> **Path:** `resources/views/profile/partials/update-password-form.blade.php`  
> **Jenis:** View/Partial  
> **Dipakai oleh:** `profile/edit.blade.php`  
> **Memanggil:** `<x-input-label>`, `<x-text-input>`, `<x-input-error>`, `<x-primary-button>`, `<x-auth-session-status>`, route `password.update`

## Ringkasan

Form untuk mengubah password pengguna. Meminta password saat ini, password baru, dan konfirmasi password baru. Menampilkan notifikasi sukses sementara.

## Peran dalam Sistem

Bagian profil untuk pembaruan password dengan validasi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Section | `resources/views/profile/partials/update-password-form.blade.php:1` | Wrapper. |
| Header | `resources/views/profile/partials/update-password-form.blade.php:2-10` | Judul "Update Password" dan penjelasan keamanan. |
| Form | `resources/views/profile/partials/update-password-form.blade.php:12-47` | Form POST ke `route('password.update')`, `@csrf`, `@method('put')`. |
| Current password | `resources/views/profile/partials/update-password-form.blade.php:16-20` | Input password saat ini, error dari bag `updatePassword`. |
| New password | `resources/views/profile/partials/update-password-form.blade.php:22-26` | Input password baru. |
| Confirm password | `resources/views/profile/partials/update-password-form.blade.php:28-32` | Konfirmasi password baru. |
| Tombol & status | `resources/views/profile/partials/update-password-form.blade.php:34-46` | Tombol Save dan pesan "Saved." sementara jika `session('status') === 'password-updated'` (dengan Alpine show/hide + timeout). |

## Alur Data

```text
User isi form -> submit PUT ke password.update -> 
controller validasi password saat ini dan password baru -> 
update password -> redirect dengan session('status')='password-updated' -> 
view menampilkan pesan sukses sementara.
```

- **Form:** `method="post"`, `action="{{ route('password.update') }}"`, `@csrf`, `@method('put')`.
- **Error bag:** `$errors->updatePassword` untuk error per field.
- **Session:** `session('status')` untuk notifikasi sukses.
- **State JS:** Alpine untuk hide otomatis pesan sukses setelah 2 detik.

## Relasi

- **Route:** `PUT /password` (`password.update`).
- **Controller:** PasswordController (Breeze) / update password action.
- **Komponen:** `input-label`, `text-input`, `input-error`, `primary-button`.
- **Partial induk:** `profile/edit.blade.php`.

## Catatan Teknis

- Menggunakan method PUT.
- Error dipisah dalam error bag `updatePassword`.
- Notifikasi sukses dengan transisi Alpine dan auto-hide.
- Autocomplete diset dengan benar (current-password, new-password).

## Aksesibilitas

- Label terhubung, autocomplete sesuai konteks.

## Edge Case

- Password saat ini salah -> error di `current_password`.
- Password baru tidak sesuai konfirmasi -> error di `password_confirmation`.
- Password tidak memenuhi aturan validasi -> error di `password`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Field apa saja yang dibutuhkan?  
**Jawab:** Password saat ini, password baru, konfirmasi password baru.

**Tanya:** Bagaimana notifikasi sukses muncul?  
**Jawab:** Jika session status adalah 'password-updated', tampilkan pesan "Saved." yang hilang otomatis setelah 2 detik via Alpine.

**Tanya:** Error ditampilkan di mana?  
**Jawab:** Menggunakan error bag `updatePassword` via `<x-input-error>`.

## File Terkait

- [profile/edit.blade.php](../edit.blade.php.md)
- [components/input-label.blade.php](../../components/input-label.blade.php.md)
- [components/text-input.blade.php](../../components/text-input.blade.php.md)
- [components/input-error.blade.php](../../components/input-error.blade.php.md)
- [components/primary-button.blade.php](../../components/primary-button.blade.php.md)
- [components/auth-session-status.blade.php](../../components/auth-session-status.blade.php.md)
- [routes/auth.php (peta route)](../../../../03-peta-route-dan-halaman.md)
