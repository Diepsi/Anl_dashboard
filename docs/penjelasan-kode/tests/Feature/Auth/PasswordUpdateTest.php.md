# Feature/Auth/PasswordUpdateTest

> **Path:** `tests/Feature/Auth/PasswordUpdateTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Update password dari profil (Breeze)
> **Memanggil:** `App\Models\User`, `Illuminate\Support\Facades\Hash`, route `password.update`

## Ringkasan

Menguji update password melalui halaman profil: berhasil dengan password saat ini benar, gagal jika password saat ini salah.

## Peran dalam Sistem

Memastikan user bisa mengganti password sendiri dengan verifikasi password saat ini.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `test_password_can_be_updated()` | `tests/Feature/Auth/PasswordUpdateTest.php:14-32` | actingAs user, PUT `/password` dengan `current_password` benar -> no errors, redirect `/profile`, password berubah (Hash::check) |
| `test_correct_password_must_be_provided_to_update_password()` | `tests/Feature/Auth/PasswordUpdateTest.php:34-50` | PUT dengan `current_password` salah -> error di bag `updatePassword`, redirect `/profile` |

## Alur Arrange-Act-Assert

1. **Update sukses:** actingAs -> from `/profile` -> PUT `/password` -> no session errors, redirect `/profile`, `Hash::check('new-password', $user->refresh()->password)` true
2. **Password salah:** actingAs -> from `/profile` -> PUT dengan wrong current password -> `assertSessionHasErrorsIn('updatePassword', 'current_password')`, redirect

## Relasi

- Route: `PUT /password`
- Model/tabel: `users` (password)
- View: profile
- Test: Auth suite

## Catatan Teknis

- Menggunakan `RefreshDatabase`.
- Menggunakan `Hash::check` untuk verifikasi perubahan password.
- Error diletakkan di error bag `updatePassword` (konvensi Breeze).
- Menggunakan `from()` untuk mensimulasikan referer.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa perlu verifikasi current_password?  
**Jawab:** Keamanan - mencegah perubahan password jika sesi dibajak.

**Tanya:** Apakah password baru harus berbeda?  
**Jawab:** Tidak diuji di sini.

## File Terkait

- [tests/Feature/Auth/PasswordConfirmationTest.php.md](PasswordConfirmationTest.php.md)
- [tests/Feature/ProfileTest.php.md](../ProfileTest.php.md)
