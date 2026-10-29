# app/Console/Commands/RunClusteringAnalysis.php

> **Path:** `app/Console/Commands/RunClusteringAnalysis.php`  
> **Jenis:** Console Command  
> **Dipakai oleh:** CLI, operator  
> **Memanggil:** `App\Services\ClusteringService`

## Ringkasan

Command Artisan untuk menjalankan analisis clustering wilayah. Memanggil `ClusteringService::run()`, menampilkan ringkasan jumlah wilayah/zone dan statistik per zone, mengembalikan exit code.

## Peran dalam Sistem

Berada di lapisan **Console/CLI**. Entry point untuk clustering manual (bisa juga dipanggil setelah sync).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Signature/Description | `RunClusteringAnalysis.php:10-12` | `analytics:run-clustering`, deskripsi K-Means per kota/kabupaten. |
| `handle(ClusteringService $service)` | `RunClusteringAnalysis.php:14-44` | Info awal, panggil `run()`, tampilkan "Selesai: X wilayah dikelompokkan ke Y zona", iterasi `meta['summary']` menampilkan label, jumlah wilayah/kiriman, out-SLA %, aging rata-rata, return SUCCESS/FAILURE. |

## Alur Data (input -> proses -> output)

```text
Input: (tanpa opsi)
  -> handle(): ClusteringService::run()
  -> output: ringkasan per zone
Exit code: 0 atau 1
```

## Relasi

- **Service:** `ClusteringService`.
- **Script:** `app/Console/Scripts/clustering.py`.
- **Model:** `RegionalCluster`.

## Catatan Teknis, Edge Case, Keputusan Desain

- **Error handling:** catch Throwable, tampilkan error, return FAILURE.
- **Output informatif:** menampilkan summary dari meta cache/result.

## Kemungkinan Pertanyaan Penguji + Jawaban

**Tanya:** Apa yang ditampilkan setelah clustering berhasil?  
**Jawab:** Jumlah wilayah yang dikelompokkan dan jumlah zona, lalu per baris: label zona, jumlah wilayah, jumlah kiriman, out-SLA %, aging rata-rata (L26-41).

**Tanya:** Kapan command ini biasanya dijalankan?  
**Jawab:** Manual via CLI atau bisa dijadwalkan; dalam arsitektur disebutkan setelah sync sukses scheduler menjalankan clustering (01-alur-data-dan-sinkronisasi).

## File Terkait

- [ClusteringService](../../Services/ClusteringService.php.md)
- [clustering.py](../Scripts/clustering.py.md)
- [RegionalCluster](../../Models/RegionalCluster.php.md)
- [01-alur-data-dan-sinkronisasi](../../../01-alur-data-dan-sinkronisasi.md)
