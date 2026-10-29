# 2026_09_03_000001_add_bast_columns_to_shipments_table.php

> **Path:** `database/migrations/2026_09_03_000001_add_bast_columns_to_shipments_table.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (penambahan data BAST)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`

## Ringkasan

Menambahkan kolom terkait BAST: `bast_tgl_balik`, `bast_tgl_ke_finance`, `bast_keterangan` ke tabel `shipments`.

## Peran dalam Sistem

Lapisan database/migrasi. Mendukung pelacakan proses BAST.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` | `2026_09_03_000001_add_bast_columns_to_shipments_table.php:11-16` | Tambah ketiga kolom nullable setelah `sla_result`. |
| `down()` | `:18-23` | Drop ketiga kolom. |

## Alur Data

```text
migrate -> tambahkan kolom BAST -> siap disimpan dari hasil sync jika tersedia di sheet
```

## Relasi

- Route: -
- Model/tabel: `shipments`
- View: shipments (detail/tabel)
- Service: `App\Services\ShipmentSyncService`
- Test: -

## Catatan Teknis

- Semua nullable (sesuai prinsip tidak mengarang nilai).
- Posisi kolom disesuaikan dengan alur migrasi bertahap.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah kolom BAST wajib?  
**Jawab:** Tidak; nullable dan hanya diisi jika ada di sumber data.

## File Terkait

- [02-skema-database.md](../../02-skema-database.md)
- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
