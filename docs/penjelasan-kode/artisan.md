# artisan

> **Path:** `artisan`  
> **Jenis:** CLI entrypoint  
> **Dipakai oleh:** Pengguna/terminal  
> **Memanggil:** `Illuminate\Foundation\Application`, `Symfony\Component\Console\Input\ArgvInput`, `vendor/autoload.php`, `bootstrap/app.php`

## Ringkasan

File executable untuk menjalankan Artisan CLI. Bootstrap aplikasi dan handle command.

## Peran dalam Sistem

Lapisan CLI entrypoint.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Shebang | `artisan:1` | `#!/usr/bin/env php` |
| Autoload | `:10` | Require autoloader. |
| Bootstrap | `:14-16` | Load bootstrap/app.php, handle command dengan ArgvInput, exit status. |

## Alur Data

```text
php artisan <command> -> artisan -> bootstrap app -> handleCommand -> output
```

## Relasi

- Route: routes/console.php
- Model/tabel: -
- View: -
- Service: -
- Test: -

## Catatan Teknis

- Digunakan untuk migrate, db:seed, shipments:sync, analytics:run-clustering, schedule:run, dll.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Untuk apa file artisan?  
**Jawab:** Entrypoint CLI Laravel untuk menjalankan perintah Artisan.

## File Terkait

- [public/index.php.md](public/index.php.md)
- [bootstrap/app.php.md](bootstrap/app.php.md)
- [routes/console.php.md](routes/console.php.md)
