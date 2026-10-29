# Feature/ProfileTest

> **Path:** `tests/Feature/ProfileTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Manajemen profil user (Breeze)
> **Memanggil:** `App\Models\User`, route profile/password

## Ringkasan

Menguji halaman profil, update informasi profil, perubahan email yang sama tidak mereset verifikasi email, hapus akun berhasil, dan hapus akun gagal dengan password salah.

## Peran dalam Sistem

Memastikan user bisa mengelola profil sendiri dengan aman.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `test_profile_page_is_displayed()` | `tests/Feature/ProfileTest.php:13-22` | actingAs user GET `/profile` -> OK |
| `test_profile_information_can_be_updated()` | `tests/Feature/ProfileTest.php:24-44` | PATCH `/profile` update name/email -> no errors, redirect, data terupdate, `email_verified_at` menjadi null (email berubah) |
| `test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()` | `tests/Feature/ProfileTest.php:46-62` | PATCH dengan email sama -> `email_verified_at` tetap ada |
| `test_user_can_delete_their_account()` | `tests/Feature/ProfileTest.php:64-80` | DELETE `/profile` dengan password benar -> no errors, redirect `/`, guest, user null |
| `test_correct_password_must_be_provided_to_delete_account()` | `tests/Feature/ProfileTest.php:82-98` | DELETE dengan password salah -> error di bag `userDeletion`, redirect, user tetap ada |

## Alur Arrange-Act-Assert

**Tampilkan profil:** actingAs -> GET `/profile` -> assertOk

**Update profil:** actingAs -> PATCH -> redirect, refresh user, assert name/email berubah, email_verified_at null

**Email sama:** PATCH email sama -> email_verified_at tidak null

**Hapus akun sukses:** actingAs -> DELETE dengan password -> redirect `/`, assertGuest(), user fresh null

**Hapus akun gagal:** actingAs -> DELETE password salah -> errors di `userDeletion`, user masih ada

## Relasi

- Route: `GET /profile`, `PATCH /profile`, `DELETE /profile`
- Model/tabel: `users`
- Test: Profile/Auth

## Catatan Teknis

- Menggunakan `RefreshDatabase`.
- Ketika email diubah, Laravel biasanya mereset verifikasi email (menjadi null) - sesuai test.
- Penghapusan akun memerlukan konfirmasi password.
- Error bag untuk penghapusan adalah `userDeletion`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kenapa email_verified_at jadi null saat email berubah?  
**Jawab:** Standar keamanan - email baru perlu diverifikasi kembali.

**Tanya:** Apakah user bisa menghapus akunnya sendiri?  
**Jawab:** Ya, dengan verifikasi password.

## File Terkait

- [tests/Feature/Auth/PasswordUpdateTest.php.md](Auth/PasswordUpdateTest.php.md)
- [tests/Feature/Auth/PasswordConfirmationTest.php.md](Auth/PasswordConfirmationTest.php.md)
