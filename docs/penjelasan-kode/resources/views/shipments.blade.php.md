# resources/views/shipments.blade.php

> **Path:** `resources/views/shipments.blade.php`  
> **Jenis:** View (Blade + Alpine.js)  
> **Dipakai oleh:** Route `GET /pengiriman` melalui `ShipmentsController@index`, `POST /pengiriman/export`  
> **Memanggil:** `ShipmentsController` (mengirim variabel: `$shipments` paginated, `$statuses`, `$provinces`, `$staggingList`, `$status`, `$provinsi`, `$stagging`, `$sla`, `$search`), `routes/web.php` (`route('shipments')`, `route('shipments.export')`, `route('sync')`), API `GET /api/shipments/detail?no_resi=...`

## Ringkasan

Halaman daftar pengiriman lengkap. Menyediakan pencarian (no resi/sekolah/penerima), filter status/provinsi/stagging/SLA vendor-customer/resi, pagination, tombol export Excel (POST dengan filter yang sama), tombol sync untuk admin, serta modal detail resi via Alpine.js. Default hanya resi valid; baris tanpa resi dapat dibuka lewat filter audit dan diberi badge "tanpa resi".

## Peran dalam Sistem

Lapisan presentasi. Menampilkan tabel hasil filter dari controller, mengirim filter ke export, dan menyediakan drill-down detail via modal tanpa pindah halaman.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Header + Export + Sync | `shipments.blade.php:5-42` | Judul, tombol POST `route('shipments.export')` dengan hidden input filter, tombol sync admin. |
| Form filter | `shipments.blade.php:45-105` | Form GET dengan search, status, provinsi, stagging, SLA vendor/customer, dan resi valid/tanpa resi/semua. |
| Flash messages | `shipments.blade.php:97-101` | Menampilkan `session('status')`. |
| Tabel daftar shipment | `shipments.blade.php:120-200` | Kolom No Resi, Sekolah, Provinsi, Vendor LM, Tanggal HO, Stagging, Status Akhir, SLA Vendor, SLA Customer. Badge duplikat dan "tanpa resi" ditampilkan; baris hanya bisa diklik jika ada `no_resi`. Pagination `$shipments->links()`. |
| Modal detail resi | `shipments.blade.php:185-228` | Modal Alpine `x-show="open"`, menampilkan data dari `data`, field list via `fieldList()`. |
| Alpine detailController | `shipments.blade.php:232-299` | `openDetail(noResi)`, fetch `GET /api/shipments/detail?no_resi=...`, `fieldList()` memformat tanggal dan filter field kosong. |

## Alur Data

```text
Route GET /pengiriman (auth) -> ShipmentsController@index
  -> apply filter (search/status/provinsi/stagging/sla) -> paginate
  -> pass data + filter values ke view
View render form + tabel + modal
User interaksi:
  - submit form filter (GET) -> reload dengan query
  - klik Export Excel (POST) -> ShipmentsController@export dengan filter tersimpan di hidden input
  - klik baris dengan no_resi -> Alpine openDetail -> fetch /api/shipments/detail?no_resi=... (JSON)
  - admin klik Sync Sekarang -> POST /sync
```

## State Alpine (modal, filter, loading, error, chart)

**detailController** (`shipments.blade.php:233-298`):
- `open: false`, `loading: false`, `notFound: false`, `data: {}`
- `openDetail(noResi)`: buka modal, set loading true, reset notFound/data, fetch `/api/shipments/detail?no_resi=` + encodeURIComponent(noResi). Jika `!r.ok` set `notFound=true` dan return null. Jika ok set `data = row`. Catch set `notFound=true`. Finally `loading=false`.
- `fieldList()`: ambil `this.data`, jika tidak ada `no_resi` return []. `fmtDate(v)`: jika string YYYY-MM-DD ambil 10 char, else return as-is. Bangun array items (banyak field), filter hanya yang tidak null/undefined/kosong, map ke `{label,value:String(value)}`.

Filter bersifat form GET (state di URL/query).

## Relasi route/controller/API

- `GET /pengiriman` -> `ShipmentsController@index` (auth)
- `POST /pengiriman/export` -> `ShipmentsController@export` (auth)
- `GET /api/shipments/detail` -> `ShipmentApiController@detail` (auth). Butuh query `no_resi` (422 jika tidak ada menurut route notes).
- `POST /sync` -> admin only (tampil jika admin)

## Catatan Teknis

- **Clickable row**: hanya baris dengan `no_resi` yang bisa diklik (cek `@if ($ship->no_resi) @click="openDetail(...)" @endif`, class cursor-pointer).
- **Export**: form POST meng-copy semua filter saat ini (`status`, `provinsi`, `stagging`, `sla`, `resi`, `search`) ke hidden input agar export mengikuti filter aktif.
- **Badge duplikat/tanpa resi**: duplikat dari `is_duplicate_no_resi`; "tanpa resi" untuk baris audit dengan `no_resi` kosong.
- **Detail SLA**: modal menampilkan `SLA Customer (hari)`, `SLA Vendor (hari)`, `Status SLA Customer (tanggal)`, dan `Hasil SLA Vendor (sheet)` agar kedua sumber bisa dibandingkan.
- **Modal field filtering**: `fieldList()` hanya menampilkan field yang terisi (menghilangkan field kosong/null).
- **Tanggal**: diformat dengan memotong ke YYYY-MM-DD jika format tersebut.
- **Pagination**: `$shipments->links()` (default Laravel pagination).
- **Empty state**: "Belum ada data pengiriman." jika kosong.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana export Excel mengikuti filter?
**Jawab:** Form export POST mengirim hidden input berisi nilai filter saat ini (`status`, `provinsi`, `stagging`, `sla`, `resi`, `search`), sehingga `ShipmentsController@export` menggunakan filter yang sama dengan halaman.

**Tanya:** Kapan baris tabel bisa diklik untuk detail?
**Jawab:** Hanya jika `no_resi` ada. Baris dengan `no_resi` NULL tidak bisa diklik (tidak ada cursor-pointer dan tidak ada click handler).

**Tanya:** Apa yang ditampilkan di modal jika resi tidak ditemukan?
**Jawab:** Jika fetch mengembalikan response tidak ok, Alpine set `notFound=true` dan menampilkan "Resi tidak ditemukan." (div dengan `x-show="notFound && !loading"`).

**Tanya:** Field apa saja yang bisa muncul di modal detail?
**Jawab:** Banyak field shipment (sekolah, penerima, alamat/wilayah, vendor, nomor DO/redock, funder, tanggal, koli, status, stagging, SLA, harga, status invoice) - hanya yang terisi yang ditampilkan.

**Tanya:** Mengapa ada tombol sync di halaman pengiriman?
**Jawab:** Untuk kemudahan admin melakukan sync langsung dari halaman data pengiriman (konsisten dengan dashboard).

## File Terkait

- [03-peta-route-dan-halaman.md](../../03-peta-route-dan-halaman.md)
- [04-istilah-dan-rumus.md](../../04-istilah-dan-rumus.md)
- [app/Http/Controllers/ShipmentsController.php](../../app/Http/Controllers/ShipmentsController.php.md)
- [app/Http/Controllers/ShipmentApiController.php](../../app/Http/Controllers/ShipmentApiController.php.md)
- [routes/web.php](../../routes/web.php.md)
- [resources/views/dashboard.blade.php.md](dashboard.blade.php.md)
