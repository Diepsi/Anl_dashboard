# config/logging.php

> **Path:** `config/logging.php`  
> **Jenis:** Config  
> **Dipakai oleh:** Laravel Logging (Monolog)  
> **Memanggil:** `Monolog\Handler\*`, `Monolog\Processor\PsrLogMessageProcessor`, `env()`

## Ringkasan

Konfigurasi logging: channel default stack/single, berbagai channel, dan deprecations.

## Peran dalam Sistem

Lapisan konfigurasi logging.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| default | `config/logging.php:21` | `env('LOG_CHANNEL','stack')`. |
| single | `:61-67` | `storage/logs/laravel.log`. |
| daily | `:69-74` | Log harian. |

## Alur Data

```text
Log facade -> Monolog handler
```

## Relasi

- Route: -
- Model/tabel: -
- View: -
- Service: -
- Test: -

## Catatan Teknis

- Default single file log.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Lokasi log default?  
**Jawab:** `storage/logs/laravel.log`.

## File Terkait

- [05-konfigurasi-environment.md](../05-konfigurasi-environment.md)
