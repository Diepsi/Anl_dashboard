# Feature/ShipmentDuplicateMarkingTest

> **Path:** `tests/Feature/ShipmentDuplicateMarkingTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Sinkronisasi shipment - penandaan duplikat resi
> **Memanggil:** `App\Models\Shipment`, `App\Services\ShipmentSyncService`, `Illuminate\Support\Facades\Cache`, `Illuminate\Support\Facades\Http`

## Ringkasan

Mengunci keputusan pemilik data: **semua baris disimpan**, resi yang muncul >1 kali ditandai `is_duplicate_no_resi = true` (bukan digabung/dibuang). Menguji marking, statistik, pembersihan baris NULL pada update, penanganan scientific notation, dan perilaku update sync.

## Peran dalam Sistem

Menjaga integritas jumlah baris sumber - penting agar tidak ada kehilangan data saat ada resi kembar atau format resi khusus.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `setUp()` | `tests/Feature/ShipmentDuplicateMarkingTest.php:25-37` | Set config URL, pasang fingerprint di cache agar tidak auto-replace |
| `csv()` | `tests/Feature/ShipmentDuplicateMarkingTest.php:39-47` | Helper buat CSV |
| `resi()` | `tests/Feature/ShipmentDuplicateMarkingTest.php:49-52` | Buat nomor resi 15 digit valid |
| `fakeCsv()` | `tests/Feature/ShipmentDuplicateMarkingTest.php:54-57` | Http::fake untuk URL |
| `test_rows_sharing_a_resi_are_all_stored_and_marked()` | `tests/Feature/ShipmentDuplicateMarkingTest.php:59-94` | 5 baris dengan duplikat -> 5 baris tersimpan, duplikat ditandai true, unik false |
| `test_stats_reflect_that_every_row_was_imported()` | `tests/Feature/ShipmentDuplicateMarkingTest.php:96-122` | Statistik mencerminkan raw/valid/skipped/imported/dup_extra/unique/duplicates/malformed |
| `test_null_receipt_rows_do_not_accumulate_on_update_syncs()` | `tests/Feature/ShipmentDuplicateMarkingTest.php:124-149` | Update sync (replace=false) menghapus baris NULL lama dan tidak menumpuk |
| `test_a_previously_merged_scientific_batch_comes_back_marked()` | `tests/Feature/ShipmentDuplicateMarkingTest.php:151-174` | Scientific notation `1,01E+15` dipulihkan jadi `1010000000000000`, 3 baris masuk dan ditandai duplikat |
| `test_a_smaller_update_keeps_old_rows_and_refreshes_overlapping_ones()` | `tests/Feature/ShipmentDuplicateMarkingTest.php:176-207` | replace=false: baris lama tidak dihapus, baris overlap diupdate, duplikat tetap ditandai |

## Alur Arrange-Act-Assert

**Semua baris disimpan:** CSV 5 baris (A,A,B,B,C) -> sync replace true -> count 5, semua A,B ditandai duplikat, C tidak

**Statistik:** ada baris dengan NULL dan `#N/A` -> stats menunjukkan skipped_empty/skipped_malformed, imported_rows = total baris yang masuk ke DB (termasuk NULL)

**NULL cleanup:** DB punya a dan 2 baris NULL -> sync update dengan a,b dan NULL baru -> total 3, hanya 1 NULL, a diupdate

**Scientific notation:** 3 baris `"1,01E+15"` (quoted) -> dipulihkan jadi digit penuh, semua ditandai duplikat

**Update sync:** DB punya c,d lama -> CSV kecil (a,a,b,c baru) -> total 5, c diupdate ke 'SD 3 baru', d tetap ada

## Relasi

- Service: `App\Services\ShipmentSyncService` (`sync()`, `markDuplicateNoResi()`, `sourceStats()`)
- Model/tabel: `shipments` (`no_resi`, `is_duplicate_no_resi`, `nama_sekolah`)
- Cache: `SOURCE_FINGERPRINT_CACHE_KEY`
- HTTP: Google Sheets CSV (fake)
- Test: Duplikat resi

## Catatan Teknis

- Fingerprint dipasang di setUp agar fingerprint sama dengan URL -> tidak memicu auto-replace.
- `markDuplicateNoResi()` menandai **semua** baris yang share resi yang sama muncul >1x.
- Pada update sync (replace=false), baris dengan `no_resi NULL` dihapus lalu di-import ulang dari CSV (mencegah akumulasi).
- Normalisasi resi menangani scientific notation dan pemisah ribuan (lihat ShipmentSyncMappingTest).
- Statistik `dup_extra = imported_rows - unique_rows` mencerminkan baris tambahan akibat duplikat.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa tidak menggabungkan baris duplikat?  
**Jawab:** Keputusan pemilik data - semua baris sheet harus disimpan, hanya ditandai. Ini menjaga jumlah baris sumber dan informasi asli.

**Tanya:** Bagaimana cara mendeteksi duplikat?  
**Jawab:** Berdasarkan nilai `no_resi` yang sama (setelah normalisasi); semua baris dengan resi tersebut diberi flag `true`.

**Tanya:** Apa yang terjadi dengan baris NULL pada update sync?  
**Jawab:** Dihapus agar tidak menumpuk, lalu di-import ulang dari sheet saat ini.

## File Terkait

- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [tests/Feature/ShipmentSyncMappingTest.php.md](ShipmentSyncMappingTest.php.md)
- [tests/Feature/ShipmentSyncGuardTest.php.md](ShipmentSyncGuardTest.php.md)
