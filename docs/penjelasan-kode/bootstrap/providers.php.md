# bootstrap/providers.php

> **Path:** `bootstrap/providers.php`  
> **Jenis:** Bootstrap  
> **Dipakai oleh:** Aplikasi Laravel (bootstrap providers)  
> **Memanggil:** `App\Providers\AppServiceProvider`

## Ringkasan

Mendaftarkan service provider aplikasi. Saat ini hanya `App\Providers\AppServiceProvider`.

## Peran dalam Sistem

Lapisan bootstrap. Daftar provider yang akan di-load framework.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Providers array | `bootstrap/providers.php:5-7` | Mengembalikan array berisi `AppServiceProvider::class`. |

## Alur Data

```text
bootstrap/app.php -> load providers dari bootstrap/providers.php
```

## Relasi

- Route: -
- Model/tabel: -
- View: -
- Service: -
- Test: -

## Catatan Teknis

- File ini mengikuti pola Laravel 11+ untuk mendaftarkan providers.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Provider apa yang terdaftar?  
**Jawab:** Hanya `App\Providers\AppServiceProvider`.

## File Terkait

- [bootstrap/app.php.md](app.php.md)
