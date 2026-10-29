# 2026_09_29_000003_allow_duplicate_no_resi_and_mark_them.php

> **Path:** `database/migrations/2026_09_29_000003_allow_duplicate_no_resi_and_mark_them.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (penanganan duplikasi resi)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`, `Illuminate\Support\Facades\DB`

## Ringkasan

Mengizinkan `no_resi` bisa NULL dan tidak unik, menambahkan kolom `is_duplicate_no_resi` (boolean default false). Tujuannya agar semua baris sheet masuk ke database (termasuk baris kembar) dengan penanda duplikasi, bukan digabung.

## Peran dalam Sistem

Lapisan database/migrasi. Menjaga integritas jumlah baris sumber sesuai permintaan pemilik data.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` - drop index unik/indeks | `2026_09_29_000003_allow_duplicate_no_resi_and_mark_them.php:22-31` | Mencoba drop `shipments_no_resi_unique` dan `shipments_no_resi_index` (try/catch untuk kompatibilitas driver). |
| `up()` - allow null | `:33-35` | `no_resi` diubah menjadi nullable. |
| `up()` - add flag | `:37-39` | Tambah `is_duplicate_no_resi` boolean default false after `no_resi`. |
| `down()` - drop flag | `:44-46` | Drop kolom flag. |
| `down()` - prune data | `:50-61` | Hapus baris `no_resi` NULL atau bukan representatif (simpan hanya MIN(id) per `no_resi` yang tidak null) per chunk 1000. |
| `down()` - restore unique | `:63-66` | `no_resi` menjadi not null dan unique kembali. |

## Alur Data

```text
up: hapus constraint unik -> no_resi nullable + flag
   -> service menandai duplikat via markDuplicateNoResi() saat sync
down (destruktif): hapus baris tanpa resi/kembar -> kembalikan unique
```

## Relasi

- Route: -
- Model/tabel: `shipments`
- View: dashboard/shipments (menampilkan info duplikat jika perlu)
- Service: `App\Services\ShipmentSyncService` (markDuplicateNoResi, penanganan baris tanpa resi)
- Test: -

## Catatan Teknis

- Komentar migration menjelaskan latar belakang: batch "1,01E+15" sulit dibedakan string-nya sehingga baris kembar digabung secara tidak sengaja di skema unik; pemilik data ingin semua 31.709 baris masuk.
- Baris template/resi rusak bisa disimpan dengan `no_resi = NULL` (sesuai 01-alur-data-dan-sinkronisasi).
- **Rollback sangat destruktif**: menghapus semua baris `no_resi` NULL dan semua duplikat kecuali satu per grup.
- `markDuplicateNoResi()` di service mengisi flag untuk semua baris dengan `no_resi` yang muncul > 1 kali.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa tidak menggabungkan resi kembar?  
**Jawab:** Agar jumlah baris sumber tidak hilang sesuai keputusan pemilik data. Semua baris tetap disimpan dan ditandai `is_duplicate_no_resi = true`.

**Tanya:** Apa risiko rollback migrasi ini?  
**Jawab:** Kehilangan data baris tanpa resi dan baris duplikat — hanya aman di development.

## File Terkait

- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [02-skema-database.md](../../02-skema-database.md)
- [2026_09_02_020542_create_shipments_table.php.md](2026_09_02_020542_create_shipments_table.php.md)
