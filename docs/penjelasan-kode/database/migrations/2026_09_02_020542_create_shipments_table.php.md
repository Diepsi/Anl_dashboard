# 2026_09_02_020542_create_shipments_table.php

> **Path:** `database/migrations/2026_09_02_020542_create_shipments_table.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (skema awal shipments)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`

## Ringkasan

Membuat tabel `shipments` skema awal. Berisi identitas resi, lokasi, vendor/funder, tanggal, aging/status, harga, dan indeks penting untuk query dashboard.

## Peran dalam Sistem

Lapisan database/migrasi. Menjadi tabel operasional utama hasil sinkronisasi dari Google Sheets.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` - create shipments | `2026_09_02_020542_create_shipments_table.php:14-41` | Membuat tabel dengan kolom identitas, lokasi, vendor, tanggal, status, harga, timestamps. |
| Identitas | `:16-19` | `no_resi` string unique + index, `nomor_redock`, `delivery_order`, `nama_sekolah` + index. |
| Lokasi | `:20-24` | `nama_penerima`, `provinsi` nullable + index, `daerah`, `kota_kabupaten`, `kecamatan`. |
| Vendor/funder | `:25-28` | `vendor_mm`, `kode_funder`, `nama_funder` + index, `vendor_lm` nullable + index. |
| Tanggal/status | `:29-36` | `tanggal_manifest` + index, `completed_date` + index, `tgl_sampai_kota_tujuan`, `aging` default 0, `status_akhir` default 'On Process' + index, `status_instalasi` varchar(20) default 'BELUM', `harga_per_shipment` decimal(15,2) default 0, `status_invoice` nullable. |
| Indeks komposit | `:39-40` | Index (`nama_funder`, `tanggal_manifest`), (`nama_funder`, `status_akhir`). |
| `down()` | `:47-50` | Drop `shipments`. |

## Alur Data

```text
migrate -> buat shipments (skema awal) -> kemudian dimodifikasi oleh migrasi bertahap
```

## Relasi

- Route: -
- Model/tabel: `shipments`
- View: dashboard, shipments, clustering
- Service: `App\Services\ShipmentSyncService`
- Test: -

## Catatan Teknis

- Skema awal mengasumsikan `no_resi` unik dan beberapa kolom wajib (defaults) seperti `aging` 0, `status_akhir` 'On Process', `status_instalasi` 'BELUM', `harga_per_shipment` 0, `nama_funder` diindeks (belum nullable di skema ini).
- Migrasi bertahap kemudian mengubah asumsi ini (lihat 000002-000009) karena realita data berbeda dari skema awal.
- `down()` drop tabel secara destruktif.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa ada banyak indeks di tabel `shipments`?  
**Jawab:** Untuk performa filter/pencarian dan agregasi dashboard (provinsi, vendor_lm, tanggal_manifest, completed_date, funder, status_akhir).

**Tanya:** Apakah skema awal masih utuh saat ini?  
**Jawab:** Tidak. Banyak kolom ditambah/diubah/dilonggarkan oleh migrasi setelahnya (stagging/sla, bast, tgl_ho_sartrans/koli, sla_due_date, duplikasi no_resi, perluasan status_instalasi).

## File Terkait

- [02-skema-database.md](../../02-skema-database.md)
- [2026_09_02_030346_add_stagging_sla_to_shipments.php.md](2026_09_02_030346_add_stagging_sla_to_shipments.php.md)
- [2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php.md](2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php.md)
- [2026_09_29_000003_allow_duplicate_no_resi_and_mark_them.php.md](2026_09_29_000003_allow_duplicate_no_resi_and_mark_them.php.md)
- [2026_10_27_000001_widen_status_instalasi_on_shipments_table.php.md](2026_10_27_000001_widen_status_instalasi_on_shipments_table.php.md)
