# app/Http/Requests/Auth/LoginRequest.php

> **Path:** `app/Http/Requests/Auth/LoginRequest.php`  
> **Jenis:** Form Request (Auth)  
> **Dipakai oleh:** `AuthenticatedSessionController@store`  
> **Memanggil:** `Illuminate\Auth\Events\Lockout`, `Illuminate\Support\Facades\Auth`, `Illuminate\Support\Facades\RateLimiter`, `Illuminate\Support\Str`

## Ringkasan

Form Request untuk login: validasi email/password, autentikasi via `Auth::attempt()` dengan remember, rate limiting (max 5 percobaan) per `email|ip`, mengeluarkan event `Lockout` saat dibatasi, membersihkan limiter saat berhasil.

## Peran dalam Sistem

Lapisan validasi + autentikasi untuk login, terintegrasi dengan throttle Laravel.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `authorize(): bool` | `LoginRequest.php:18-21` | Return true (siapa pun bisa mengajukan login) |
| `rules(): array` | `LoginRequest.php:28-34` | `email` required|email, `password` required|string |
| `authenticate(): void` | `LoginRequest.php:41-54` | Cek rate limit, `Auth::attempt($this->only('email','password'), $this->boolean('remember'))`, jika gagal `RateLimiter::hit()` + ValidationException `auth.failed`, jika berhasil `RateLimiter::clear()` |
| `ensureIsNotRateLimited(): void` | `LoginRequest.php:61-77` | Jika `tooManyAttempts(throttleKey, 5)` fire `Lockout`, hitung sisa waktu, lempar ValidationException `auth.throttle` dengan detik/menit |
| `throttleKey(): string` | `LoginRequest.php:82-85` | `Str::transliterate(Str::lower($email).'|'.$ip)` |

## Alur Data

```text
POST login -> rules validasi -> authenticate()
  ensureIsNotRateLimited(): jika >=5 -> Lockout event + throttle exception
  Auth::attempt(email,password,remember): gagal -> hit() + auth.failed exception
  berhasil -> clear() -> return (controller lanjut cek is_active)
```

## Relasi

- Controller: `AuthenticatedSessionController`
- Model: `User`
- Events: `Lockout`
- Facades: Auth, RateLimiter

## Catatan Teknis

- **Throttle key**: berdasarkan email (lowercase, transliterated) + IP untuk mencegah brute force per kombinasi.
- **Max attempts**: 5.
- **Remember**: meneruskan nilai boolean `remember`.
- **Pesan**: menggunakan translasi `auth.failed` dan `auth.throttle`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Berapa maksimal percobaan login?  
**Jawab:** 5 percobaan per throttle key (`email|ip`). Setelah itu dibatasi dengan pesan throttle. (`LoginRequest.php:63, 70-77`)

**Tanya:** Apa yang terjadi saat berhasil login?  
**Jawab:** Rate limiter di-clear untuk key tersebut. (`LoginRequest.php:53`)

**Tanya:** Kapan event Lockout di-fire?  
**Jawab:** Saat `tooManyAttempts()` true di `ensureIsNotRateLimited()`. (`LoginRequest.php:67`)

## File Terkait

- [AuthenticatedSessionController](../../Controllers/Auth/AuthenticatedSessionController.php.md)
- [User Model](../../../Models/User.php.md)
- [02-skema-database.md](../../../../02-skema-database.md) (cache/rate limit)
