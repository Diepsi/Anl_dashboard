# 2026_09_29_000001_add_tgl_ho_sartrans_and_koli_to_shipments_table.php

> **Path:** `database/migrations/2026_09_29_000001_add_tgl_ho_sartrans_and_koli_to_shipments_table.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (pemisahan first-mile dan koli)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`

## Ringkasan

Menambahkan `tgl_ho_sartrans` (date nullable) sebagai titik first-mile dari SarTrans dan `koli` (int nullable) sebagai jumlah koli. Menambahkan indeks pada `tgl_ho_sartrans`.

## Peran dalam Sistem

Lapisan database/migrasi. Memisahkan kejadian first-mile dari `tanggal_manifest` dan menyediakan data jumlah koli.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` | `2026_09_29_000001_add_tgl_ho_sartrans_and_koli_to_shipments_table.php:11-23` | Tambah `tgl_ho_sartrans` after `tanggal_manifest` (penjelasan pemisahan first-mile), tambah `koli` after `tanggal_manifest`, lalu tambah index `tgl_ho_sartrans`. |
| `down()` | `:25-31` | Drop index, drop kolom. |

## Alur Data

```text
migrate -> tambahkan kolom -> digunakan untuk anchor perhitungan SLA (jika SLA berupa hari)
```

## Relasi

- Route: -
- Model/tabel: `shipments`
- View: dashboard/shipments
- Service: `App\Services\ShipmentSyncService`
- Test: -

## Catatan Teknis

- Komentar migration menjelaskan alasan pemisahan: `tanggal_manifest` vs `tgl_ho_sartrans` adalah kejadian berbeda.
- `tgl_ho_sartrans` menjadi anchor saat kolom `sla` berisi jumlah hari (lihat 01-alur-data-dan-sinkronisasi).
- Indeks ditambahkan terpisah setelah menambah kolom.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa perlu `tgl_ho_sartrans` terpisah dari `tanggal_manifest`?  
**Jawab:** Untuk menghitung deadline SLA dengan benar ketika SLA berupa jumlah hari (dijangkarkan ke titik first-mile SarTrans), bukan ke `tanggal_manifest`.

**Tanya:** Apa fungsi `koli`?  
**Jawab:** Menyimpan jumlah koli dari sumber (nullable, tidak di-karang).

## File Terkait

- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [02-skema-database.md](../../02-skema-database.md)
- [2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php.md](2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php.md)
