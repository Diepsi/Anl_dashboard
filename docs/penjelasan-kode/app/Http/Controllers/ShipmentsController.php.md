# app/Http/Controllers/ShipmentsController.php

> **Path:** `app/Http/Controllers/ShipmentsController.php`  
> **Jenis:** Controller  
> **Dipakai oleh:** Route `/pengiriman`, view `shipments.blade.php`, tombol export  
> **Memanggil:** `App\Models\Shipment`, `App\Services\ShipmentSyncService` (tidak dipakai langsung kecuali kemungkinan; di index ada parameter tapi tidak digunakan), `Illuminate\Http\Request`, PhpSpreadsheet (Spreadsheet/Xlsx), model scopes (`outSla`, `meetSla`)

## Ringkasan

Halaman daftar pengiriman dengan filter (status, provinsi, stagging, SLA vendor/customer, resi valid/tanpa resi/semua, pencarian), pagination 25, serta export Excel `.xlsx`. Default hanya menampilkan resi valid; filter audit bisa menampilkan baris tanpa resi. Pencarian mencakup `no_resi`, `nama_sekolah`, `nama_penerima` dengan escaping LIKE.

## Peran dalam Sistem

Lapisan HTTP untuk browsing data operasional detail. Menyediakan ekspor untuk pelaporan dengan penegasan bahwa "Status SLA" dihitung ulang, berbeda dari "Verdict Sheet".

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `index(Request $request, ShipmentSyncService $service)` | `ShipmentsController.php:18-62` | Bangun query terfilter via `filteredQuery()`, urut manifest desc + id desc, paginate 25 `withQueryString()`, ambil opsi filter (distinct) |
| `export(Request $request)` | `ShipmentsController.php:64-282` | Export Excel: definisi kolom, chunk 1000, render nilai, styling header/border/striped/auto-filter/freeze, meta filter + catatan SLA |
| `filteredQuery(Request $request)` | `ShipmentsController.php:284-336` | Query builder dengan filter resi (`valid` default), status/provinsi/stagging, SLA vendor/customer, pencarian LIKE dengan escape |

## Alur Data

```text
GET /pengiriman?status=...&provinsi=...&stagging=...&sla=vendor_over|vendor_meet|customer_out|customer_meet&resi=valid|invalid|all&search=... 
-> filteredQuery() -> orderByDesc(tanggal_manifest,id) -> paginate(25)->withQueryString() 
-> load distinct options -> view shipments

POST /pengiriman/export (dengan filter sama) 
-> filteredQuery() chunk 1000 -> buat Spreadsheet, set header+styling, isi baris (date/number/boolean/null handling), auto-filter/freeze, meta + catatan, save ke temp, download + deleteFileAfterSend
```

## Relasi

- Route: `/pengiriman`, export route
- Model/tabel: `App\Models\Shipment` -> `shipments`
- View: `resources/views/shipments.blade.php`
- Service: `ShipmentSyncService` (diterima di index tapi tidak dipakai)
- Test: -

## Catatan Teknis

### Filter
- `status` -> `where('status_akhir', $status)`
- `provinsi` -> `where('provinsi', $provinsi)`
- `stagging` -> `where('stagging', $stagging)`
- `resi` -> `valid` default (`hasValidResi()`), `invalid` hanya baris `no_resi` NULL/kosong, `all` tanpa filter
- `sla` -> `vendor_over`/`vendor_meet` memakai label `RESULT FOR VENDOR`; `customer_out`/`customer_meet` (alias lama `out`/`meet`) memakai scope tanggal
- `search` -> trim, escape `\ % _`, cari di `no_resi` OR `nama_sekolah` OR `nama_penerima` dengan `like %...%`

### Export Excel
- **Kolom**: termasuk `is_duplicate_no_resi`, `sla_verdict`, `sla_result`, `sla_vendor_days`, dan `sla_vendor_result`. `sla_verdict` adalah verdict customer hasil hitung ulang; `sla_vendor_result` adalah label vendor dari sheet.
- **Handling nilai**:
  - DateTime -> setValue object + format `YYYY-MM-DD` + tracking panjang
  - `sla_verdict === null` -> diisi `'Tidak terverifikasi'` (mencegah kosong yang sulit dibedakan)
  - `is_duplicate_no_resi` -> `'Ya'/'Tidak'`
  - `null`/`''` -> null
  - `harga_per_shipment` -> cast float + format `#,##0.00`
  - Numeric lain -> right align
- **Styling**: header cyan `FF0891B2` putih bold center, freeze `A2`, auto-filter seluruh tabel, striping baris genap `FFF8FAFC`, border thin `FFCBD5E1`, kolom auto-width `min(len+3,50)`
- **Chunking**: 1000 baris per chunk untuk menghemat memori
- **Meta**: baris setelah data berisi timestamp + jumlah baris + filter yang dipakai; baris berikutnya berisi catatan penjelasan perbedaan `Status SLA` vs `Verdict Sheet`, persentase tercakup, dll. Catatan ini eksplisit sesuai prinsip sistem.
- **File**: nama `pengiriman_YYYYmmdd_His.xlsx`, tulis ke temp, download lalu hapus setelah kirim.

### Lainnya
- `index()` menerima `ShipmentSyncService $service` di signature tapi tidak menggunakannya (kemungkinan sisa/refactor) - tidak mempengaruhi perilaku.
- Urutan default `tanggal_manifest desc, id desc`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa perbedaan kolom "Status SLA" dan "Verdict Sheet" di export?  
**Jawab:** "Status SLA" adalah verdict yang dihitung ulang dari `completed_date` vs `sla_due_date` (berdasarkan ambang nyata setelah normalisasi saat sync). "Verdict Sheet" adalah teks apa adanya dari sumber (`sla_result`). Baris tanpa ambang/tanggal selesai muncul sebagai "Tidak terverifikasi". Catatan ini dicetak di baris meta file. (`ShipmentsController.php:93-95, 143-148, 262-272`)

**Tanya:** Bagaimana filter pencarian bekerja?  
**Jawab:** Mencari partial match (LIKE) di `no_resi`, `nama_sekolah`, `nama_penerima`. Karakter `%`, `_`, `\` di-escape untuk mencegah wildcard tak disengaja. (`ShipmentsController.php:308-317`)

**Tanya:** Mengapa menggunakan chunking saat export?  
**Jawab:** Untuk menghindari memory exhaustion saat mengekspor data dalam jumlah besar; diproses per 1000 baris. (`ShipmentsController.php:130-170`)

**Tanya:** Apakah bisa export semua data tanpa pagination?  
**Jawab:** Ya, endpoint export memakai `filteredQuery()` tanpa paginate, mengalirkan seluruh hasil sesuai filter via chunk. (`ShipmentsController.php:66-69`)

## File Terkait

- [DashboardController](DashboardController.php.md)
- [ShipmentApiController](ShipmentApiController.php.md)
- [01-alur-data-dan-sinkronisasi.md](../../../01-alur-data-dan-sinkronisasi.md)
- [02-skema-database.md](../../../02-skema-database.md)
- [Shipment Model](../../Models/Shipment.php.md)
- [ShipmentSyncService](../../Services/ShipmentSyncService.php.md)
