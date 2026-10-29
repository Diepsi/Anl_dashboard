# 2026_09_29_000004_create_regional_clusters_table.php

> **Path:** `database/migrations/2026_09_29_000004_create_regional_clusters_table.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (skema hasil clustering)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`

## Ringkasan

Membuat tabel `regional_clusters` untuk menyimpan hasil K-Means per kota/kabupaten: cluster_id, label, avg_aging, out_sla_rate, total_shipment, verifiable_count, risk_index.

## Peran dalam Sistem

Lapisan database/migrasi. Mendukung halaman `/clustering` dan analitik wilayah.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` | `2026_09_29_000004_create_regional_clusters_table.php:11-24` | Buat tabel dengan `kota_kabupaten` unique, `provinsi`, `cluster_id` unsignedTinyInteger, `cluster_label`, `avg_aging` decimal(8,2) nullable, `out_sla_rate` decimal(5,2) nullable, `total_shipment` unsignedInteger, `verifiable_count` unsignedInteger default 0, `risk_index` decimal(5,2) nullable, timestamps. |
| `down()` | `:26-29` | Drop `regional_clusters`. |

## Alur Data

```text
analytics:run-clustering -> ClusteringService -> Python K-Means -> upsert ke regional_clusters
```

## Relasi

- Route: routes/web.php (`/clustering`)
- Model/tabel: `regional_clusters` (relasi analitis ke `shipments` via `kota_kabupaten`)
- View: clustering.blade.php
- Service: `App\Services\ClusteringService`
- Test: -

## Catatan Teknis

- `kota_kabupaten` unik sebagai kunci upsert.
- Tidak ada foreign key fisik ke `shipments` (hubungan analitis).
- `verifiable_count` mencatat jumlah baris dengan data SLA lengkap untuk perhitungan yang aman.
- `risk_index` dihitung oleh service/Python.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana clustering dijalankan?  
**Jawab:** Via command `analytics:run-clustering` yang dipanggil otomatis setelah `shipments:sync` berhasil (routes/console.php). `ClusteringService` menulis CSV observasi, menjalankan Python `clustering.py`, lalu upsert hasil ke tabel ini.

**Tanya:** Apa syarat menjalankan K-Means k=3?  
**Jawab:** Minimal 9 wilayah dengan `avg_aging` dan `out_sla_rate` valid (01-alur-data-dan-sinkronisasi).

## File Terkait

- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [02-skema-database.md](../../02-skema-database.md)
- [03-peta-route-dan-halaman.md](../../03-peta-route-dan-halaman.md)
- [routes/console.php](../../routes/console.php.md)
- [config/services.php](../../config/services.php.md)
