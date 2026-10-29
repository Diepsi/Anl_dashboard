# app/Http/Controllers/Auth/AuthenticatedSessionController.php

> **Path:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`  
> **Jenis:** Controller (Auth)  
> **Dipakai oleh:** Route auth (login/logout), view `auth.login`  
> **Memanggil:** `App\Http\Controllers\Controller`, `App\Http\Requests\Auth\LoginRequest`, `Illuminate\Support\Facades\Auth`, view `auth.login`

## Ringkasan

Menangani login dan logout. `store()` memanggil `LoginRequest::authenticate()` (rate limit + cred check), lalu mengecek `is_active` - jika tidak aktif, logout dan lempar ValidationException. Jika aktif, regenerate session dan redirect intended ke dashboard.

## Peran dalam Sistem

Bagian autentikasi Laravel Breeze dengan tambahan pengecekan status aktif user.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `create(): View` | `AuthenticatedSessionController.php:18-21` | Tampilkan form login |
| `store(LoginRequest $request): RedirectResponse` | `AuthenticatedSessionController.php:26-41` | Autentikasi, cek `Auth::user()->is_active`, logout jika tidak aktif dengan pesan, regenerate session, redirect intended dashboard |
| `destroy(Request $request): RedirectResponse` | `AuthenticatedSessionController.php:46-55` | Logout guard web, invalidate session, regenerate token, redirect `/` |

## Alur Data

```text
GET /login -> create() -> view auth.login
POST /login -> LoginRequest::authenticate() (rate limit 5, Auth::attempt remember) 
-> jika gagal -> hit rate limiter + ValidationException auth.failed
-> jika sukses -> cek is_active: false -> Auth::guard('web')->logout() + ValidationException 'Akun ini sedang tidak aktif...'
-> is_active true -> session()->regenerate() -> redirect()->intended(route('dashboard'))
POST /logout -> destroy() -> logout + invalidate + regenerate token -> redirect /
```

## Relasi

- Route: login/create/store, logout
- Request: `App\Http\Requests\Auth\LoginRequest`
- Model/tabel: `App\Models\User` -> `users` (`is_active`)
- View: `auth/login.blade.php`
- Middleware: guest untuk login, auth untuk logout

## Catatan Teknis

- **Pengecekan aktif**: dilakukan setelah `authenticate()` berhasil (setelah kredensial valid dan rate limit clear). Jika tidak aktif, sesi langsung di-logout untuk mencegah akses.
- **Pesan error**: custom pesan Indonesia untuk akun nonaktif; kegagalan kredensial/rate limit pakai pesan default Laravel (`auth.failed`, `auth.throttle`).
- **Session regeneration**: dilakukan hanya jika user aktif (mencegah session fixation).
- **Intended redirect**: mengarahkan ke URL yang diminta sebelum login.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa yang terjadi jika user tidak aktif mencoba login?  
**Jawab:** `LoginRequest::authenticate()` tetap bisa berhasil (kredensial benar), lalu controller memeriksa `is_active`. Jika false, melakukan `Auth::guard('web')->logout()`, melempar `ValidationException` dengan pesan "Akun ini sedang tidak aktif. Hubungi administrator.", sehingga tidak bisa masuk. (`AuthenticatedSessionController.php:30-37`)

**Tanya:** Bagaimana rate limiting diterapkan?  
**Jawab:** Dikelola oleh `LoginRequest::ensureIsNotRateLimited()` dengan batas 5 percobaan per throttle key (`email|ip`), memicu event `Lockout` saat terlampaui, mengembalikan pesan throttle dengan detik/menit. (`LoginRequest.php:43-77`)

**Tanya:** Kapan session di-regenerate?  
**Jawab:** Hanya setelah autentikasi sukses dan user aktif, sebelum redirect. (`AuthenticatedSessionController.php:38-40`)

## File Terkait

- [LoginRequest](../../Requests/Auth/LoginRequest.php.md)
- [EnsureUserIsAdmin](../../Middleware/EnsureUserIsAdmin.php.md)
- [User Model](../../../Models/User.php.md)
- [NewPasswordController](NewPasswordController.php.md)
- [PasswordResetLinkController](PasswordResetLinkController.php.md)
