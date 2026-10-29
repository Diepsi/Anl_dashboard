# app/Http/Controllers/DashboardController.php

> **Path:** `app/Http/Controllers/DashboardController.php`  
> **Jenis:** Controller  
> **Dipakai oleh:** Route `GET /` (dashboard), view `dashboard.blade.php`, Alpine.js modal (memanggil API staging/bottleneck)  
> **Memanggil:** `App\Models\Shipment`, `App\Services\ShipmentSyncService`, `Illuminate\Support\Carbon`, model scopes (`inRange`, `meetSla`, `outSla`, `slaVerifiable`, `withConsistentDates`), view `dashboard`

## Ringkasan

`DashboardController@index` menyusun seluruh KPI operasional: total/shipment, status, SLA vendor/customer, tren harian, pipeline stagging, bottleneck provinsi, vendor, lead time (median/P90), aging aktif, perhatian pengiriman, serta info sync. Semua agregasi berbasis query terkloning dari `Shipment::hasValidResi()->inRange($range)` dengan filter `range` hanya `0|30|90`. Baris tanpa No Resi valid tetap tersimpan untuk audit, tetapi tidak masuk KPI.

## Peran dalam Sistem

Berada di lapisan HTTP. Bertanggung jawab atas presentasi data agregat untuk halaman utama operasional. Menghitung metrik secara eksplisit (bukan hanya menampilkan kolom mentah) sesuai prinsip: SLA dihitung ulang dari `completed_date` vs `sla_due_date`, tanggal konsisten via scope, anchor aging dari `completed_date` tepercaya, lead time pakai median/P90.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `index(Request $request, ShipmentSyncService $service)` | `DashboardController.php:12-328` | Orkestrasi seluruh metrik dashboard |
| Validasi range | `DashboardController.php:14-18` | Hanya `0,30,90` diterima, selain itu `0` |
| `hasValidResi()->inRange($range)` base | `DashboardController.php:20` | Basis query terkloning untuk semua agregasi; KPI hanya resi valid |
| Mode SLA + fallback | `DashboardController.php:22-32` | Pilihan `vendor`/`customer` dari query; fallback ke customer bila label vendor kosong |
| Status aktif | `DashboardController.php:34` | `['On Process','On Process Delivery','Dikirim','On Delivery','Undelivered','Hold','Retur']` |
| Hitung SLA | `DashboardController.php:47-71` | Vendor: `meetSlaVendor()/outSlaVendor()`; customer: `meetSla()/outSla()`, `outSlaActive/outSlaDone` |
| Tren harian | `DashboardController.php:87`, `:297-403` | `trendSeries()`: volume kirim per `tanggal_manifest`; selesai, Out SLA, dan compliance per `completed_date`; sumbu tanggal kontinu |
| Stagging pipeline (9 tahap) | `DashboardController.php:120-145` | Urutan tetap + `keyBy` + filter yang punya nilai |
| Bottleneck & vendor | `DashboardController.php:147-204` | `outSla()` per provinsi, top vendor + "Lainnya", `leadTimeStatsByVendor()` |
| Anchor tanggal & aging | `DashboardController.php:206-257` | Anchor dari `MAX(completed_date)` konsisten (bukan manifest), bucket ≤14/15–30/31–60/>60, `agingExcluded` |
| Perhatian (attention) | `DashboardController.php:258-281` | Masalah status (`Hold/Undelivered/Retur`) didahulukan, baris tua ≥14 hari juga masuk; order khusus |
| Lead time | `DashboardController.php:337-407` | `leadTimeStats()`, `leadTimeValues()` filter `>=0`, `statsFromValues()`, `percentile()` floor-based |
| `mapBastKeterangan()` | `DashboardController.php:409-421` | Normalisasi label BAST |

## Alur Data

```text
Input (GET /?range=0|30|90&sla_mode=vendor|customer) -> validasi range + mode
-> base = Shipment::hasValidResi()->inRange(range) (clone)
-> hitung KPI/status/SLA (vendor: meetSlaVendor/outSlaVendor; customer: meetSla/outSla/slaVerifiable) 
-> tren harian via trendSeries(): volume kirim per tanggal_manifest, outcome per completed_date, sumbu kontinu 
-> staggingList (urutan 9 tahap) 
-> bottleneckStats (outSla per provinsi) + topVendors + vendorLeadTimes 
-> anchor = max(completed_date) konsisten -> agingBuckets + agingExcluded 
-> attentionShipments (prioritas masalah + usia>=14, urutan khusus) 
-> info sync -> view('dashboard', compact([...]))
```

**Transformasi penting:**
- SLA: default memakai label `RESULT FOR VENDOR`; mode customer menghitung ulang dari tanggal. Fallback otomatis ke customer bila data vendor kosong.
- Resi: hanya baris `hasValidResi` yang masuk KPI; baris tanpa resi valid dapat dilihat di halaman pengiriman dengan filter audit. 
- Anchor aging memakai `completed_date` terakhir konsisten (mencegah spike tanggal manifest). 
- `attentionShipments`: Retur/Undelivered/Hold dimasukkan tanpa wajib tanggal manifest; baris lain butuh `tanggal_manifest` dan usia ≥14 hari (menghindari baris masalah hilang). 
- Lead time: hanya `Completed`, kedua tanggal ada, selisih >=0, median/P90 (bukan mean) untuk tahan outlier.
- Tren harian: `volume_kirim` memakai `tanggal_manifest`, sedangkan `volume_selesai`, `Out SLA`, dan `SLA Compliance` memakai `completed_date`. Hari tanpa aktivitas tetap muncul dengan nilai 0 (compliance `null` bila tidak ada denominator). Label memakai `d M y` untuk range All dan `d M` untuk 30/90 hari.

