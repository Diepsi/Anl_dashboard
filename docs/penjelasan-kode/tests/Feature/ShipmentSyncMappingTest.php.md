# Feature/ShipmentSyncMappingTest

> **Path:** `tests/Feature/ShipmentSyncMappingTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Sinkronisasi - pemetaan header dan normalisasi
> **Memanggil:** `App\Services\ShipmentSyncService` (via reflection untuk method internal)

## Ringkasan

Mengunci aturan mapping/normalisasi yang mencegah angka palsu: `tanggal_manifest` tidak boleh mewarisi `Tgl HO dari Sartrans`, penanganan SLA dua bentuk (tanggal vs hari), ambang di kolom `Aging`, tanggal mustahil ditolak, sel kosong jadi NULL, error formula dibuang, harga tidak di-zero-fill, koli angka, dan normalisasi resi (termasuk scientific notation).

## Peran dalam Sistem

Menjaga integritas data import - mencegah bug historis (2392 baris salah tanggal manifest, 28320 baris kehilangan ambang SLA).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `service()` | `tests/Feature/ShipmentSyncMappingTest.php:35-38` | Instantiate service |
| `index()` | `tests/Feature/ShipmentSyncMappingTest.php:40-45` | Test `buildHeaderIndex()` via reflection |
| `map()` | `tests/Feature/ShipmentSyncMappingTest.php:50-64` | Bangun row dari headers, panggil `mapRow()` via reflection |
| `normalizeResi()` | `tests/Feature/ShipmentSyncMappingTest.php:66-71` | Test `normalizeResi()` via reflection |
| `test_manifest_does_not_borrow_the_sartrans_handover_date()` | `tests/Feature/ShipmentSyncMappingTest.php:73-85` | Jika hanya ada Tgl HO Sartrans, tanggal_manifest NULL; tgl_ho_sartrans terisi |
| `test_manifest_uses_the_vendor_handover_when_present()` | `tests/Feature/ShipmentSyncMappingTest.php:87-96` | Prioritaskan 'Tanggal HO ke Vendor' |
| `test_sla_only_survives_as_a_plausible_day_count()` | `tests/Feature/ShipmentSyncMappingTest.php:98-106` | SLA angka 1-365 valid, serial Excel (45940), 0, 400 ditolak -> NULL |
| `test_a_date_shaped_sla_becomes_the_deadline()` | `tests/Feature/ShipmentSyncMappingTest.php:113-119` | SLA berisi tanggal -> jadi sla_due_date, sla tetap NULL |
| `test_a_day_shaped_sla_is_anchored_to_the_sartrans_handover()` | `tests/Feature/ShipmentSyncMappingTest.php:121-131` | SLA hari + anchor -> sla_due_date dihitung |
| `test_a_day_threshold_without_its_anchor_gets_no_deadline()` | `tests/Feature/ShipmentSyncMappingTest.php:133-138` | Tanpa anchor -> NULL |
| `test_an_anchor_later_than_completion_gets_no_deadline()` | `tests/Feature/ShipmentSyncMappingTest.php:140-152` | Anchor > completed -> NULL (mencegah selalu Meet SLA) |
| `test_the_threshold_column_keeps_values_above_365()` | `tests/Feature/ShipmentSyncMappingTest.php:154-162` | Kolom Aging bisa 400 -> sla_threshold_days 400, walau SLA biasa dibatasi 365 |
| `test_empty_cells_are_stored_as_null_instead_of_invented_values()` | `tests/Feature/ShipmentSyncMappingTest.php:164-175` | Kosong -> NULL (bukan "On Process"/"Panthera"/"BELUM"/0) |
| `test_formula_errors_are_discarded_rather_than_stored_as_status()` | `tests/Feature/ShipmentSyncMappingTest.php:177-191` | #N/A, #VALUE! dll dibuang jadi NULL |
| `test_prices_are_not_zero_filled()` | `tests/Feature/ShipmentSyncMappingTest.php:193-198` | Harga valid disimpan, kosong/ilmiah tidak jadi 0 |
| `test_impossible_calendar_dates_are_rejected()` | `tests/Feature/ShipmentSyncMappingTest.php:200-205` | 31/02/2026 ditolak, 28/02 valid |
| `test_koli_is_imported_as_a_number()` | `tests/Feature/ShipmentSyncMappingTest.php:207-211` | koli jadi integer/number, kosong NULL |
| `test_malformed_resi_values_are_rejected()` | `tests/Feature/ShipmentSyncMappingTest.php:213-224` | Resi valid 13-18 digit, ditolak jika salah format |
| `test_scientific_notation_is_recovered_as_resi()` | `tests/Feature/ShipmentSyncMappingTest.php:232-241` | 1,01E+15, 1.009.400.821.166.140,00 dipulihkan, eksponen kecil ditolak |

## Alur Arrange-Act-Assert

**Manifest:** map dengan Tgl HO Vendor kosong tapi Tgl HO Sartrans ada -> tanggal_manifest null, tgl_ho_sartrans terisi

**SLA dua bentuk:** SLA '25/08/2025' -> sla null, sla_due_date terisi. SLA '17' + anchor -> sla_due_date dihitung

**Anchor rules:** tanpa anchor atau anchor>completed -> sla_due_date null

**Threshold terpisah:** Aging '400' disimpan di sla_threshold_days

**Kosong & error:** semua jadi null

**Tanggal invalid:** checkdate mencegah

**Resi:** scientific notation dipulihkan dengan logika khusus

## Relasi

- Service: `App\Services\ShipmentSyncService` (private methods diuji via reflection)
- Model/tabel: `shipments`
- Test: Mapping/normalisasi

## Catatan Teknis

- Menggunakan reflection karena method seperti `buildHeaderIndex`, `mapRow`, `normalizeResi` bersifat internal/service.
- `tanggal_manifest` TIDAK boleh fallback ke `Tgl HO dari Sartrans` (bug yang menimpa 2392 baris Panthera).
- SLA bisa berupa tanggal atau hari - keduanya valid dan harus dipetakan ke `sla_due_date`.
- Serial Excel (angka besar seperti 45940) ditolak untuk kolom SLA (bukan jumlah hari valid).
- `sla_threshold_days` dari kolom `Aging` bisa >365 (beda dari batas SLA biasa).
- `checkdate()` digunakan untuk validasi tanggal.
- Normalisasi resi menangani notasi ilmiah dengan koma/titik dan pemisah ribuan.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa tanggal_manifest tidak boleh mewarisi Sartrans?  
**Jawab:** Kedua kolom punya arti berbeda; fallback salah menyebabkan tanggal manifest salah dan merusak lead time/aging.

**Tanya:** Bagaimana membedakan SLA berupa tanggal vs hari?  
**Jawab:** Diuji/dideteksi oleh logika mapping - jika berbentuk tanggal valid, diperlakukan sebagai deadline; jika angka dalam rentang hari, dihitung dari anchor.

**Tanya:** Kenapa anchor lebih baru dari completed dibiarkan NULL?  
**Jawab:** Jika dibuat deadline karangan, baris bisa salah dianggap Meet SLA. Lebih aman menjadi tidak terverifikasi.

## File Terkait

- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [04-istilah-dan-rumus.md](../../04-istilah-dan-rumus.md)
- [tests/Feature/DashboardMetricIntegrityTest.php.md](DashboardMetricIntegrityTest.php.md)
- [tests/Feature/SlaScopeConsistencyTest.php.md](SlaScopeConsistencyTest.php.md)
- [tests/Feature/ShipmentSyncGuardTest.php.md](ShipmentSyncGuardTest.php.md)
