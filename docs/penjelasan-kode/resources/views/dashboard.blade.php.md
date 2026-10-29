# resources/views/dashboard.blade.php

> **Path:** `resources/views/dashboard.blade.php`  
> **Jenis:** View (Blade + Alpine.js + Chart.js)  
> **Dipakai oleh:** Route `GET /` melalui `DashboardController@index`  
> **Memanggil:** `DashboardController` (mengirim variabel: `$range`, `$totalShipment`, `$completed`, `$onDeliveryCount`, `$holdCount`, `$undeliveredCount`, `$withinSla`, `$overSla`, `$outSlaActive`, `$outSlaDone`, `$slaPct`, `$slaCovered`, `$slaUnverified`, `$completionRate`, `$leadTime`, `$trend`, `$statusBreakdown`, `$topProvinces`, `$topVendors`, `$bottleneckStats`, `$staggingList`, `$agingBuckets`, `$agingExcluded`, `$returCount`, `$attentionShipments`, `$recentShipments`, `$vendorStats`, `$latestManifest`, `$earliestManifest`, `$lastSync`, `$dbTotal`, `$sourceStats`, `$koliRows`, `$totalKoli`), `routes/web.php` (`route('sync')`, `route('login')`), API `GET /api/shipments/staging/{stagging}`, `GET /api/shipments/bottleneck?provinsi=...`

## Ringkasan

Halaman dashboard operasional utama. Menampilkan KPI (hanya resi valid), tren harian (Chart.js), komposisi status/SLA/BAST, top provinsi/vendor, pipeline stagging, bottleneck over SLA per provinsi, aging/perlu perhatian, pengiriman terbaru, serta sumber data sinkronisasi. Memiliki toggle SLA Vendor (sheet) / Customer (tanggal), splash intro, filter range (All/30/90), tombol sync, dan modal detail staging/bottleneck via Alpine.js.

## Peran dalam Sistem

Lapisan presentasi (View). Menerima data agregat dari `DashboardController`, merender Blade + Tailwind, menambahkan interaksi ringan dengan Alpine.js, dan visualisasi dengan Chart.js. Interaksi modal memanggil API JSON (tanpa reload halaman).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `x-data="introController()"` | `dashboard.blade.php:3` | Kontrol splash intro (sessionStorage `anl_intro_shown`, skip, animasi stagger). |
| Splash intro | `dashboard.blade.php:8-38` | Overlay loading dengan progress bar dan tombol Lewati. |
| Header + range/SLA filter + sync | `dashboard.blade.php:42-98` | Periode, link range 0/30/90, toggle SLA `vendor`/`customer`, catatan fallback, tombol sync admin, info sync terakhir. |
| Source sync info card | `dashboard.blade.php:96-195` | Menampilkan perbandingan baris GSheet vs DB, selisih, duplikat/no-resi, detail malformed/duplicates (accordion). |
| KPI cards | `dashboard.blade.php:198-392` | Total/Completed/On Delivery/Hold/Undelivered/Within SLA/Over SLA/SLA Ach. Rate/Median Durasi, progress completion, split out-sla aktif/selesai. |
| Trend chart | `dashboard.blade.php:395-415`, `:1071-1075` | Canvas `#trendChart`, label/data dari `$trend['labels'|'kirim'|'selesai'|'outSla'|'compliance']`. |
| Doughnut charts | `dashboard.blade.php:418-475` | `#statusChart`, `#slaChart`, `#bastChart`, `#financeChart`. |
| Top charts | `dashboard.blade.php:478-501` | `#provinsiChart`, `#vendorChart`. |
| Vendor table | `dashboard.blade.php:504-555` | Tabel kinerja vendor (`$vendorStats`) dengan on-time, median/p90 lead time. |
| Stagging pipeline | `dashboard.blade.php:561-587` | List `$staggingList`, tombol `@click="openStaging(...)"` dengan `data-stage`, `data-total`. |
| Bottleneck | `dashboard.blade.php:590-621` | List `$bottleneckStats` dengan bar progress, tombol `@click="openBottleneck(...)"`. |
| Perlu perhatian (aging) | `dashboard.blade.php:624-720` | Bucket `$agingBuckets`, `$agingExcluded`, tabel `$attentionShipments`. |
| Pengiriman terbaru | `dashboard.blade.php:722-813` | Tabel `$recentShipments` (8 baris). |
| Modal detail (Alpine) | `dashboard.blade.php:815-893` | Modal `x-show="modalOpen"`, tabel `modalRows` dengan `x-for="row in modalRows" :key="row.id"`, state loading/error/empty. |
| Intro JS | `dashboard.blade.php:927-949` | `Alpine.data('introController', ...)`. |
| Modal JS | `dashboard.blade.php:950-1060` | `Alpine.data('modalController', ...)` dengan `openStaging/openBottleneck/loadModal`, `slaVerifiable/slaVerdict`, `statusClass`. |
| Chart JS (DOM ready) | `dashboard.blade.php:1062-1196` | Render semua Chart.js dari data JSON Blade. |