## Relasi

- Route: `GET /` (dashboard)
- Model/tabel: `App\Models\Shipment` -> tabel `shipments`
- View: `resources/views/dashboard.blade.php`
- Service: `App\Services\ShipmentSyncService` (dipakai untuk `lastSyncedAt()`, `sourceStats()`)
- Test: `tests/Feature/DashboardMetricIntegrityTest.php`, `tests/Feature/SlaScopeConsistencyTest.php`, `tests/Feature/DashboardTrendTest.php`

## Catatan Teknis

- **Range**: hanya `0/30/90`; fallback ke `0`. Dihitung dari basis `inRange` (lihat model/scope terkait) - anchor tren memakai `completed_date` terakhir konsisten, bukan `today()`.
- **SLA**: `slaPct = withinSla / (withinSla+overSla)` dibulatkan; jika denom 0 -> `null`. `slaUnverified = totalShipment - slaCovered`. Mode vendor memakai `sla_vendor_result`; mode customer memakai `completed_date` vs `sla_due_date`.
- **Stagging**: urutan hardcoded 9 tahap (`BERANGKAT DARI CROSSDOCK` ... `PROCESS DOORING`), hanya yang muncul dengan total>0 dimasukkan ke `staggingList`.
- **Bottleneck**: `outSla()->whereNotNull('provinsi')` per provinsi.
- **Vendor**: top 5 + agregasi sisanya jadi `Lainnya`. `vendorStats` hanya vendor terisi, menghitung SLA on-time per vendor + lead time median/P90/sample.
- **Aging**: memakai `DATEDIFF(DATE(agingAnchor), DATE(tanggal_manifest))` inline dengan anchor string; `withConsistentDates()` untuk mencegah tanggal tidak konsisten. Baris aktif tanpa `tanggal_manifest` dihitung di `agingExcluded`.
- **Attention**: prioritas status bermasalah (`CASE WHEN status_akhir IN (...) THEN 0 ELSE 1 END`), lalu `COALESCE(days_open,0) DESC`, lalu `id DESC`. `days_open` dihitung dari anchor.
- **Lead time**: `percentile()` memakai `floor($percentile/100 * ($count-1))` (metode indeks berbasis posisi). Drop baris dengan selisih < 0. Mean tetap dihitung sebagai info tambahan.
- **BAST**: dikelompokkan via `mapBastKeterangan()` (regex-like match), menampilkan "Belum Terdata" jika kosong.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa anchor aging bukan `tanggal_manifest` terbaru?  
**Jawab:** Karena bisa ada spike tanggal manifest korup; memakai `MAX(completed_date)` konsisten mencegah seluruh kiriman aktif jatuh ke bucket tertua. (`DashboardController.php:219-224`)

**Tanya:** Bagaimana SLA dihitung dan mengapa ada dua mode?  
**Jawab:** KPI default mengikuti label `RESULT FOR VENDOR` dari sheet lewat `meetSlaVendor()/outSlaVendor()`. Mode customer menghitung ulang dari `completed_date` vs `sla_due_date` lewat `meetSla()/outSla()/slaVerifiable()`. `sla_due_date` sudah disatukan saat sync (bisa dari ambang hari atau tanggal batas), sehingga kedua mode tetap bisa dibandingkan. (`DashboardController.php:22-71`)

**Tanya:** Mengapa `attentionShipments` memasukkan Retur/Hold/Undelivered tanpa tanggal?  
**Jawab:** Untuk mencegah baris bermasalah hilang dari daftar perhatian (ada kasus tanpa `tanggal_manifest`). Hanya baris tidak bermasalah yang wajib punya tanggal dan usia ≥14 hari. Urutan memprioritaskan status bermasalah. (`DashboardController.php:258-281`)

**Tanya:** Mengapa lead time pakai median/P90 bukan rata-rata?  
**Jawab:** Outlier (batch dengan tanggal tidak logis) bisa membuat rata-rata negatif/salah; median lebih stabil. Baris dengan durasi < 0 dibuang. (`DashboardController.php:330-407`)

## File Terkait

- [00-ringkasan-arsitektur.md](../../../00-ringkasan-arsitektur.md)
- [01-alur-data-dan-sinkronisasi.md](../../../01-alur-data-dan-sinkronisasi.md)
- [02-skema-database.md](../../../02-skema-database.md)
- [ShipmentApiController](ShipmentApiController.php.md)
- [ShipmentsController](ShipmentsController.php.md)
- [Shipments Model](../../Models/Shipment.php.md)
- [ShipmentSyncService](../../Services/ShipmentSyncService.php.md)
