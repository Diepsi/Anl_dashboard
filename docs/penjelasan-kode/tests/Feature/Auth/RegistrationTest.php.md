# Feature/Auth/RegistrationTest

> **Path:** `tests/Feature/Auth/RegistrationTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Registrasi (Breeze)
> **Memanggil:** route `register`, `dashboard`

## Ringkasan

Menguji render halaman registrasi dan registrasi user baru yang berhasil.

## Peran dalam Sistem

Memastikan pendaftaran user baru berfungsi sesuai konfigurasi Breeze.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `test_registration_screen_can_be_rendered()` | `tests/Feature/Auth/RegistrationTest.php:12-17` | GET `/register` -> 200 |
| `test_new_users_can_register()` | `tests/Feature/Auth/RegistrationTest.php:19-30` | POST `/register` dengan data valid -> authenticated, redirect ke dashboard |

## Alur Arrange-Act-Assert

1. **Render:** GET `/register` -> 200
2. **Registrasi sukses:** POST data (name, email, password, password_confirmation) -> `assertAuthenticated()`, redirect `route('dashboard')`

## Relasi

- Route: `GET /register`, `POST /register`
- Model/tabel: `users`
- Test: Auth suite

## Catatan Teknis

- Menggunakan `RefreshDatabase`.
- Setelah registrasi, user otomatis terautentikasi (perilaku Breeze default).
- Redirect ke dashboard.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah aplikasi ini mengizinkan registrasi publik?  
**Jawab:** Berdasarkan test ini ya (route tersedia). Namun di arsitektur disebutkan ada role `admin/user` dan manajemen user; bisa jadi registrasi publik dibatasi atau hanya untuk setup awal. Test ini hanya mengkonfirmasi fungsionalitas Breeze.

**Tanya:** Apakah verifikasi email wajib setelah registrasi?  
**Jawab:** Tidak terlihat di test ini. EmailVerificationTest menguji verifikasi terpisah dengan user unverified.

## File Terkait

- [tests/Feature/Auth/AuthenticationTest.php.md](AuthenticationTest.php.md)
- [tests/Feature/Auth/EmailVerificationTest.php.md](EmailVerificationTest.php.md)
