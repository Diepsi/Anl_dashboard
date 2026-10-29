# resources/views/profile/partials/update-profile-information-form.blade.php

> **Path:** `resources/views/profile/partials/update-profile-information-form.blade.php`  
> **Jenis:** View/Partial  
> **Dipakai oleh:** `profile/edit.blade.php`  
> **Memanggil:** `<x-input-label>`, `<x-text-input>`, `<x-input-error>`, `<x-primary-button>`, route `verification.send`, `profile.update`

## Ringkasan

Form untuk memperbarui informasi profil (nama dan email). Menangani kasus email belum terverifikasi dengan opsi kirim ulang verifikasi email.

## Peran dalam Sistem

Bagian profil untuk update data dasar pengguna.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Section | `resources/views/profile/partials/update-profile-information-form.blade.php:1` | Wrapper. |
| Header | `resources/views/profile/partials/update-profile-information-form.blade.php:2-10` | Judul dan penjelasan. |
| Hidden form verifikasi | `resources/views/profile/partials/update-profile-information-form.blade.php:12-14` | Form tersembunyi POST ke `route('verification.send')` untuk kirim ulang verifikasi. |
| Form update | `resources/views/profile/partials/update-profile-information-form.blade.php:16-63` | Form POST ke `route('profile.update')`, `@csrf`, `@method('patch')`. |
| Nama | `resources/views/profile/partials/update-profile-information-form.blade.php:20-24` | Input nama dengan nilai `old('name', $user->name')`. |
| Email | `resources/views/profile/partials/update-profile-information-form.blade.php:26-48` | Input email, jika user harus verifikasi dan belum terverifikasi tampilkan pesan dan tombol kirim ulang (menggunakan form tersembunyi). |
| Tombol & status | `resources/views/profile/partials/update-profile-information-form.blade.php:50-62` | Tombol Save dan pesan "Saved." sementara jika `session('status') === 'profile-updated'`. |

## Alur Data

```text
User ubah nama/email -> submit PATCH ke profile.update -> 
controller update user -> redirect dengan status profile-updated -> 
jika email berubah dan perlu verifikasi, user mungkin perlu verifikasi ulang.
```

- **Data dari controller:** `$user` (Model User).
- **Form utama:** `method="post"`, `action="{{ route('profile.update') }}"`, `@csrf`, `@method('patch')`.
- **Form verifikasi:** terpisah (hidden) dengan id `send-verification` untuk bisa di-trigger oleh button.
- **Session:** `session('status')` untuk notifikasi sukses dan status kirim verifikasi.
- **Verifikasi:** Mengecek `MustVerifyEmail` dan `hasVerifiedEmail()`.

## Relasi

- **Route:** `PATCH /profile` (`profile.update`), `POST /email/verification-notification` (`verification.send`).
- **Controller:** `ProfileController@update`.
- **Model:** `User` (implements `MustVerifyEmail` jika email verification diaktifkan).
- **Partial induk:** `profile/edit.blade.php`.

## Catatan Teknis

- Menggunakan `old('name', $user->name)` untuk mempertahankan nilai.
- **Dual form pattern:** Tombol kirim ulang verifikasi menggunakan `form="send-verification"` untuk mensubmit form tersembunyi.
- **Conditional UI:** Hanya tampilkan peringatan verifikasi jika user implement `MustVerifyEmail` dan email belum terverifikasi.
- **Notifikasi:** Pesan sukses dan pesan link verifikasi terkirim ditampilkan.

## Aksesibilitas

- Label terhubung, form terpisah dengan jelas.

## Edge Case

- Email diubah menjadi email baru yang belum terverifikasi -> perlu verifikasi ulang.
- Rate limiting pada kirim ulang verifikasi.
- User bisa kirim ulang berulang kali.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana cara kirim ulang verifikasi email?  
**Jawab:** Tombol "Click here to re-send the verification email" mensubmit form tersembunyi `send-verification` yang POST ke `route('verification.send')`.

**Tanya:** Kapan peringatan verifikasi muncul?  
**Jawab:** Jika `$user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail()`.

**Tanya:** Method HTTP untuk update profil?  
**Jawab:** PATCH.

## File Terkait

- [profile/edit.blade.php](../edit.blade.php.md)
- [components/input-label.blade.php](../../components/input-label.blade.php.md)
- [components/text-input.blade.php](../../components/text-input.blade.php.md)
- [components/input-error.blade.php](../../components/input-error.blade.php.md)
- [components/primary-button.blade.php](../../components/primary-button.blade.php.md)
- [auth/verify-email.blade.php](../../auth/verify-email.blade.php.md)
- [03-peta-route-dan-halaman.md](../../../../03-peta-route-dan-halaman.md)
