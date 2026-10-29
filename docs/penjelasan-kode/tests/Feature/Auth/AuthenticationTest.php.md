# Feature/Auth/AuthenticationTest

> **Path:** `tests/Feature/Auth/AuthenticationTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Autentikasi (Breeze)
> **Memanggil:** `App\Models\User`, `Illuminate\Foundation\Testing\RefreshDatabase`, route login/logout/dashboard

## Ringkasan

Menguji alur autentikasi dasar: render halaman login, login berhasil, login gagal dengan password salah, dan logout.

## Peran dalam Sistem

Memastikan mekanisme login/logout berfungsi sesuai konfigurasi Laravel Breeze yang dipakai aplikasi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `test_login_screen_can_be_rendered()` | `tests/Feature/Auth/AuthenticationTest.php:13-18` | GET `/login` -> 200 |
| `test_users_can_authenticate_using_the_login_screen()` | `tests/Feature/Auth/AuthenticationTest.php:20-31` | POST `/login` dengan kredensial valid -> authenticated, redirect ke `route('dashboard')` |
| `test_users_can_not_authenticate_with_invalid_password()` | `tests/Feature/Auth/AuthenticationTest.php:33-43` | POST `/login` dengan password salah -> guest |
| `test_users_can_logout()` | `tests/Feature/Auth/AuthenticationTest.php:45-53` | `actingAs($user)` POST `/logout` -> guest, redirect `/` |

## Alur Arrange-Act-Assert

1. **test_login_screen_can_be_rendered:** Arrange - ; Act - GET `/login`; Assert - status 200
2. **test_users_can_authenticate_using_the_login_screen:** Arrange - `User::factory()->create()`; Act - POST `/login` (email, password='password'); Assert - `assertAuthenticated()`, redirect dashboard
3. **test_users_can_not_authenticate_with_invalid_password:** Arrange - user dibuat; Act - POST dengan password salah; Assert - `assertGuest()`
4. **test_users_can_logout:** Arrange - user + actingAs; Act - POST `/logout`; Assert - guest, redirect `/`

## Relasi

- Route: `GET /login`, `POST /login`, `POST /logout`, `GET /dashboard` (route name `dashboard`)
- Model/tabel: `users`
- View: auth login
- Service: -
- Test: Auth suite

## Catatan Teknis

- Menggunakan `RefreshDatabase` (migrasi fresh per test).
- User factory diasumsikan membuat user dengan password default yang bisa di-hash/valid (default Breeze).
- Redirect setelah login mengarah ke `route('dashboard', absolute: false)`.
- Logout mengarahkan ke `/`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa yang terjadi jika user tidak aktif (`is_active = false`)?  
**Jawab:** Test ini tidak mengujinya. Di `00-ringkasan-arsitektur.md` disebutkan "Akun nonaktif ditolak saat login" - perilaku tersebut diuji di tempat lain atau merupakan tambahan dari Breeze; test ini fokus pada alur dasar.

**Tanya:** Apakah ada rate limiting pada login?  
**Jawab:** Dideskripsikan ada rate limit 5 percobaan di arsitektur, tetapi tidak diuji di file ini.

**Tanya:** Mengapa logout redirect ke `/` bukan `/login`?  
**Jawab:** Sesuai assertion test: `$response->assertRedirect('/')`.

## File Terkait

- [tests/TestCase.php.md](../../TestCase.php.md)
- [tests/Feature/Auth/EmailVerificationTest.php.md](EmailVerificationTest.php.md)
- [tests/Feature/Auth/PasswordResetTest.php.md](PasswordResetTest.php.md)
- [00-ringkasan-arsitektur.md](../../../00-ringkasan-arsitektur.md)
