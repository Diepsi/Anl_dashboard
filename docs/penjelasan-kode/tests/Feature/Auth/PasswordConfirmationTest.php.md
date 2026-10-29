# Feature/Auth/PasswordConfirmationTest

> **Path:** `tests/Feature/Auth/PasswordConfirmationTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Konfirmasi password (Breeze)
> **Memanggil:** `App\Models\User`, route `confirm-password`

## Ringkasan

Menguji halaman konfirmasi password, konfirmasi berhasil dengan password benar, dan gagal dengan password salah.

## Peran dalam Sistem

Memastikan proteksi route sensitif bisa dipanggil hanya setelah user mengkonfirmasi password.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `test_confirm_password_screen_can_be_rendered()` | `tests/Feature/Auth/PasswordConfirmationTest.php:13-20` | actingAs user GET `/confirm-password` -> 200 |
| `test_password_can_be_confirmed()` | `tests/Feature/Auth/PasswordConfirmationTest.php:22-32` | POST `/confirm-password` dengan password benar -> redirect, no errors |
| `test_password_is_not_confirmed_with_invalid_password()` | `tests/Feature/Auth/PasswordConfirmationTest.php:34-43` | POST dengan password salah -> session has errors |

## Alur Arrange-Act-Assert

1. **Render:** actingAs user -> GET -> 200
2. **Konfirmasi sukses:** actingAs -> POST password valid -> redirect, `assertSessionHasNoErrors()`
3. **Konfirmasi gagal:** actingAs -> POST password salah -> `assertSessionHasErrors()`

## Relasi

- Route: `GET /confirm-password`, `POST /confirm-password`
- Model/tabel: `users`
- Test: Auth suite

## Catatan Teknis

- Menggunakan `RefreshDatabase`.
- Password default factory diasumsikan 'password'.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan password confirmation digunakan?  
**Jawab:** Biasanya untuk aksi sensitif (hapus akun, ubah pengaturan penting) setelah periode waktu tertentu.

**Tanya:** Apa efek setelah konfirmasi berhasil?  
**Jawab:** Session ditandai sebagai sudah dikonfirmasi; test hanya memverifikasi redirect dan tidak ada error.

## File Terkait

- [tests/Feature/Auth/AuthenticationTest.php.md](AuthenticationTest.php.md)
- [tests/Feature/Auth/PasswordUpdateTest.php.md](PasswordUpdateTest.php.md)
