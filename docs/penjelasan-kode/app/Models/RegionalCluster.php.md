# app/Models/RegionalCluster.php

> **Path:** `app/Models/RegionalCluster.php`  
> **Jenis:** Model (Eloquent)  
> **Dipakai oleh:** `ClusteringService`, halaman clustering  
> **Memanggil:** `Illuminate\Database\Eloquent\Model`

## Ringkasan

Model untuk tabel `regional_clusters` hasil K-Means per kota/kabupaten. Mendefinisikan fillable dan casting numerik/decimal.

## Peran dalam Sistem

Berada di lapisan **Model/Analitik**. Menyimpan hasil clustering (cluster_id/label, avg_aging, out_sla_rate, total_shipment, verifiable_count, risk_index).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `$fillable` | `RegionalCluster.php:9-19` | Semua kolom hasil clustering. |
| `casts()` | `RegionalCluster.php:21-31` | `cluster_id` integer, `avg_aging`/`out_sla_rate`/`risk_index` decimal:2, `total_shipment`/`verifiable_count` integer. |

## Alur Data (input -> proses -> output)

```text
Hasil Python (regions) -> ClusteringService::persist() upsert
  -> RegionalCluster tersimpan
Output: dibaca halaman clustering + cache summary
```

## Relasi

- **Route:** halaman clustering.
- **Model/tabel:** `regional_clusters` (kunci unik `kota_kabupaten`).
- **Service:** diisi oleh `ClusteringService`.

## Catatan Teknis, Edge Case, Keputusan Desain

- **Kunci unik:** `kota_kabupaten` unik digunakan untuk upsert dan cleanup wilayah hilang.
- **Casting konsisten:** desimal untuk rate/aging/index.

## Kemungkinan Pertanyaan Penguji + Jawaban

**Tanya:** Bagaimana relasi antara shipments dan regional_clusters?  
**Jawab:** Tidak ada FK fisik; hubungan analitis lewat `kota_kabupaten` untuk agregasi clustering (lihat 01/02).

**Tanya:** Apa yang disimpan di kolom `risk_index`?  
**Jawab:** Skor risiko 0-100 hasil perhitungan Python dari komponen ter-standardisasi (aging+out-sla) yang di-clip ±2 std dev (lihat clustering.py).

## File Terkait

- [ClusteringService](../Services/ClusteringService.php.md)
- [clustering.py](../Console/Scripts/clustering.py.md)
- [02-skema-database](../../02-skema-database.md)
- [04-istilah-dan-rumus](../../04-istilah-dan-rumus.md)
