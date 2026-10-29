# Feature/DashboardMetricIntegrityTest

> **Path:** `tests/Feature/DashboardMetricIntegrityTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Integritas metrik dashboard
> **Memanggil:** `App\Models\Shipment`, `App\Models\User`, `Illuminate\Support\Carbon`

## Ringkasan

Mengunci perbaikan dua bug dashboard: (1) rata-rata durasi negatif karena manifest > completed (tanggal tidak konsisten), (2) filter "last N days" yang salah anchor karena spike korup. Juga menguji vendor stats, SLA dengan dua bentuk kolom, dan cakupan SLA.

## Peran dalam Sistem

Menjaga konsistensi KPI dashboard sesuai prinsip di arsitektur (gunakan scope `withConsistentDates`, anchor range dari `completed_date` terakhir yang tepercaya, hitung ulang verdict SLA).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `shipment()` | `tests/Feature/DashboardMetricIntegrityTest.php:21-28` | Helper buat shipment |
| `seedScenario()` | `tests/Feature/DashboardMetricIntegrityTest.php:30-60` | Buat 3 valid (10 hari), 1 korup (manifest setelah completed), 1 aktif (tanpa completed_date) |
| `test_impossible_date_order_rows_are_excluded_from_date_aggregates()` | `tests/Feature/DashboardMetricIntegrityTest.php:62-67` | `withConsistentDates()` hanya menghitung 4 dari 5 |
| `test_duration_statistic_ignores_impossible_date_order_rows()` | `tests/Feature/DashboardMetricIntegrityTest.php:69-85` | Lead time: sample 3, median/p90 10.0, mean > 0 |
| `test_range_anchor_uses_latest_completion_not_the_corrupt_manifest_spike()` | `tests/Feature/DashboardMetricIntegrityTest.php:87-112` | Anchor dari max completed_date (2025-12-11), MID1 tidak masuk 30 hari tapi masuk 90 hari |
| `test_unfinished_shipments_stay_inside_every_range()` | `tests/Feature/DashboardMetricIntegrityTest.php:114-127` | ACTIVE1 tetap muncul di range 30 dan 90 |
| `test_vendor_table_reports_median_and_sample_instead_of_mean_aging()` | `tests/Feature/DashboardMetricIntegrityTest.php:129-145` | Vendor stats pakai medianLead + leadSample, tidak ada avgAging/avgLead |
| `test_on_time_counts_both_sla_column_shapes_and_only_those()` | `tests/Feature/DashboardMetricIntegrityTest.php:147-211` | Panthera (sla_due_date langsung) -> 100%, BOMA (hari + anchor) -> verdict dihitung ulang jadi Out SLA 0%, tanpa ambang -> null/0 |
| `test_dashboard_reports_how_much_of_the_data_the_sla_rate_covers()` | `tests/Feature/DashboardMetricIntegrityTest.php:213-229` | Tanpa ambang -> slaCovered 0 |

## Alur Arrange-Act-Assert

**Konsistensi tanggal:** seed 5 rows -> assert withConsistentDates count 4. Akses dashboard -> leadTime sample 3, median/p90 10, mean>0

**Range anchor:** tambah MID1 (completed 2025-10-01), pastikan max completed 2025-12-11 -> inRange(30) tidak berisi MID1, inRange(90) berisi MID1; ACTIVE1 selalu ada

**Vendor stats:** cek struktur menggunakan median/sample, tidak ada field mean aging

**SLA dua bentuk:** buat 3 kasus -> vendorStats sesuai (hitung ulang untuk BOMA), tanpa ambang null

**Coverage:** semua tanpa ambang -> slaCovered 0

## Relasi

- Route: `GET /` (dashboard)
- Model/tabel: `shipments`, `users`
- Model scope: `Shipment::withConsistentDates()`, `Shipment::inRange($days)`
- Controller: `DashboardController` (menghitung leadTime, vendorStats, sla)
- Test: Integritas metrik

## Catatan Teknis

- Scope `withConsistentDates()` membuang baris dengan urutan tanggal tidak konsisten (manifest > completed) - mencegah durasi negatif.
- `inRange()` meng-anchor pada `completed_date` terakhir yang tepercaya, bukan `today()`; baris belum selesai (tanpa completed_date) tetap dimasukkan.
- Lead time menggunakan median/P90, bukan mean (lebih stabil terhadap data korup).
- SLA: verdict dihitung ulang dari `completed_date` vs `sla_due_date`, bukan menyalin `sla_result` sheet. Kolom SLA bisa berupa tanggal atau hari (lihat ShipmentSyncMappingTest).
- Tanpa ambang (`sla_due_date` null) -> tidak masuk perhitungan SLA, ditampilkan sebagai tidak terverifikasi.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa baris korup bisa mempengaruhi rata-rata?  
**Jawab:** Jika dihitung tanpa filter konsistensi, `DATEDIFF(completed, manifest)` bisa negatif dan merusak rata-rata; test memaksa penggunaan scope konsisten.

**Tanya:** Kenapa anchor pakai completed_date terakhir?  
**Jawab:** Agar window filter tetap relevan meski data sheet belum diperbarui atau ada tanggal manifest di masa depan (korup). Sesuai prinsip arsitektur.

**Tanya:** Mengapa vendor stats pakai median bukan mean?  
**Jawab:** Lebih robust terhadap outlier/korupsi data; test menegaskan ini.

## File Terkait

- [00-ringkasan-arsitektur.md](../../00-ringkasan-arsitektur.md)
- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [04-istilah-dan-rumus.md](../../04-istilah-dan-rumus.md)
- [tests/Feature/SlaScopeConsistencyTest.php.md](SlaScopeConsistencyTest.php.md)
- [tests/Feature/ShipmentSyncMappingTest.php.md](ShipmentSyncMappingTest.php.md)
