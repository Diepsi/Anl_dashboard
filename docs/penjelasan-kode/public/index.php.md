# public/index.php

> **Path:** `public/index.php`  
> **Jenis:** Public entrypoint  
> **Dipakai oleh:** Web server (Apache/Nginx/Valet/Laragon)  
> **Memanggil:** `Illuminate\Foundation\Application`, `Illuminate\Http\Request`, `vendor/autoload.php`, `bootstrap/app.php`

## Ringkasan

Front controller Laravel. Mengatur maintenance mode, memuat autoloader, bootstrap aplikasi, dan menangani request.

## Peran dalam Sistem

Lapisan HTTP entrypoint. Titik masuk semua request web.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| LARAVEL_START | `public/index.php:6` | Mencatat waktu mulai untuk profiling. |
| Maintenance check | `:9-11` | Jika ada `storage/framework/maintenance.php`, load dan jalankan mode maintenance. |
| Autoload | `:14` | Require `vendor/autoload.php`. |
| Bootstrap | `:18-20` | Load `bootstrap/app.php`, handle request dengan `Request::capture()`. |

## Alur Data

```text
HTTP Request -> public/index.php -> maintenance check -> autoload -> bootstrap/app.php -> Application->handleRequest() -> Response
```

## Relasi

- Route: bootstrap/app.php mengarahkan routing
- Model/tabel: -
- View: -
- Service: -
- Test: -

## Catatan Teknis

- Hanya file ini yang diakses langsung oleh web server (document root biasanya `public/`).
- `LARAVEL_START` digunakan untuk menghitung waktu eksekusi.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa semua request lewat index.php?  
**Jawab:** Karena ini front controller Laravel (single entry point).

## File Terkait

- [bootstrap/app.php.md](../bootstrap/app.php.md)
- [artisan.md](../artisan.md)
