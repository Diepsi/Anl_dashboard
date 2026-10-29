# Feature/ShipmentSyncGuardTest

> **Path:** `tests/Feature/ShipmentSyncGuardTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Sinkronisasi - pre-flight guard
> **Memanggil:** `App\Models\Shipment`, `App\Services\ShipmentSyncService`, `Illuminate\Support\Facades\Http`

## Ringkasan

Mengunci pre-flight guard untuk mencegah bencana: CSV hanya header/tidak ada data valid tidak boleh mengosongkan tabel. Juga menguji penolakan sheet menyusut drastis, baris tanpa resi disimpan sebagai NULL, baris template dibuang, `--force` melewati guard, dan sync gagal tidak mengupdate `last_synced_at`.

## Peran dalam Sistem

Guard ini krusial karena `replace` mengosongkan tabel. Tanpa guard, sheet salah bisa mengosongkan dashboard.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `setUp()` | `tests/Feature/ShipmentSyncGuardTest.php:31-36` | Set config URL |
| `csv()` | `tests/Feature/ShipmentSyncGuardTest.php:39-48` | Helper CSV |
| `resi()` | `tests/Feature/ShipmentSyncGuardTest.php:51-54` | Resi valid |
| `seedShipments()` | `tests/Feature/ShipmentSyncGuardTest.php:56-65` | Seed data |
| `fakeCsv()` | `tests/Feature/ShipmentSyncGuardTest.php:67-70` | Http::fake |
| `test_header_only_csv_never_deletes_existing_data()` | `tests/Feature/ShipmentSyncGuardTest.php:72-85` | Seed 3, CSV hanya header -> exception '0 baris data valid', tabel tetap 3 |
| `test_csv_without_a_header_is_rejected()` | `tests/Feature/ShipmentSyncGuardTest.php:87-99` | CSV kosong -> exception, tabel tetap |
| `test_rows_without_a_usable_receipt_are_stored_with_null_resi()` | `tests/Feature/ShipmentSyncGuardTest.php:101-122` | `#N/A`, scientific kecil, `123` -> semua disimpan dengan no_resi NULL (3 baris) |
| `test_rows_without_shipment_data_are_not_imported()` | `tests/Feature/ShipmentSyncGuardTest.php:124-138` | Baris template (hanya No/CLOSE STATUS FALSE) dibuang, hanya 1 baris valid diimport |
| `test_template_rows_do_not_defeat_the_shrink_guard()` | `tests/Feature/ShipmentSyncGuardTest.php:140-161` | 10 existing, CSV punya 1 valid + 20 template rows -> shrink guard menolak (turun drastis) |
| `test_a_shrunken_sheet_is_rejected_before_the_table_is_cleared()` | `tests/Feature/ShipmentSyncGuardTest.php:163-176` | 10 existing, 1 valid -> ditolak sebelum tabel dikosongkan |
| `test_a_failed_sync_leaves_the_last_synced_timestamp_alone()` | `tests/Feature/ShipmentSyncGuardTest.php:178-193` | Sync gagal -> `lastSyncedAt()` masih null |
| `test_force_allows_a_deliberately_smaller_sheet()` | `tests/Feature/ShipmentSyncGuardTest.php:195-204` | Dengan `force: true`, shrink diperbolehkan -> tabel diganti jadi 1 baris |
| `test_a_healthy_sheet_is_still_replaced_normally()` | `tests/Feature/ShipmentSyncGuardTest.php:206-226` | existing 4, incoming 6 (>=50%) -> replace normal, tabel jadi 6 |

## Alur Arrange-Act-Assert

**Header only:** seed 3 -> fake header only -> expect exception mengandung '0 baris data valid' -> count tetap 3

**Tanpa header:** fake '' -> exception, count tetap

**Resi tak bisa dipulihkan:** 3 baris invalid resi -> sync berhasil, count 3, semua NULL

**Baris template:** ada baris No,,,FALSE -> tidak diimport, hanya baris valid

**Shrink guard:** existing 10, valid 1 -> exception 'turun drastis', count tetap 10

**Force:** sama tapi force true -> count jadi 1

**Gagal sync:** sync gagal -> lastSyncedAt null

**Sehat:** existing 4, valid 6 -> replace normal

## Relasi

- Service: `App\Services\ShipmentSyncService` (`sync()`, `countImportableRows()`, `assertImportIsSafe()`, `lastSyncedAt()`)
- Model/tabel: `shipments`
- HTTP: fake
- Cache: `last_synced_at`

## Catatan Teknis

- Guard menghitung baris yang **benar-benar berisi data shipment** (bukan template). Template seperti `No,,,FALSE` dibuang oleh logika filter.
- Kondisi penolakan: incoming valid == 0 ATAU (existing > 0 dan incoming < existing * 0.5).
- Guard berjalan **sebelum** tabel dikosongkan/diperbarui - penting.
- `force: true` melewati guard untuk kasus disengaja.
- Sync gagal tidak mengupdate timestamp `last_synced_at` (mencegah dashboard berpikir data baru).
- Baris dengan resi tidak bisa dipulihkan disimpan sebagai NULL (keputusan pemilik data) - berbeda dari baris template/header-only.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan guard aktif?  
**Jawab:** Saat sync, sebelum mengubah tabel. Terutama penting saat mode replace.

**Tanya:** Apa bedanya baris template vs baris tanpa resi valid?  
**Jawab:** Baris template (biasanya ada nomor urut tapi kolom kunci kosong/FALSE) **tidak diimport**. Baris yang punya data shipment tapi resi rusak **tetap diimport** dengan `no_resi = NULL`.

**Tanya:** Mengapa 50% threshold?  
**Jawab:** Untuk mendeteksi shrink drastis (sheet terfilter/salah tab/terhapus). Bisa dilewati dengan `--force`.

## File Terkait

- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [04-istilah-dan-rumus.md](../../04-istilah-dan-rumus.md)
- [tests/Feature/ShipmentDuplicateMarkingTest.php.md](ShipmentDuplicateMarkingTest.php.md)
- [tests/Feature/ShipmentSyncMappingTest.php.md](ShipmentSyncMappingTest.php.md)
