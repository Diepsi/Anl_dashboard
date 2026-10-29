# app/Services/ShipmentSyncService.php

> **Path:** `app/Services/ShipmentSyncService.php`  
> **Jenis:** Service  
> **Dipakai oleh:** `app/Console/Commands/SyncShipments.php`, `routes/console.php` (scheduler), `routes/web.php` (tombol "Sync Sekarang" via controller), `database/seeders/ShipmentSeeder.php`  
> **Memanggil:** `App\Models\Shipment`, `Illuminate\Support\Carbon`, `Illuminate\Support\Facades\Cache`, `Illuminate\Support\Facades\DB`, `Illuminate\Support\Facades\Http`, `SplFileObject`

## Ringkasan

`ShipmentSyncService` bertanggung jawab mengunduh CSV publik Google Sheets (sheet "ANL Publikasi", vendor BOMA) dari `GOOGLE_SHEETS_CSV_URL`, memetakan header ke kolom database, menormalkan nilai (resi/ilmiah/ribuan, tanggal, SLA dua format), melakukan pre-flight guard, menulis data ke tabel `shipments` (upsert/replace), menandai duplikat resi, serta menyimpan cache statistik/fingerprint/sync terakhir.

## Peran dalam Sistem

Berada di lapisan **Service/Domain**. Orkestrasi sinkronisasi sumber eksternal (CSV) ke model/database, dengan aturan bisnis ketat (anti-sheet-kosong/menyusut, normalisasi berlapis, SLA dua format, penanganan resi rusak/NULL, deduplikasi berbasis flag).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `$columnMap` | `ShipmentSyncService.php:14-46` | Pemetaan header sumber ke key internal dengan banyak alias (No Resi, Tanggal HO ke Vendor/Manifest, Kabupaten/Kota/Kota/Kab Tujuan, Update Stagging/Status update, SLA/SLA for DB, `SLA FOR VENDOR`, `RESULT FOR VENDOR`, dll). |
| Konstanta cache/fingerprint | `ShipmentSyncService.php:46-50` | `LAST_SYNC_CACHE_KEY`, `SOURCE_STATS_CACHE_KEY`, `SOURCE_FINGERPRINT_CACHE_KEY`. |
| `MIN_ROW_RATIO`, `FORMULA_ERRORS` | `ShipmentSyncService.php:57,65` | Guard rasio (0.5) dan daftar penanda error formula Excel. |
| `lastSyncedAt()` / `sourceStats()` | `ShipmentSyncService.php:66-78` | Akses cache. |
| `sync(bool $replace=false, bool $force=false)` | `ShipmentSyncService.php:80-128` | Baca URL, hitung fingerprint (sha256) -> jika beda force replace, download via HTTP (timeout 120), simpan temp, panggil `importFromFile` dalam transaksi, tulis cache (fingerprint/stats/last_synced_at), return jumlah di-import. |
| `importFromFile(string $file, bool $replace, bool $force=false)` | `ShipmentSyncService.php:130-223` | Cari header (wajib ada kolom "No Resi"), pre-flight (`countImportableRows` + `assertImportIsSafe`), bangun rows via `buildRows`, hapus baris lama sesuai mode (replace=delete all; else hapus per unique resi + hapus semua `no_resi NULL`), insert per 500 via `flush`, `markDuplicateNoResi`, kembalikan stats (truncate contoh duplikat/malformed ke 50). |
| `buildRows(string $file, array $index)` | `ShipmentSyncService.php:231-302` | Lewati header, iterasi baris, buang baris kosong/template (`isEmptyRow` + `isEmptyShipment`), `mapRow`, hitung raw/valid/duplikat/malformed, normalisasi resi, akumulasi statistik. |
| `countImportableRows(string $file, array $index)` | `ShipmentSyncService.php:309-338` | Hitung baris importable tanpa menyentuh DB (persis filter build). |
| `isEmptyShipment(array $data)` | `ShipmentSyncService.php:347-364` | Abaikan `no_resi`, `sla_vendor_days`, `sla_vendor_result`; jika semua field lain null/empty -> true (baris template dilewati). |
| `isEmptyRow(array $row)` | `ShipmentSyncService.php:362-371` | Semua sel kosong setelah trim -> true. |
| `uniqueResi(array $rows)` | `ShipmentSyncService.php:373-384` | Kumpulkan unique resi (non-null). |
| `markDuplicateNoResi(array $duplicateResi)` | `ShipmentSyncService.php:392-399` | Set semua `is_duplicate_no_resi=false`, lalu set true per chunk untuk resi yang muncul >1x. |
| `assertImportIsSafe(int $incoming, int $existing, bool $force)` | `ShipmentSyncService.php:409-434` | Tolak jika incoming==0 atau (existing>0 && incoming < floor(existing*0.5)); lewati jika force. |
| `buildHeaderIndex(array $row)` | `ShipmentSyncService.php:436-447` | Lowercase header non-kosong. |
| `mapRow(array $row, array $index)` | `ShipmentSyncService.php:451-535` | Map semua kolom via `pick`, termasuk `sla_vendor_days`/`sla_vendor_result`; normalisasi `status_invoice` (TRUE/PAID -> PAID else null), resolusi SLA dua format, hitung `sla_due_date`. |
| `pick(array $row, array $index, array $headers)` | `ShipmentSyncService.php:527-550` | Pilih nilai non-kosong pertama dari daftar alias; simpan fallback pertama jika semua kosong. |
| `flush(array $chunks)` | `ShipmentSyncService.php:552-562` | Tambah `created_at/updated_at` jika belum ada, `Shipment::insert($chunks)`. |
| `nullableString($value)` | `ShipmentSyncService.php:564-571` | Trim -> null jika kosong. |
| `cleanText($value)` | `ShipmentSyncService.php:579-588` | `nullableString` lalu tolak jika `isFormulaError` -> null. |
| `isFormulaError(string $text)` | `ShipmentSyncService.php:590-604` | Cek substring marker error; deteksi notasi ilmiah `^\d+(?:,\d+)?E\+\d+$/i`. |
| `toDate($value)` | `ShipmentSyncService.php:612-635` | Hanya terima d/m/Y atau Y-m-d dengan `checkdate()` valid. |
| `normalizeResi(string $raw)` | `ShipmentSyncService.php:652-704` | Terima resi alfanumerik BOMA (`26P` + 12-15 digit), notasi ilmiah, pemisah ribuan+desimal, atau pemisah umum; selain itu wajib 13-18 digit. |
| `toIntOrNull($value)`, `toSlaDays`, `toThresholdDays` | `ShipmentSyncService.php:696-737` | `toSlaDays` 1-365, `toThresholdDays` 1-3650. |
| `toDecimalOrNull($value)` | `ShipmentSyncService.php:744-757` | Hanya numeric valid, formula error ditolak -> null (tidak jadi 0). |

