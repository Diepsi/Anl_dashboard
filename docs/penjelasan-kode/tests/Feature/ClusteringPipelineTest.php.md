# Feature/ClusteringPipelineTest

> **Path:** `tests/Feature/ClusteringPipelineTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Clustering wilayah (K-Means)
> **Memanggil:** `App\Models\RegionalCluster`, `App\Models\Shipment`, `App\Models\User`, `App\Services\ClusteringService`, `Illuminate\Support\Facades\Schedule`

## Ringkasan

Menguji pipeline clustering: `persist()` melakukan upsert dan pruning wilayah lama, `writeObservations()` menghasilkan CSV sesuai aturan kelayakan, command menangani Python tidak ditemukan, halaman clustering bisa diakses, dan clustering terjadwal setelah sync.

## Peran dalam Sistem

Mengunci perilaku `ClusteringService` dan integrasinya dengan scheduler/halaman.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `service()` | `tests/Feature/ClusteringPipelineTest.php:17-20` | Helper instantiate service |
| `shipment()` | `tests/Feature/ClusteringPipelineTest.php:22-35` | Helper buat shipment |
| `test_ingest_upserts_regional_results_and_prunes_stale()` | `tests/Feature/ClusteringPipelineTest.php:37-99` | Buat data lama di `regional_clusters`, panggil `persist($regions)`, pastikan Kota Lama dihapus, Kota X diupdate, cache disimpan |
| `test_observations_csv_respects_eligibility_rules()` | `tests/Feature/ClusteringPipelineTest.php:101-130` | Buat shipment (Completed+durasi, On Process tanpa durasi, Completed out SLA, tanpa kota) -> writeObservations -> CSV punya header benar, baris tanpa kota dikecualikan, durasi/is_out hanya untuk yang valid |
| `test_command_fails_gracefully_when_python_is_missing()` | `tests/Feature/ClusteringPipelineTest.php:132-139` | Set config python bin invalid -> `analytics:run-clustering` mengandung 'gagal', exit code 1 |
| `test_clustering_page_renders_section()` | `tests/Feature/ClusteringPipelineTest.php:141-162` | Buat RegionalCluster, actingAs user GET `route('clustering')` -> 200, lihat teks dan `clusterChart` |
| `test_clustering_is_scheduled_to_run_after_successful_sync()` | `tests/Feature/ClusteringPipelineTest.php:164-178` | Cek ada event schedule untuk `shipments:sync`, pastikan ada after-callback (clustering menempel) |

## Alur Arrange-Act-Assert

**persist + prune:** Arrange - buat 2 regional cluster lama; Act - `persist($regions)` dengan 2 wilayah baru (Kota X update, Kota Baru tambah); Assert - count jadi 2, Kota Lama null, Kota X label/cluster_id sesuai, cache terisi, meta summary urut label

**observations:** Arrange - 4 shipment dengan kondisi berbeda; Act - `writeObservations($path)`; Assert - header, count 3, durasi hanya ['','9','19'], is_out ['','0','1']

**command:** Arrange - config python invalid; Act - artisan command; Assert - output mengandung gagal, exit 1

**page:** Arrange - cluster + user; Act - GET clustering; Assert - OK, melihat judul dan elemen chart

**schedule:** Arrange - baca Schedule events; Act - filter; Assert - ada sync event dan ada after-callback

## Relasi

- Route: `GET /clustering` (named `clustering`)
- Model/tabel: `regional_clusters`, `shipments`, `users`
- Service: `App\Services\ClusteringService`
- Command: `analytics:run-clustering`
- Scheduler: `routes/console.php` (clustering attached after sync success)
- Python: `clustering.py` (diorkestrasi service)

## Catatan Teknis

- `persist()` melakukan upsert per kota dan menghapus wilayah yang tidak ada di hasil baru (prune).
- Cache key `ClusteringService::RESULT_CACHE_KEY` digunakan untuk meta run.
- `writeObservations()` menulis CSV untuk Python; hanya baris yang memenuhi kriteria (ada kota/kabupaten, Completed dengan durasi valid, dll.) ikut. Detail kriteria sesuai logika service (lihat implementasi).
- Test memverifikasi `durasi` dan `is_out` hanya terisi untuk kondisi tertentu - konsisten dengan prinsip "jangan mengarang nilai".
- Pengujian schedule menggunakan reflection untuk mengakses `afterCallbacks` (internal property).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa Kota Lama dihapus saat persist?  
**Jawab:** Karena wilayah tersebut tidak lagi muncul di hasil clustering terbaru - service melakukan prune untuk menjaga tabel sesuai hasil run terkini.

**Tanya:** Kapan durasi dihitung dalam observations?  
**Jawab:** Hanya untuk shipment `Completed` dengan tanggal konsisten sehingga durasi bisa dihitung; baris in-progress/tanpa durasi mendapat nilai kosong.

**Tanya:** Kenapa clustering dijadwalkan setelah sync sukses?  
**Jawab:** Agar clustering menggunakan data terbaru hasil sinkronisasi (lihat `routes/console.php`/deskripsi arsitektur).

## File Terkait

- [00-ringkasan-arsitektur.md](../../00-ringkasan-arsitektur.md)
- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [04-istilah-dan-rumus.md](../../04-istilah-dan-rumus.md)
- [tests/Feature/ShipmentSyncGuardTest.php.md](ShipmentSyncGuardTest.php.md)
