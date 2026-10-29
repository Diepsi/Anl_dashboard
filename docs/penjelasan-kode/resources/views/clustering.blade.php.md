# resources/views/clustering.blade.php

> **Path:** `resources/views/clustering.blade.php`  
> **Jenis:** View (Blade + Chart.js)  
> **Dipakai oleh:** Route `GET /clustering` melalui `ClusteringController@index`  
> **Memanggil:** `ClusteringController` (mengirim variabel: `$clusterSummary`, `$clusterChart`, `$clusterLabels`, `$clusterTopRisk`, `$clusteringMeta`), Chart.js

## Ringkasan

Halaman analitik clustering wilayah menggunakan K-Means (k=3). Menampilkan ringkasan zona, scatter plot Out-SLA vs rata-rata durasi, top 5 wilayah risiko tertinggi, dan metadata waktu clustering. Data bersumber dari tabel `regional_clusters` dan cache meta.

## Peran dalam Sistem

Lapisan presentasi analitik. Menampilkan hasil clustering yang dihitung oleh Python dan disimpan Laravel.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Header | `clustering.blade.php:6-16` | Judul, deskripsi k=3, info waktu run dan jumlah wilayah dari `$clusteringMeta`. |
| Card segmentasi | `clustering.blade.php:18-100` | Jika kosong menampilkan pesan jalankan `php artisan analytics:run-clustering`. Jika ada, tampilkan ringkasan 3 zona (`$clusterSummary`) dengan warna, jumlah kab/kota, kiriman, out-sla rate, avg aging. |
| Scatter chart | `clustering.blade.php:64-70` | Canvas `#clusterChart` dalam container. |
| Top risk list | `clustering.blade.php:72-97` | List `$clusterTopRisk` (top 5) dengan provinsi dan out-sla rate. |
| Chart JS | `clustering.blade.php:102-145` | Render scatter chart dari `$clusterChart` dan `$clusterLabels` dengan tooltip. |

## Alur Data

```text
Route GET /clustering (auth) -> ClusteringController@index
  -> baca regional_clusters + clusterSummary + clusterTopRisk + clusteringMeta (dari cache/tabel)
  -> pass ke view
View: render ringkasan dan chart
DOM ready: inisialisasi Chart.js scatter dengan datasets per cluster
```

## State Alpine (modal, filter, loading, error, chart)

Tidak menggunakan Alpine.js. Hanya Blade + vanilla JS + Chart.js.

Chart state: data point per wilayah dengan properti `cluster`, `label`, `x` (out-sla %), `y` (avg aging), `volume`, `color` (dari labels). Tooltip menampilkan: "label: x% out-SLA, y hari, volume kiriman".

## Relasi route/controller/API

- `GET /clustering` -> `ClusteringController@index` (auth)
- Data berasal dari `regional_clusters` dan cache meta (diisi oleh `ClusteringService` + `analytics:run-clustering`)

## Catatan Teknis

- **Label zona**: ditentukan dari risk/volume score (bukan urutan cluster_id) sesuai prinsip sistem.
- **Scatter axes**: X = Out SLA Rate (%), Y = Rata-rata Durasi (hari).
- **Warna per zona**: diambil dari `$zone['color']` dengan transparansi pada point.
- **Empty state**: jika belum ada hasil clustering, menampilkan instruksi menjalankan command.
- **Sumber data**: "sumber: seluruh data hasil sync terakhir" (disebutkan di UI).
- **Metadata**: menampilkan waktu run dan jumlah wilayah.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana zona clustering ditentukan?
**Jawab:** Label zona ditentukan dari pusat cluster berdasarkan risk_score (z_avg_aging + z_out_sla_rate) dan volume_score (z_total_shipment) - High Risk/Bottleneck, High Volume, atau Standard/Low Risk (bukan berdasarkan nomor cluster_id).

**Tanya:** Fitur apa yang digunakan K-Means?
**Jawab:** `avg_aging`, `out_sla_rate`, `total_shipment` per kota/kabupaten, dinormalisasi dengan StandardScaler, k=3, random_state=42.

**Tanya:** Kapan halaman clustering terisi?
**Jawab:** Setelah menjalankan `php artisan analytics:run-clustering` (otomatis setelah sync sukses, atau manual). Hasil disimpan di `regional_clusters` dan cache meta.

**Tanya:** Apa yang ditampilkan di scatter plot?
**Jawab:** Setiap titik = satu kabupaten/kota, posisi berdasarkan % Out-SLA (x) dan rata-rata durasi hari (y), ukuran/warna berdasarkan cluster, tooltip menampilkan label, nilai, dan volume.

**Tanya:** Apa arti "5 Wilayah Risiko Keterlambatan Tertinggi"?
**Jawab:** Diambil dari `$clusterTopRisk` (top risk berdasarkan metrik clustering) - ditampilkan dengan out-sla rate.

## File Terkait

- [00-ringkasan-arsitektur.md](../../00-ringkasan-arsitektur.md)
- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [03-peta-route-dan-halaman.md](../../03-peta-route-dan-halaman.md)
- [04-istilah-dan-rumus.md](../../04-istilah-dan-rumus.md)
- [app/Http/Controllers/ClusteringController.php](../../app/Http/Controllers/ClusteringController.php.md)
- [app/Services/ClusteringService.php](../../app/Services/ClusteringService.php.md)
- [routes/web.php](../../routes/web.php.md)
- [resources/js/app.js](../js/app.js.md)
