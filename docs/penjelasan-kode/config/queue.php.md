# config/queue.php

> **Path:** `config/queue.php`  
> **Jenis:** Config  
> **Dipakai oleh:** Laravel Queue  
> **Memanggil:** `env()`

## Ringkasan

Konfigurasi queue: default database, koneksi berbagai driver, batching, failed jobs.

## Peran dalam Sistem

Lapisan konfigurasi antrian (saat ini tidak dipakai untuk sync).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| default | `config/queue.php:16` | `env('QUEUE_CONNECTION','database')`. |
| connections.database | `:38-45` | Driver database, tabel `jobs`. |
| failed | `:123-127` | Driver `database-uuids`, tabel `failed_jobs`. |

## Alur Data

```text
Queue facade -> koneksi database -> tabel jobs
```

## Relasi

- Route: -
- Model/tabel: `jobs`, `job_batches`, `failed_jobs`
- View: -
- Service: -
- Test: phpunit.xml menggunakan sync

## Catatan Teknis

- Sync dijalankan langsung, bukan melalui queue.
- Infrastruktur queue tersedia via migrasi.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah sync menggunakan queue?  
**Jawab:** Tidak. Dijalankan sinkron via command/closure.

## File Terkait

- [database/migrations/0001_01_01_000002_create_jobs_table.php.md](../database/migrations/0001_01_01_000002_create_jobs_table.php.md)
- [05-konfigurasi-environment.md](../05-konfigurasi-environment.md)
- [phpunit.xml.md](../phpunit.xml.md)