## Alur Data

```text
Route GET / (auth) -> DashboardController@index
  -> query agregat (KPI, tren, stagging, bottleneck, vendor, aging, recent, sourceStats)
  -> pass variabel ke view('dashboard', [...])
View: render Blade, Alpine mount introController() dan modalController() di scope
  -> Chart.js inisialisasi dari data JSON (@json)
Interaksi:
  - klik range -> link dengan ?range
  - klik sync (admin) -> POST /sync
  - klik stage -> Alpine openStaging -> fetch GET /api/shipments/staging/{stage}?range=... (same-origin, credentials)
  - klik bottleneck -> Alpine openBottleneck -> fetch GET /api/shipments/bottleneck?provinsi=...&range=...
  - 401 -> redirect ke route('login'); error array -> modalError
```

Variabel kunci digunakan langsung di Blade (kondisi, angka, link) dan di-inject ke JS via `@json(...)`.

## State Alpine (modal, filter, loading, error, chart)

**introController** (`dashboard.blade.php:929-947`):
- `splash: false` (awalnya), diset true jika tidak `prefers-reduced-motion` dan belum `sessionStorage.anl_intro_shown`
- `init()`: set flag, tampil splash 3 detik, tambahkan `intro-active` ke `document.documentElement` lalu hapus
- `skip()`: sembunyikan splash, hapus `intro-active`

**modalController** (`dashboard.blade.php:952-1059`):
- `modalOpen`, `modalTitle`, `modalSubtitle`, `modalRows` (array), `modalRowsFound` (number), `modalLoading`, `modalError`
- `statusClass(status)`: mapping status akhir -> kelas badge
- `slaMode`: `vendor` atau `customer` dari controller
- `rowSlaVerdict(row)`: mode vendor membaca `sla_vendor_result`; mode customer membandingkan `completed_date <= sla_due_date`
- `rowSlaBadgeClass/Label`: styling badge berdasarkan verdict mode aktif
- `openStaging(stage,total)`: bangun URL `/api/shipments/staging/{encoded stage}?range={@json($range)}`, panggil `loadModal`
- `openBottleneck(provinsi,total)`: bangun URL `/api/shipments/bottleneck?provinsi={encoded}&range=...`, panggil `loadModal`
- `loadModal(title,subtitle,url,foundTotal)`: reset state, buka modal, `fetch` dengan `credentials: 'same-origin'`, `headers: {Accept:'application/json'}`. Jika status 401 atau URL mengarah ke `/login` -> `window.location.href = route('login')` dan throw 'unauthenticated'. Jika !r.ok -> throw. Jika JSON bukan array -> throw 'Respons tidak valid'. Hasil di-set ke `modalRows`, `modalLoading=false`. Catch: kosongkan rows, set `modalError='Gagal memuat data. Silakan coba lagi.'` kecuali unauthenticated.

Chart.js: diinisialisasi di `DOMContentLoaded` (trend, status, SLA, bast, finance, provinsi, vendor). Data diambil dari Blade via `@json`.

## Relasi route/controller/API

