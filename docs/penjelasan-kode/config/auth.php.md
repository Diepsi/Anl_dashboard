# config/auth.php

> **Path:** `config/auth.php`  
> **Jenis:** Config  
> **Dipakai oleh:** Laravel Auth  
> **Memanggil:** `App\Models\User`, `env()`

## Ringkasan

Konfigurasi autentikasi: guard default (web/session), provider (eloquent User), dan password broker.

## Peran dalam Sistem

Lapisan konfigurasi autentikasi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| defaults | `config/auth.php:18-21` | guard `web`, passwords `users`. |
| guards | `:40-45` | guard web menggunakan driver session, provider users. |
| providers | `:64-74` | provider users menggunakan Eloquent model `App\Models\User` (bisa override via `AUTH_MODEL`). |
| passwords | `:95-102` | broker users pakai table `password_reset_tokens`, expire 60 menit, throttle 60 detik. |
| password_timeout | `:115` | Timeout konfirmasi password (default 10800 detik). |

## Alur Data

```text
Auth facade menggunakan guard web -> provider users (Eloquent User)
```

## Relasi

- Route: routes/auth.php
- Model/tabel: `users`, `password_reset_tokens`
- View: auth/*
- Service: -
- Test: -

## Catatan Teknis

- Menggunakan session guard (cocok dengan `SESSION_DRIVER=database`).
- Password reset menggunakan tabel `password_reset_tokens`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Guard apa yang digunakan?  
**Jawab:** Guard `web` dengan driver session.

## File Terkait

- [routes/auth.php.md](../routes/auth.php.md)
- [database/migrations/0001_01_01_000000_create_users_table.php.md](../database/migrations/0001_01_01_000000_create_users_table.php.md)
- [05-konfigurasi-environment.md](../05-konfigurasi-environment.md)
