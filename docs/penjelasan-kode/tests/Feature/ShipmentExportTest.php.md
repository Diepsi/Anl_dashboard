# Feature/ShipmentExportTest

> **Path:** `tests/Feature/ShipmentExportTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Ekspor Excel pengiriman
> **Memanggil:** `App\Models\Shipment`, `App\Models\User`, `PhpOffice\PhpSpreadsheet\Reader\Xlsx`

## Ringkasan

Menguji ekspor Excel: verdict SLA dihitung ulang vs teks sheet, label "Tidak terverifikasi" untuk baris tanpa ambang, catatan cakupan mengikuti filter, kolom baru (KOLI, Tgl HO SarTrans, Ambang SLA), harga kosong tidak jadi 0, penandaan duplikat, dan route terautentikasi.

## Peran dalam Sistem

Memastikan file ekspor bisa dipakai sebagai pengganti sheet sumber - nilai konsisten dengan perhitungan sistem, ada catatan cakupan.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `export()` | `tests/Feature/ShipmentExportTest.php:23-37` | actingAs user POST export, baca file dari response, load spreadsheet, hapus file, return worksheet aktif |
| `rows()` | `tests/Feature/ShipmentExportTest.php:39-56` | Ambil baris data (skip header, skip baris kosong) |
| `shipment()` | `tests/Feature/ShipmentExportTest.php:58-66` | Helper buat shipment |
| `test_export_writes_the_recomputed_verdict_next_to_the_sheet_text()` | `tests/Feature/ShipmentExportTest.php:68-87` | BOMA out SLA tapi sheet "Meet SLA" -> export menulis "Out SLA" di kolom Status SLA, teks sheet tetap ada di "Verdict Sheet" |
| `test_export_labels_unverifiable_rows_instead_of_leaving_them_blank()` | `tests/Feature/ShipmentExportTest.php:89-112` | Tanpa ambang -> "Tidak terverifikasi", ada catatan menyebut "dihitung ulang" dan persentase cakupan |
| `test_coverage_note_counts_only_the_rows_in_the_file()` | `tests/Feature/ShipmentExportTest.php:114-158` | Cakupan dihitung dari baris di file hasil filter, bukan seluruh tabel |
| `test_export_carries_the_new_source_columns()` | `tests/Feature/ShipmentExportTest.php:160-178` | Kolom KOLI, Tgl HO dari SarTrans, Ambang SLA (hari) ter-export |
| `test_missing_prices_stay_empty_rather_than_becoming_zero()` | `tests/Feature/ShipmentExportTest.php:180-191` | Harga kosong tetap null/empty, bukan 0 |
| `test_export_marks_duplicate_receipt_rows()` | `tests/Feature/ShipmentExportTest.php:193-204` | Kolom "Duplikat Resi" berisi Ya/Tidak |
| `test_the_export_route_is_authenticated()` | `tests/Feature/ShipmentExportTest.php:206-209` | POST tanpa auth -> redirect login |

## Alur Arrange-Act-Assert

**Verdict dihitung ulang:** buat shipment BOMA dengan sla_due_date 04/12, completed 05/12, sla_result 'Meet SLA' -> export: Status SLA 'Out SLA', Verdict Sheet tetap 'Meet SLA'

**Tidak terverifikasi:** tanpa sla_due_date -> Status SLA 'Tidak terverifikasi', catatan ada

**Cakupan per filter:** 3 baris tersebar provinsi, export all -> 100% (3/3), export filter Bali -> 100% (1/1)

**Kolom baru:** set koli, tgl_ho_sartrans, sla_threshold_days -> ter-export dengan nilai benar

**Harga:** tanpa harga -> null di kolom Harga/Shipment

**Duplikat:** buat dengan is_duplicate_no_resi true/false -> Ya/Tidak

**Auth:** POST tanpa auth -> redirect login

## Relasi

- Route: `POST /pengiriman/export` (named `shipments.export`)
- Controller: ShipmentsController/export
- Model/tabel: `shipments`
- Export: PhpSpreadsheet
- Test: Ekspor

## Catatan Teknis

- File export dibaca dari `$response->baseResponse->getFile()->getPathname()` karena streaming download bisa kosong body.
- Verdict SLA dihitung ulang oleh sistem (konsisten dengan DashboardMetricIntegrityTest), teks asli sheet tetap disimpan di kolom terpisah.
- Catatan cakupan menampilkan persentase baris terverifikasi dari **baris di file ini**.
- Harga tidak di-zero-fill (penting - menghindari rata-rata salah).
- Kolom duplikat mencerminkan flag `is_duplicate_no_resi`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa menyimpan teks verdict sheet terpisah?  
**Jawab:** Untuk audit/perbandingan - bisa dilihat perbedaan antara perhitungan sheet vs perhitungan sistem.

**Tanya:** Bagaimana cakupan SLA dihitung di export?  
**Jawab:** Hanya baris yang punya ambang terverifikasi (sla_due_date valid + completed_date) masuk pembilang/penyebut.

**Tanya:** Kenapa harga tidak boleh jadi 0?  
**Jawab:** Banyak baris tanpa harga di sumber (28k+); mengisi 0 akan merusak statistik/rata-rata.

## File Terkait

- [tests/Feature/DashboardMetricIntegrityTest.php.md](DashboardMetricIntegrityTest.php.md)
- [tests/Feature/ShipmentSyncMappingTest.php.md](ShipmentSyncMappingTest.php.md)
- [tests/Feature/SlaScopeConsistencyTest.php.md](SlaScopeConsistencyTest.php.md)
- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
