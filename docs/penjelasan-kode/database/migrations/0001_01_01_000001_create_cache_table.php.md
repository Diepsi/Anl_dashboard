# 0001_01_01_000001_create_cache_table.php

> **Path:** `database/migrations/0001_01_01_000001_create_cache_table.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (skema cache)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`

## Ringkasan

Membuat tabel `cache` dan `cache_locks` untuk driver cache berbasis database. Digunakan untuk menyimpan metadata sync dan statistik aplikasi.

## Peran dalam Sistem

Lapisan database/migrasi. Mendukung `CACHE_STORE=database` (konfigurasi proyek) untuk menyimpan kunci cache seperti `shipments.*` dan `clustering.last_run`.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` - cache | `0001_01_01_000001_create_cache_table.php:14-18` | Tabel `cache` dengan `key` primary, `value` mediumText, `expiration` bigint + index. |
| `up()` - cache_locks | `0001_01_01_000001_create_cache_table.php:20-24` | Tabel `cache_locks` dengan `key` primary, `owner`, `expiration` bigint + index. |
| `down()` | `0001_01_01_000001_create_cache_table.php:30-34` | Drop `cache` dan `cache_locks`. |

## Alur Data

```text
migrate -> buat tabel cache/cache_locks -> Laravel Cache dapat memakai driver database
```

## Relasi

- Route: -
- Model/tabel: `cache`, `cache_locks`
- View: -
- Service: `App\Services\ShipmentSyncService` (menulis `shipments.last_synced_at`, `shipments.source_stats`, `shipments.source_fingerprint`), `App\Services\ClusteringService` (menulis metadata clustering)
- Test: -

## Catatan Teknis

- `value` mediumText (data ter-serialize).
- `expiration` disimpan sebagai Unix timestamp (bigint).
- `cache_locks` untuk lock atomik saat operasi cache sensitif.
- Jika cache dibersihkan, dashboard kehilangan waktu sync terakhir dan metadata clustering hingga sync/klaster berikutnya berjalan (sesuai 05-konfigurasi-environment).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kunci cache apa yang penting di aplikasi ini?  
**Jawab:** `shipments.last_synced_at`, `shipments.source_stats`, `shipments.source_fingerprint`, `clustering.last_run` (lihat 01-alur-data-dan-sinkronisasi dan 05-konfigurasi-environment).

**Tanya:** Apa fungsi `cache_locks`?  
**Jawab:** Menyediakan lock untuk mencegah race condition pada operasi cache yang perlu atomik.

## File Terkait

- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [05-konfigurasi-environment.md](../../05-konfigurasi-environment.md)
- [config/cache.php](../../config/cache.php.md)
