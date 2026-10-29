# phpunit.xml

> **Path:** `phpunit.xml`  
> **Jenis:** Config  
> **Dipakai oleh:** PHPUnit  
> **Memanggil:** -

## Ringkasan

Konfigurasi PHPUnit untuk testing: test suites Unit/Feature, source app, dan environment testing (sqlite in-memory, array cache/session/mail, sync queue).

## Peran dalam Sistem

Konfigurasi pengujian otomatis.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| testsuites | `phpunit.xml:7-14` | Unit dan Feature. |
| source | `:15-19` | Include `app/`. |
| php env | `:20-35` | APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:, CACHE_STORE=array, SESSION_DRIVER=array, MAIL_MAILER=array, QUEUE_CONNECTION=sync. |

## Alur Data

```text
php artisan test / vendor/bin/phpunit -> load config -> run tests dengan env testing
```

## Relasi

- Route: -
- Model/tabel: -
- View: -
- Service: -
- Test: tests/*

## Catatan Teknis

- Menggunakan database in-memory untuk isolasi test.
- Berbagai service di-fake dengan array/null/sync.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Database apa untuk testing?  
**Jawab:** SQLite in-memory (`:memory:`).

## File Terkait

- [composer.json.md](composer.json.md)
- [config/database.php.md](config/database.php.md)
- [config/cache.php.md](config/cache.php.md)
