# composer.json

> **Path:** `composer.json`  
> **Jenis:** Config  
> **Dipakai oleh:** Composer  
> **Memanggil:** -

## Ringkasan

Definisi dependensi PHP (Laravel 13, PhpSpreadsheet), dev dependencies, autoload PSR-4, dan script Composer.

## Peran dalam Sistem

Manajemen dependensi dan skrip setup.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| require | `composer.json:8-13` | PHP ^8.3, laravel/framework ^13.17, laravel/tinker ^3.0, phpoffice/phpspreadsheet ^5.9. |
| require-dev | `:14-23` | breeze, pint, phpunit ^12.5.12, collision, dll. |
| autoload | `:24-30` | PSR-4 App\, Database\Factories\, Database\Seeders\. |
| scripts | `:36-71` | setup, dev, test, post-autoload-dump, dll. |

## Alur Data

```text
composer install -> dependencies -> vendor/autoload.php
```

## Relasi

- Route: -
- Model/tabel: -
- View: -
- Service: -
- Test: phpunit.xml

## Catatan Teknis

- Menggunakan PhpSpreadsheet untuk export Excel.
- Laravel Breeze untuk scaffolding auth.
- Script `dev` menjalankan `php artisan dev`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Library apa untuk export Excel?  
**Jawab:** `phpoffice/phpspreadsheet` ^5.9.

## File Terkait

- [package.json.md](package.json.md)
- [phpunit.xml.md](phpunit.xml.md)
- [03-peta-route-dan-halaman.md](03-peta-route-dan-halaman.md)
