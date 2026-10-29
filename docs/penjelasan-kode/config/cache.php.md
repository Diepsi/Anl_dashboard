# config/cache.php

> **Path:** `config/cache.php`  
> **Jenis:** Config  
> **Dipakai oleh:** Laravel Cache  
> **Memanggil:** `Illuminate\Support\Str`, `env()`

## Ringkasan

Konfigurasi cache: default store database, daftar store, prefix key, serializable classes.

## Peran dalam Sistem

Lapisan konfigurasi cache untuk menyimpan metadata sync dan clustering.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| default | `config/cache.php:18` | `env('CACHE_STORE','database')`. |
| stores.database | `:42-48` | Driver database, table `cache`, lock table `cache_locks`. |
| prefix | `:121` | `CACHE_PREFIX` atau slug APP_NAME + '-cache-'. |
| serializable_classes | `:134` | `false` (mencegah unserialisasi kelas PHP untuk keamanan). |

## Alur Data

```text
Cache facade -> store database -> tabel cache/cache_locks
```

## Relasi

- Route: -
- Model/tabel: `cache`, `cache_locks`
- View: -
- Service: `App\Services\ShipmentSyncService`, `App\Services\ClusteringService`
- Test: -

## Catatan Teknis

- Proyek menggunakan cache database (sesuai 05-konfigurasi-environment).
- `serializable_classes` false untuk mitigasi gadget chain attack.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kunci cache apa yang kritis?  
**Jawab:** `shipments.last_synced_at`, `shipments.source_stats`, `shipments.source_fingerprint`, `clustering.last_run`.

## File Terkait

- [database/migrations/0001_01_01_000001_create_cache_table.php.md](../database/migrations/0001_01_01_000001_create_cache_table.php.md)
- [05-konfigurasi-environment.md](../05-konfigurasi-environment.md)
- [01-alur-data-dan-sinkronisasi.md](../01-alur-data-dan-sinkronisasi.md)
