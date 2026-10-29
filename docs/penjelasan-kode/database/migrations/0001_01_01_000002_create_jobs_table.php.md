# 0001_01_01_000002_create_jobs_table.php

> **Path:** `database/migrations/0001_01_01_000002_create_jobs_table.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (infrastruktur queue)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`

## Ringkasan

Membuat tabel infrastruktur queue Laravel: `jobs`, `job_batches`, `failed_jobs`. Saat ini tidak dipakai untuk proses sync, namun tersedia untuk pengembangan lanjutan.

## Peran dalam Sistem

Lapisan database/migrasi. Mendukung `QUEUE_CONNECTION=database` sesuai konfigurasi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` - jobs | `0001_01_01_000002_create_jobs_table.php:14-22` | `id`, `queue` + index, `payload` longText, `attempts` unsignedSmallInteger, `reserved_at` nullable unsignedInteger, `available_at` unsignedInteger, `created_at` unsignedInteger. |
| `up()` - job_batches | `0001_01_01_000002_create_jobs_table.php:24-36` | `id` string primary, `name`, `total_jobs`, `pending_jobs`, `failed_jobs`, `failed_job_ids` longText, `options` mediumText nullable, `cancelled_at`, `created_at`, `finished_at` nullable. |
| `up()` - failed_jobs | `0001_01_01_000002_create_jobs_table.php:37-47` | `id`, `uuid` unique, `connection`, `queue`, `payload` longText, `exception` longText, `failed_at` useCurrent(), index (`connection`,`queue`,`failed_at`). |
| `down()` | `0001_01_01_000002_create_jobs_table.php:53-58` | Drop `jobs`, `job_batches`, `failed_jobs`. |

## Alur Data

```text
dispatch job -> jobs -> worker proses -> sukses/hapus atau masuk failed_jobs
```

## Relasi

- Route: -
- Model/tabel: `jobs`, `job_batches`, `failed_jobs`
- View: -
- Service: -
- Test: -

## Catatan Teknis

- Waktu disimpan sebagai Unix timestamp (unsignedInteger) di `jobs/job_batches` (kecuali `failed_jobs.failed_at` timestamp).
- `QUEUE_CONNECTION=database` diatur, namun proses sinkronisasi dijalankan langsung via command/tombol (sinkron) sesuai 01-alur-data-dan-sinkronisasi.
- Rollback menghapus seluruh antrian dan riwayat job gagal.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah sync memakai queue saat ini?  
**Jawab:** Tidak. `routes/web.php` memanggil `ShipmentSyncService::sync()` langsung saat tombol ditekan; `routes/console.php` menjalankan command secara langsung.

**Tanya:** Kapan `failed_jobs` terisi?  
**Jawab:** Saat job yang di-dispatch gagal dan tidak bisa dicoba lagi sesuai konfigurasi queue.

## File Terkait

- [05-konfigurasi-environment.md](../../05-konfigurasi-environment.md)
- [config/queue.php](../../config/queue.php.md)
- [routes/web.php](../../routes/web.php.md)
- [routes/console.php](../../routes/console.php.md)
