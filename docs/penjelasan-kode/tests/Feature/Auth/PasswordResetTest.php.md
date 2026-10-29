# Feature/Auth/PasswordResetTest

> **Path:** `tests/Feature/Auth/PasswordResetTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Reset password (Breeze)
> **Memanggil:** `App\Models\User`, `Illuminate\Auth\Notifications\ResetPassword`, `Illuminate\Support\Facades\Notification`

## Ringkasan

Menguji alur lupa password: render form, request link reset (notifikasi terkirim), render form reset dengan token, dan reset password berhasil.

## Peran dalam Sistem

Memastikan user bisa meminta reset password dan meresetnya melalui link yang dikirimkan.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `test_reset_password_link_screen_can_be_rendered()` | `tests/Feature/Auth/PasswordResetTest.php:15-20` | GET `/forgot-password` -> 200 |
| `test_reset_password_link_can_be_requested()` | `tests/Feature/Auth/PasswordResetTest.php:22-31` | `Notification::fake()`, POST email -> notifikasi `ResetPassword` dikirim ke user |
| `test_reset_password_screen_can_be_rendered()` | `tests/Feature/Auth/PasswordResetTest.php:33-48` | Request link, ambil token dari notifikasi, GET `/reset-password/{token}` -> 200 |
| `test_password_can_be_reset_with_valid_token()` | `tests/Feature/Auth/PasswordResetTest.php:50-72` | Request link, POST ke `/reset-password` dengan token+email+password baru -> no errors, redirect ke login |

## Alur Arrange-Act-Assert

1. **Render form lupa password:** GET `/forgot-password` -> 200
2. **Request link:** `Notification::fake()` -> POST email -> `Notification::assertSentTo($user, ResetPassword::class)`
3. **Render form reset:** request link -> ambil notifikasi via callback, GET reset link dengan token -> 200
4. **Reset sukses:** request link -> POST reset dengan data lengkap -> session no errors, redirect login

## Relasi

- Route: `GET /forgot-password`, `POST /forgot-password`, `GET /reset-password/{token}`, `POST /reset-password`
- Model/tabel: `users`
- Notification: `ResetPassword`
- Test: Auth suite

## Catatan Teknis

- Menggunakan `RefreshDatabase`.
- `Notification::fake()` mencegah pengiriman email nyata.
- Token diambil dari instance notifikasi yang dikirim (closure assertion).
- Setelah reset berhasil, redirect ke route `login`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah token bisa digunakan berkali-kali?  
**Jawab:** Tidak diuji di sini; perilaku default Laravel biasanya token sekali pakai, tapi test ini tidak memverifikasinya.

**Tanya:** Apa yang terjadi jika email tidak terdaftar?  
**Jawab:** Tidak diuji. Laravel bisa merespons sama untuk keamanan (tidak mengungkap keberadaan email).

**Tanya:** Bagaimana validasi password konfirmasi bekerja?  
**Jawab:** Diuji secara implisit (`password_confirmation` dikirim dan reset berhasil).

## File Terkait

- [tests/Feature/Auth/AuthenticationTest.php.md](AuthenticationTest.php.md)
- [tests/Feature/Auth/PasswordUpdateTest.php.md](PasswordUpdateTest.php.md)
