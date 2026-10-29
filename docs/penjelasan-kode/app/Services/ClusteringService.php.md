# app/Services/ClusteringService.php

> **Path:** `app/Services/ClusteringService.php`  
> **Jenis:** Service  
> **Dipakai oleh:** `app/Console/Commands/RunClusteringAnalysis.php`  
> **Memanggil:** `App\Models\RegionalCluster`, `App\Models\Shipment`, `Illuminate\Support\Facades\Cache`, `Illuminate\Support\Facades\DB`, `Illuminate\Support\Facades\File`, `Symfony\Component\Process\Process`, Python script `app/Console/Scripts/clustering.py`

## Ringkasan

`ClusteringService` mengorkestrasi clustering wilayah per kota/kabupaten: menulis observasi per baris pengiriman ke CSV sementara, menjalankan script Python (KMeans k=3 dengan StandardScaler), membaca hasil JSON, melakukan upsert ke tabel `regional_clusters`, menghapus wilayah yang tidak lagi muncul, dan menyimpan metadata ringkasan ke cache.

## Peran dalam Sistem

Berada di lapisan **Service/Analitik**. Memisahkan logika agregasi/orientasi data (PHP) dari algoritma clustering (Python) dengan kontrak file (CSV in, JSON out).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Konstanta | `ClusteringService.php:15-17` | `WORK_DIR = 'app/clustering'` (storage), `RESULT_CACHE_KEY = 'clustering.last_run'`. |
| `run()` | `ClusteringService.php:19-52` | Buat workspace, panggil `writeObservations`, jalankan `Process` (python bin + script + input + output, timeout dari config), validasi sukses, `readResult` -> `persist`, cleanup file temp di `finally`. |
| `writeObservations(string $path)` | `ClusteringService.php:61-92` | Tulis header `kota_kabupaten,provinsi,durasi,is_out`, query Shipment per chunk 2000 dengan filter wilayah terisi dan kalkulasi: `durasi` hanya jika `status_akhir='Completed'` dan tanggal konsisten (`tanggal_manifest<=completed_date`), `is_out` hanya jika terverifikasi (`sla_due_date` dan `completed_date` ada) -> 1 jika `completed_date>sla_due_date` else 0 else NULL. |
| `readResult(string $path)` | `ClusteringService.php:94-105` | Decode JSON, ambil `regions` (array), throw jika kosong. |
| `persist(array $regions)` | `ClusteringService.php:107-147` | Dalam transaksi: `RegionalCluster::upsert` by `kota_kabupaten` update kolom hasil, hapus wilayah tidak ada di hasil, bangun summary per cluster_label, simpan meta ke cache `clustering.last_run`. |
| `snippet(string $text, int $length=500)` | `ClusteringService.php:149-156` | Potong output error untuk pesan RuntimeException. |

## Alur Data (input -> proses -> output)

```text
Input: data shipments (kota_kabupaten terisi)
  -> writeObservations(): agregasi per baris observasi dengan aturan kelayakan
  -> Process: python clustering.py observations.csv result.json
  -> readResult(): parse JSON regions
  -> persist(): upsert regional_clusters, delete stale, cache meta
Output: ['regions'=>count, 'meta'=>[run_at, algo, n_regions, summary]]
```

**Aturan kelayakan di PHP (sesuai dasbor):**
- Durasi hanya untuk `Completed` + tanggal konsisten (`tanggal_manifest <= completed_date`) -> dihitung di Python sebagai rata-rata per wilayah.
- `is_out` hanya untuk baris terverifikasi (`sla_due_date` dan `completed_date` ada); 1 jika lewat SLA else 0; else NULL (tidak dihitung dalam verifiable).
- Hanya baris dengan `kota_kabupaten` tidak null/kosong yang diproses.

## Relasi

- **Route/Command:** dipanggil oleh `app/Console/Commands/RunClusteringAnalysis.php` (`analytics:run-clustering`).
- **Model/tabel:** `App\Models\RegionalCluster` -> tabel `regional_clusters`; membaca `App\Models\Shipment`.
- **View:** hasil ditampilkan di halaman clustering (membaca tabel `regional_clusters` dan cache `clustering.last_run`).
- **External:** memanggil Python via Symfony Process dengan konfigurasi `services.python.*`.

## Catatan Teknis, Edge Case, Keputusan Desain

- **Kelayakan di PHP:** memastikan definisi "durasi" dan "verifiable/out" sama dengan logika KPI SLA dashboard.
- **Chunking 2000:** efisien untuk volume data.
- **Upsert + cleanup:** `upsert` berdasarkan `kota_kabupaten`, lalu hapus wilayah yang tidak lagi muncul di hasil Python (mencegah sisa data lama).
- **Cache metadata:** `clustering.last_run` menyimpan `run_at`, `algo`, `n_regions`, `summary` per cluster.
- **Error handling:** jika proses Python gagal, lempar exception dengan snippet stderr/stdout; file temp dibersihkan di `finally`.
- **Konfigurasi terpusat:** bin/python/script/timeout diambil dari config (tidak di-hardcode).

## Kemungkinan Pertanyaan Penguji + Jawaban

**Tanya:** Apa kriteria baris yang masuk sebagai observasi durasi untuk clustering?  
**Jawab:** Hanya baris berstatus `Completed`, kedua tanggal ada, dan `tanggal_manifest <= completed_date` (konsisten) yang dihitung durasinya; baris lain durasi = NULL (L71-75).

**Tanya:** Bagaimana penentuan "out SLA" untuk observasi?  
**Jawab:** Hanya baris terverifikasi (`sla_due_date` dan `completed_date` ada) yang dinilai: `is_out = 1` jika `completed_date > sla_due_date`, else `0`. Jika tidak terverifikasi, `is_out = NULL` (L76-78).

**Tanya:** Apa yang terjadi jika wilayah hasil Python berbeda dari yang ada di tabel?  
**Jawab:** Dilakukan upsert untuk wilayah hasil, lalu `RegionalCluster::whereNotIn('kota_kabupaten', $kept)->delete()` sehingga wilayah yang tidak lagi ada dihapus (L110-118).

## File Terkait

- [RunClusteringAnalysis Command](../Console/Commands/RunClusteringAnalysis.php.md)
- [clustering.py Script](../Console/Scripts/clustering.py.md)
- [RegionalCluster Model](../Models/RegionalCluster.php.md)
- [Shipment Model](../Models/Shipment.php.md)
- [01-alur-data-dan-sinkronisasi](../../01-alur-data-dan-sinkronisasi.md)
- [04-istilah-dan-rumus](../../04-istilah-dan-rumus.md)