## Alur Data (input -> proses -> output)

```text
Input: GOOGLE_SHEETS_CSV_URL (CSV publik)
  -> sync(): fingerprint URL (sha256) vs cache; jika beda -> replace=true; HTTP GET timeout 120
  -> importFromFile(): baca header (wajib "No Resi"), pre-flight countImportableRows + assertImportIsSafe(force)
    -> buildRows(): skip empty/template, mapRow, normalizeResi (ilmiah/ribuan), klasifikasi duplikat/malformed
    -> hapus baris lama (replace all / per unique resi + semua no_resi NULL)
    -> flush per 500 dalam DB::transaction
    -> markDuplicateNoResi
  -> cache: SOURCE_FINGERPRINT_CACHE_KEY, SOURCE_STATS_CACHE_KEY, LAST_SYNC_CACHE_KEY
Output: jumlah baris di-import (int); stats ter-cache
```

**Detail penting:**
- **Fingerprint URL:** jika URL berubah, mode replace dipaksa (mencegah mencampur sheet lama/baru). L70-93, L123.
- **Guard 0/50%:** `assertImportIsSafe` menolak incoming==0 atau existing>0 dan incoming < floor(existing*0.5); bisa dilewati `--force`. L409-434.
- **Mapping header:** `columnMap` punya alias; `buildHeaderIndex` lowercase; `pick` ambil non-kosong pertama. L14-44, L436-550.
- **Baris template:** `isEmptyShipment` menganggap baris hanya dengan nomor urut/flag formula sebagai dibuang, dan tidak menghitung kolom SLA vendor sebagai bukti shipment. Baris dengan resi rusak tapi ada data shipment disimpan dengan `no_resi=NULL`. L233-364.
- **Normalisasi resi:** menerima format alfanumerik BOMA (`26P296011490159`), notasi ilmiah (`1,01E+15`), ribuan/desimal (`1.009.400.821.166.140,00`), atau 13-18 digit; `#N/A`/formula -> null. L652-704.
- **SLA vendor:** `SLA FOR VENDOR` -> `sla_vendor_days`, `RESULT FOR VENDOR` -> `sla_vendor_result`; dipakai dashboard dan filter halaman pengiriman.
- **Tanggal:** `toDate` pakai `checkdate()` (menolak 31/02). L612-635.
- **SLA dua format:** jika `sla` berupa tanggal -> `sla_due_date = tanggal`. Jika berupa hari (1-365, `toSlaDays`) -> hitung dari `tgl_ho_sartrans + hari` HANYA jika anchor <= completed_date atau completed_date null; jika anchor > completed_date -> `sla_due_date` dibiarkan NULL (mencegah selalu Meet SLA). L467-491, L713-722.
- **Duplikat:** semua baris tetap disimpan; `markDuplicateNoResi` menandai semua baris dengan resi kembar (`is_duplicate_no_resi=true`). L392-399.
- **Transaksi + chunking:** seluruh import dalam `DB::transaction`, insert/delete per 500. L105, L190-198.
- **Cache:** stats (raw/skipped_empty/skipped_malformed/valid/imported/dup_extra/unique/duplicates/malformed_resi, contoh dipotong 50) disimpan. L111-128.

