# config/session.php

> **Path:** `config/session.php`  
> **Jenis:** Config  
> **Dipakai oleh:** Laravel Session  
> **Memanggil:** `Illuminate\Support\Str`, `env()`, `storage_path()`

## Ringkasan

Konfigurasi sesi: driver database, lifetime, cookie, security options.

## Peran dalam Sistem

Lapisan konfigurasi sesi login.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| driver | `config/session.php:21` | `env('SESSION_DRIVER','database')`. |
| lifetime | `:35` | Menit idle (default 120). |
| table | `:89` | Tabel `sessions`. |
| cookie | `:130-133` | Nama cookie berdasarkan APP_NAME. |
| http_only/secure/same_site | `:185,172,202` | Pengaturan keamanan cookie. |

## Alur Data

```text
Sesi disimpan di tabel sessions (database driver)
```

## Relasi

- Route: routes/auth.php
- Model/tabel: `sessions`
- View: -
- Service: -
- Test: phpunit.xml menggunakan array

## Catatan Teknis

- Sesuai dengan migrasi sessions berbasis database.
- Konfigurasi cookie mengikuti praktik Laravel.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Dimana sesi disimpan?  
**Jawab:** Di tabel `sessions` karena `SESSION_DRIVER=database`.

## File Terkait

- [database/migrations/0001_01_01_000000_create_users_table.php.md](../database/migrations/0001_01_01_000000_create_users_table.php.md)
- [05-konfigurasi-environment.md](../05-konfigurasi-environment.md)
- [phpunit.xml.md](../phpunit.xml.md)
