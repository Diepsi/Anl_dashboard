# Feature/SlaScopeConsistencyTest

> **Path:** `tests/Feature/SlaScopeConsistencyTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Konsistensi SLA di seluruh panel/modal/filter
> **Memanggil:** `App\Models\Shipment`, `App\Models\User`, `Illuminate\Support\Carbon`

## Ringkasan

Mengunci konsistensi perhitungan SLA: kedua bentuk deadline (hari+anchor BOMA, tanggal langsung vendor lain) dihitung benar, verdict dihitung ulang (bukan copy sheet), baris tanpa ambang/tanpa completed_date tidak terverifikasi, konsistensi antara dashboard panel, bottleneck/staging modal, filter shipments page, penanganan Retur di attention panel, dan baris tanpa resi mendapat id unik di modal.

## Peran dalam Sistem

Memastikan tidak ada perbedaan hitungan antara tempat berbeda (dulu terjadi inkonsistensi). Sangat penting karena 28320 baris non-BOMA kehilangan ambang.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `shipment()` | `tests/Feature/SlaScopeConsistencyTest.php:29-37` | Helper buat shipment |
| `bomaWithDays()` | `tests/Feature/SlaScopeConsistencyTest.php:40-50` | Helper BOMA style (sla hari + anchor) |
| `vendorWithDueDate()` | `tests/Feature/SlaScopeConsistencyTest.php:53-61` | Helper vendor lain (sla_due_date langsung) |
| `test_a_date_based_deadline_is_verified()` | `tests/Feature/SlaScopeConsistencyTest.php:63-78` | Kedua bentuk dihitung, dashboard menghitung 1 meet + 1 out |
| `test_a_day_threshold_is_verified_against_its_anchor()` | `tests/Feature/SlaScopeConsistencyTest.php:80-87` | 17 hari dari 08/10 -> due 25/10, selesai 27/10 = Out SLA |
| `test_verdict_is_recomputed_rather_than_copied_from_the_sheet()` | `tests/Feature/SlaScopeConsistencyTest.php:89-99` | sla_result 'Meet SLA' tapi dihitung ulang jadi 'Out SLA' |
| `test_a_day_threshold_without_its_anchor_is_not_verified()` | `tests/Feature/SlaScopeConsistencyTest.php:101-117` | Tanpa anchor -> slaCovered 0, slaUnverified 1 |
| `test_a_row_without_a_completed_date_stays_unverified()` | `tests/Feature/SlaScopeConsistencyTest.php:119-135` | Belum selesai -> tidak bisa dinilai, bukan Out SLA |
| `test_bottleneck_modal_lists_both_deadline_shapes()` | `tests/Feature/SlaScopeConsistencyTest.php:137-150` | Modal bottleneck menerima kedua bentuk |
| `test_bottleneck_modal_row_count_matches_the_panel_that_opened_it()` | `tests/Feature/SlaScopeConsistencyTest.php:152-173` | Jumlah baris modal = angka di panel |
| `test_bottleneck_modal_follows_the_range_switcher()` | `tests/Feature/SlaScopeConsistencyTest.php:175-207` | Range 30/90 konsisten |
| `test_staging_modal_follows_the_range_switcher()` | `tests/Feature/SlaScopeConsistencyTest.php:209-236` | Range konsisten untuk staging |
| `test_staging_modal_returns_a_unique_id_for_rows_without_a_resi()` | `tests/Feature/SlaScopeConsistencyTest.php:238-271` | Baris no_resi NULL tetap punya id unik |
| `test_bottleneck_modal_returns_a_unique_id_for_rows_without_a_resi()` | `tests/Feature/SlaScopeConsistencyTest.php:273-294` | Sama untuk bottleneck modal |
| `test_dashboard_staging_panel_includes_tiba_di_hub_destinasi()` | `tests/Feature/SlaScopeConsistencyTest.php:296-320` | Urutan stage termasuk 'TIBA DI HUB DESTINASI' |
| `test_an_unrecognised_range_falls_back_to_all_time()` | `tests/Feature/SlaScopeConsistencyTest.php:322-347` | Range tidak valid -> fallback semua waktu |
| `test_shipments_page_sla_filter_matches_the_dashboard_verdicts()` | `tests/Feature/SlaScopeConsistencyTest.php:349-378` | Filter sla=out/meet konsisten dengan dashboard |
| `test_returned_shipments_reach_the_attention_panel_with_an_empty_age()` | `tests/Feature/SlaScopeConsistencyTest.php:380-408` | Retur tanpa manifest tetap muncul, days_open null, tidak masuk bucket aging |
| `test_returned_shipments_are_not_crowded_out_by_older_aged_rows()` | `tests/Feature/SlaScopeConsistencyTest.php:410-438` | Retur tidak terdorong keluar oleh aging ordering |
| `test_a_row_without_a_deadline_is_labelled_unverified()` | `tests/Feature/SlaScopeConsistencyTest.php:440-475` | Label "Tidak terverifikasi", tidak pakai badge emerald, API mengembalikan sla_due_date null, sla_verdict null |

## Alur Arrange-Act-Assert

**Konsistensi bentuk SLA:** buat meet/out dengan dua cara -> verdict benar, dashboard hitung keduanya

**Hitung ulang:** force sla_result 'Meet SLA' pada baris out -> sla_verdict jadi Out SLA

**Tanpa ambang/completed:** tidak terverifikasi, tidak dihitung

**Modal konsistensi:** panel dan modal hitung sama, range konsisten

**Id unik:** 2 baris NULL + 1 baris punya resi -> 3 id unik

**Staging order:** panel menampilkan urutan lengkap termasuk TIBA DI HUB DESTINASI

**Range fallback:** range invalid -> tetap muncul semua waktu

**Filter halaman shipments:** filter match dashboard

**Retur:** tanpa manifest -> muncul di attention, days_open null, agingExcluded bertambah

**Ordering:** 30 aged rows + 1 retur tanpa tanggal -> retur tetap muncul

**UI/API:** tidak terverifikasi tampil benar, API detail konsisten

## Relasi

- Route: `GET /`, `GET /pengiriman`, `GET /api/shipments/bottleneck`, `GET /api/shipments/staging/{stagging}`, `GET /api/shipments/{no_resi}/detail`
- Model/tabel: `shipments`, `users`
- Model: `Shipment` (verdict, scopes)
- Controller: DashboardController, ShipmentsController, ShipmentApiController
- Test: Konsistensi SLA

## Catatan Teknis

- Kedua bentuk SLA harus disatukan: BOMA hari + anchor, vendor lain tanggal langsung.
- Verdict **dihitung ulang** dari data, bukan disalin dari sheet.
- Baris tanpa `sla_due_date` atau tanpa `completed_date` = tidak terverifikasi.
- Modal API harus mengembalikan `id` unik bahkan untuk baris `no_resi = NULL` (mencegah key duplikat di frontend).
- Range switcher: nilai tidak valid fallback ke semua waktu (tidak silently narrow).
- Retur bisa tanpa `tanggal_manifest` - tidak boleh difilter keluar, age dihitung sebagai unknown (NULL), tidak masuk bucket.
- Ordering attention panel perlu mempertimbangkan Retur (tidak hanya by age).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa ada inkonsistensi antar panel dulu?  
**Jawab:** Sebelum perbaikan, beberapa query hanya menerima SLA bentuk angka 1-365 atau hanya menghitung kolom tertentu; sekarang semua menggunakan logika terpadu.

**Tanya:** Bagaimana cara membedakan "tidak terverifikasi" vs "Meet/Out SLA"?  
**Jawab:** Jika tidak ada deadline atau tidak ada completed_date, tidak bisa diverifikasi -> "Tidak terverifikasi", tidak dihitung di persentase SLA.

**Tanya:** Kenapa modal perlu id unik untuk no_resi NULL?  
**Jawab:** Frontend pakai key (Vue/Alpine); duplikat key bisa merusak rendering. API harus menyediakan id unik.

**Tanya:** Mengapa Retur khusus ditangani?  
**Jawab:** Bisa tanpa tanggal manifest, tapi tetap perlu perhatian; tidak boleh disembunyikan atau dihitung age 0.

## File Terkait

- [00-ringkasan-arsitektur.md](../../00-ringkasan-arsitektur.md)
- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [04-istilah-dan-rumus.md](../../04-istilah-dan-rumus.md)
- [tests/Feature/DashboardMetricIntegrityTest.php.md](DashboardMetricIntegrityTest.php.md)
- [tests/Feature/ShipmentSyncMappingTest.php.md](ShipmentSyncMappingTest.php.md)
- [tests/Feature/ShipmentExportTest.php.md](ShipmentExportTest.php.md)
