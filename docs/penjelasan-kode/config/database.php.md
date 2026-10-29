# config/database.php

> **Path:** `config/database.php`  
> **Jenis:** Config  
> **Dipakai oleh:** Laravel Database  
> **Memanggil:** `Illuminate\Support\Str`, `Pdo\Mysql`, `env()`

## Ringkasan

Konfigurasi koneksi database: default mysql, koneksi berbagai driver, migration table, redis.

## Peran dalam Sistem

Lapisan konfigurasi database utama.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| default | `config/database.php:20` | `env('DB_CONNECTION','sqlite')` (default framework) — proyek menggunakan mysql di .env. |
| mysql | `:47-65` | Host/port/database/username/password, charset utf8mb4, collation utf8mb4_unicode_ci, strict true. |
| migrations | `:130-133` | Tabel `migrations`. |
| redis | `:146-182` | Konfigurasi redis (tidak wajib dipakai). |

## Alur Data

```text
DB facade/Eloquent menggunakan koneksi default (mysql di praktik proyek)
```

## Relasi

- Route: -
- Model/tabel: semua tabel aplikasi
- View: -
- Service: -
- Test: phpunit.xml menggunakan sqlite in-memory

## Catatan Teknis

- Default framework adalah sqlite, namun `.env.example` dan praktik proyek menggunakan MySQL.
- MySQL menggunakan charset/collation utf8mb4.
- Pengujian menggunakan sqlite in-memory (phpunit.xml).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Database apa yang digunakan aplikasi?  
**Jawab:** MySQL (berdasarkan .env.example dan dokumentasi 05-konfigurasi-environment).

## File Terkait

- [05-konfigurasi-environment.md](../05-konfigurasi-environment.md)
- [phpunit.xml.md](../phpunit.xml.md)
- [02-skema-database.md](../02-skema-database.md)