## Relasi

- **Route:** dipanggil via `php artisan shipments:sync` (console), scheduler `Schedule::command('shipments:sync')->hourly()`, dan tombol "Sync Sekarang" `POST /sync` (auth+admin) di web.
- **Model/tabel:** `App\Models\Shipment` -> tabel `shipments` (banyak kolom; `no_resi` nullable, `is_duplicate_no_resi`, `sla_due_date`, dll).
- **View:** mempengaruhi data di dashboard, halaman pengiriman, export Excel (data hasil sync).
- **Service/Command:** digunakan oleh `App\Console\Commands\SyncShipments`.
- **Test:** perilaku penting terkait sync/guard/normalisasi tercermin di test suite (berdasarkan pola proyek).

## Catatan Teknis, Edge Case, Keputusan Desain

- **Fingerprint wajib:** pergantian URL memaksa replace untuk mencegah data bercampur.
- **Pre-flight prevent data loss:** `countImportableRows` menghitung "baris data shipment" (bukan raw CSV) dan `assertImportIsSafe` menolak sebelum menghapus tabel.
- **Baris template:** Google Sheets sering baris nomor urut+FALSE -> dibuang.
- **Resi rusak tapi ada data:** disimpan dengan `no_resi=NULL` (tetap dihitung sebagai baris; tidak bisa jadi key duplikat).
- **Normalisasi ilmiah/ribuan:** Sheets kadang menampilkan 15-16 digit sebagai notasi ilmiah atau format ribuan dengan koma desimal -> dipulihkan ke digit murni.
- **SLA anchor strict:** menggunakan `tgl_ho_sartrans` sebagai anchor untuk SLA hari (divalidasi cocok dengan verdict sheet); jika anchor > completed_date, deadline dibiarkan NULL (baris tidak bisa dinilai).
- **`is_formula_error`:** menolak `#N/A`, `#VALUE!`, dll dan bentuk E+ untuk menghindari menyimpan hasil formula rusak.
- **Duplikat dipertahankan:** semua baris disimpan, hanya ditandai; alasan: tidak bisa membedakan mana "asli" dari batch/formula.
- **NULL harga:** `toDecimalOrNull` tidak mengubah jadi 0 (hanya terisi ~2.392/31k baris).
- **Chunking & transaction:** aman untuk volume besar.
- **Stats dipotong:** duplikat/malformed hanya disimpan contoh max 50 di cache.
- **Mode non-replace:** hapus per unique resi yang muncul + hapus semua `no_resi NULL` (mencegah penumpukan baris NULL).

## Kemungkinan Pertanyaan Penguji + Jawaban

**Tanya:** Mengapa saat URL Google Sheets diganti aplikasi otomatis truncate+import ulang?  
**Jawab:** Karena `sync()` menghitung fingerprint `sha256` URL; jika berbeda dari cache, `$replace` dipaksa true (L91-93). Tujuannya mencegah data sheet lama tercampur dengan sheet baru.

**Tanya:** Bagaimana menangani resi dalam notasi ilmiah atau format ribuan?  
**Jawab:** `normalizeResi()` lebih dulu menerima pola alfanumerik BOMA (`26P296011490159`), lalu mendeteksi pola `E+` atau pola ribuan/desimal (mis. `1.009.400.821.166.140,00`), menggabungkan digit, dan mewajibkan panjang 13-18 digit (L652-704).

**Tanya:** Kapan baris SLA hari tidak dihitung deadline-nya?  
**Jawab:** Jika SLA berupa angka hari dan `tgl_ho_sartrans > completed_date` (anchor lebih baru dari selesai), kode tidak membuat `sla_due_date` (dibiarkan NULL) agar baris tidak otomatis dianggap Meet SLA (L484-491).

**Tanya:** Bagaimana proteksi agar sync tidak menghapus semua data karena sheet kosong/terfilter?  
**Jawab:** `countImportableRows()` menghitung baris data shipment yang layak, `assertImportIsSafe()` menolak jika incoming==0 atau existing>0 dan incoming < 50% existing; bisa dilewati dengan `--force` (L309-434).

## File Terkait

- [SyncShipments Command](../Console/Commands/SyncShipments.php.md)
- [Shipment Model](../Models/Shipment.php.md)
- [01-alur-data-dan-sinkronisasi](../../01-alur-data-dan-sinkronisasi.md)
- [02-skema-database](../../02-skema-database.md)
- [04-istilah-dan-rumus](../../04-istilah-dan-rumus.md)
