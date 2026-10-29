# config/app.php

> **Path:** `config/app.php`  
> **Jenis:** Config  
> **Dipakai oleh:** Laravel framework, seluruh aplikasi  
> **Memanggil:** `env()` helper

## Ringkasan

Konfigurasi aplikasi Laravel: nama, environment, debug, URL, timezone, locale, encryption key, maintenance mode.

## Peran dalam Sistem

Lapisan konfigurasi. Menentukan perilaku dasar aplikasi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| name | `config/app.php:16` | `env('APP_NAME','Laravel')` |
| env | `:29` | `env('APP_ENV','production')` |
| debug | `:42` | `(bool) env('APP_DEBUG', false)` |
| url | `:55` | `env('APP_URL','http://localhost')` |
| timezone | `:68` | `'UTC'` (sesuai catatan 05-konfigurasi-environment) |
| locale/fallback/faker | `:81-85` | Locale aplikasi |
| cipher/key | `:98-106` | Enkripsi AES-256-CBC, APP_KEY |
| maintenance | `:121-124` | Driver maintenance |

## Alur Data

```text
Config loaded -> env() dibaca -> nilai dikembalikan ke framework
```

## Relasi

- Route: -
- Model/tabel: -
- View: APP_NAME digunakan di UI/email
- Service: -
- Test: -

## Catatan Teknis

- Timezone di-set UTC. Tanggal dari sumber diproses sebagai tanggal murni (tidak ada konversi zona waktu pada field date).
- `APP_KEY` wajib untuk enkripsi; generate dengan `php artisan key:generate`.
- `APP_DEBUG` harus false di produksi.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa timezone UTC?  
**Jawab:** Sesuai konfigurasi proyek; tanggal dari Google Sheets diperlakukan sebagai tanggal murni tanpa konversi zona waktu.

## File Terkait

- [05-konfigurasi-environment.md](../05-konfigurasi-environment.md)
- [public/index.php.md](../public/index.php.md)
- [artisan.md](../artisan.md)