- Route: `GET /` -> `DashboardController@index` (middleware `auth`).
- Tombol sync: `POST /sync` -> closure/handler dengan middleware `auth`,`admin` (hanya tampil jika `auth()->user()?->isAdmin()`).
- Modal staging: `GET /api/shipments/staging/{stagging}` -> `ShipmentApiController@staging` (auth). Mengembalikan JSON array dengan kolom termasuk `id`, `no_resi`, `is_duplicate_no_resi`, dll (lihat controller).
- Modal bottleneck: `GET /api/shipments/bottleneck?provinsi=...` -> `ShipmentApiController@bottleneck` (auth), memerlukan query `provinsi` (422 jika kosong). Menggunakan `outSla()` scope.
- Link login saat 401: `route('login')`.

## Catatan Teknis

- **Verdict SLA di modal**: mode vendor memakai `sla_vendor_result`; mode customer menghitung ulang `completed <= due` seperti scope server. URL bottleneck menyertakan `sla_mode` agar konsisten dengan KPI.
- **Key modal**: menggunakan `row.id` pada `x-for` (API mengirim `id`).
- **Range**: filter 0/30/90 dikirim via query link dan juga dikirim ke API modal sebagai `?range=...`.
- **Tren harian**: `$trend` sudah berisi array siap-pakai; Volume Kirim dari tanggal manifest, sedangkan Volume Selesai/Out SLA/Compliance dari tanggal selesai. Sumbu berisi hari kosong dengan nilai 0 (compliance `null`), sehingga jarak waktu tidak dimampatkan.
- **Sumber data card**: menghitung diff `sourceRows - dbTotal`, menampilkan info duplikat/malformed.
- **Aging**: bucket dihitung di controller; `$agingExcluded` dijelaskan (resi aktif tanpa tanggal manifest).
- **Vendor**: tabel menampilkan on-time hanya jika `onTimePct` tidak null (ada ambang SLA terverifikasi).
- **Stagger animation**: CSS class `stagger-ready` + `intro-active` dengan delay bertahap.
- **Empty states**: berbagai pesan "Belum ada data...".
- **x-cloak**: digunakan untuk splash dan modal.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa verdict SLA di modal staging/bottleneck dihitung di frontend?
**Jawab:** API mengembalikan data mentah; frontend memilih `sla_vendor_result` pada mode vendor atau membandingkan `completed_date` vs `sla_due_date` pada mode customer. Dengan begitu modal konsisten dengan mode KPI yang sedang aktif.

**Tanya:** Bagaimana cara kerja tombol "Sync Sekarang"?
**Jawab:** Hanya muncul untuk admin (`auth()->user()?->isAdmin()`), mengirim POST ke `route('sync')` dengan CSRF. Tombol berada di header dashboard.

**Tanya:** Apa yang terjadi jika modal API mengembalikan 401?
**Jawab:** Frontend mendeteksi status 401 atau redirect ke `/login`, lalu mengarahkan ke halaman login (`window.location.href = route('login')`).

**Tanya:** Mengapa ada filter range 0/30/90 dan dari mana dasarnya?
**Jawab:** Filter diterapkan via query string, dikirim ke controller dan juga ke API modal. Controller menghitung berdasarkan data terakhir yang tepercaya (sesuai prinsip sistem).

**Tanya:** Apa arti badge "duplikat" di tabel?
**Jawab:** Menandakan `is_duplicate_no_resi = true` (resi muncul lebih dari sekali di sheet) - baris tetap disimpan, ditandai bukan digabung.

## File Terkait

- [00-ringkasan-arsitektur.md](../../00-ringkasan-arsitektur.md)
- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [03-peta-route-dan-halaman.md](../../03-peta-route-dan-halaman.md)
- [04-istilah-dan-rumus.md](../../04-istilah-dan-rumus.md)
- [app/Http/Controllers/DashboardController.php](../../app/Http/Controllers/DashboardController.php.md)
- [app/Http/Controllers/ShipmentApiController.php](../../app/Http/Controllers/ShipmentApiController.php.md)
- [routes/web.php](../../routes/web.php.md)
- [resources/js/app.js](../js/app.js.md)
- [resources/css/app.css](../css/app.css.md)
