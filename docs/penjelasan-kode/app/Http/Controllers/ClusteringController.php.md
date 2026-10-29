# app/Http/Controllers/ClusteringController.php

> **Path:** `app/Http/Controllers/ClusteringController.php`  
> **Jenis:** Controller  
> **Dipakai oleh:** Route `/clustering`, view `clustering.blade.php`  
> **Memanggil:** `App\Models\RegionalCluster`, `App\Services\ClusteringService`, `Illuminate\Support\Facades\Cache`, view `clustering`

## Ringkasan

Menampilkan hasil K-Means clustering per kota/kabupaten dari tabel `regional_clusters`. Menghitung ringkasan per cluster, label warna, top risk, data scatter chart (x=out_sla_rate, y=avg_aging, r=proporsional volume), serta metadata clustering dari cache.

## Peran dalam Sistem

Berada di lapisan HTTP (presentasi hasil analitik). Membaca hasil yang telah dihitung oleh `ClusteringService` (dipanggil via `analytics:run-clustering`) dan menampilkannya; controller tidak menjalankan Python langsung.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `CLUSTER_COLORS` konstanta | `ClusteringController.php:11-16` | Pemetaan warna per `cluster_id` (0=merah/high risk, 1=biru/high volume, 2=hijau/low risk) |
| `index()` | `ClusteringController.php:18-73` | Query `RegionalCluster` urut `cluster_id`, agregasi ringkasan, top risk, chart, meta dari cache |

## Alur Data

```text
GET /clustering -> RegionalCluster::query()->orderBy('cluster_id')->get()
-> groupBy cluster_id untuk summary/labels
-> sortByDesc risk_index take(5) untuk topRisk
-> map ke clusterChart (label, cluster, x=out_sla_rate, y=avg_aging, r=sqrt(volume)*1.6)
-> ambil meta dari Cache (ClusteringService::RESULT_CACHE_KEY) dengan default
-> return view('clustering', compact(...))
```

## Relasi

- Route: `/clustering`
- Model/tabel: `App\Models\RegionalCluster` -> `regional_clusters`
- View: `resources/views/clustering.blade.php`
- Service: `App\Services\ClusteringService` (konstanta cache key, hasil dihitung terpisah)
- Test: -

## Catatan Teknis

- **Warna**: hardcoded, diasosiasikan dengan makna cluster (high risk/bottleneck, high volume, standard/low risk).
- **Ringkasan**: rata-rata `out_sla_rate` dan `avg_aging` per cluster dibulatkan 1 desimal; `shipments` = sum `total_shipment`.
- **Chart**: radius dihitung `max(4, min(22, round(sqrt(total_shipment)*1.6)))` untuk menjaga proporsional visual.
- **Meta**: fallback `['run_at'=>null,'summary'=>[],'n_regions'=>0]` jika cache belum ada.
- **Tidak ada filtering range** di controller ini (berbeda dari dashboard) - menampilkan seluruh wilayah hasil clustering.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah controller ini menjalankan proses clustering?  
**Jawab:** Tidak. Controller hanya membaca `regional_clusters` dan cache metadata. Proses (CSV observasi -> Python K-Means -> upsert) dijalankan oleh `ClusteringService` via command `analytics:run-clustering`. (`ClusteringController.php:18-73`)

**Tanya:** Bagaimana data ditampilkan di scatter chart?  
**Jawab:** Setiap baris jadi titik dengan `x = out_sla_rate`, `y = avg_aging`, warna mengikuti `cluster_id`, radius berdasarkan akar volume. (`ClusteringController.php:50-59`)

## File Terkait

- [00-ringkasan-arsitektur.md](../../../00-ringkasan-arsitektur.md)
- [01-alur-data-dan-sinkronisasi.md](../../../01-alur-data-dan-sinkronisasi.md)
- [02-skema-database.md](../../../02-skema-database.md)
- [ClusteringService](../../Services/ClusteringService.php.md)
- [RegionalCluster Model](../../Models/RegionalCluster.php.md)
