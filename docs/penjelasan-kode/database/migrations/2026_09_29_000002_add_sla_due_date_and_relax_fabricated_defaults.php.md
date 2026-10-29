# 2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php

> **Path:** `database/migrations/2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (penyatuan SLA dan penghapusan nilai karangan)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`, `Illuminate\Support\Facades\DB`

## Ringkasan

Perubahan penting: rename `aging` menjadi `sla_threshold_days` (karena isinya ambang hari, bukan umur), tambah `sla_due_date` untuk menyatukan dua format SLA (tanggal atau jumlah hari), dan longgarkan beberapa kolom menjadi nullable dengan menghapus nilai default karangan.

## Peran dalam Sistem

Lapisan database/migrasi. Memperbaiki logika SLA agar benar untuk semua vendor dan menghilangkan distorsi data.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` - rename | `2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php:35-37` | `Schema::table` rename `aging` -> `sla_threshold_days`. |
| `up()` - add sla_due_date | `:39-41` | Tambah `sla_due_date` nullable after `sla` + index. |
| `up()` - relax columns | `:43-48` | `nama_funder`, `sla_threshold_days`, `status_akhir`, `status_instalasi` (20), `harga_per_shipment` diubah jadi nullable. |
| `down()` - revert | `:51-58` | Drop index `sla_due_date`, drop kolom, rename kembali `sla_threshold_days` -> `aging`. |
| `down()` - fill defaults | `:63-67` | Isi nilai NULL dengan default lama: `nama_funder`='Panthera', `aging`=0, `status_akhir`='On Process', `status_instalasi`='BELUM', `harga_per_shipment`=0. |
| `down()` - enforce not null | `:69-75` | Kembalikan default dan `nullable(false)`. |

## Alur Data

```text
up: rename aging->sla_threshold_days + tambah sla_due_date + longgarkan nullable
-> aplikasi bisa menghitung ulang SLA dari sla_due_date
down (destruktif): isi NULL dengan nilai karangan -> kembalikan constraint lama
```

## Relasi

- Route: -
- Model/tabel: `shipments`
- View: dashboard (SLA), shipments (filter SLA)
- Service: `App\Services\ShipmentSyncService`
- Test: -

## Catatan Teknis

- Migration ini sangat penting. Komentar menjelaskan masalah: kolom `SLA` di sumber bisa berupa tanggal atau jumlah hari; kode lama hanya menerima angka 1-365 sehingga ~28k baris non-BOMA kehilangan ambang SLA.
- `sla_due_date` menjadi satu kolom untuk verdict dihitung ulang.
- `sla_threshold_days` adalah ambang hari (bukan umur) — nama lama menyesatkan label "Aging".
- Menghapus nilai karangan (0, BELUM, On Process) mencegah distorsi rata-rata harga dan salah tafsir status.
- **Rollback bersifat destruktif**: mengembalikan nilai karangan yang sengaja dihapus.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa `sla_due_date` diperlukan?  
**Jawab:** Untuk menyatukan dua format SLA dari sumber (tanggal batas langsung atau jumlah hari dijangkarkan ke `tgl_ho_sartrans`) agar verdict bisa dihitung ulang konsisten.

**Tanya:** Apa risiko rollback migrasi ini?  
**Jawab:** Mengisi kembali nilai karangan (0, BELUM, 'On Process', 'Panthera') yang tidak bisa dibedakan dari data asli, sehingga bisa merusak analisis — hanya untuk dev.

## File Terkait

- [00-ringkasan-arsitektur.md](../../00-ringkasan-arsitektur.md)
- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [02-skema-database.md](../../02-skema-database.md)
- [2026_09_29_000001_add_tgl_ho_sartrans_and_koli_to_shipments_table.php.md](2026_09_29_000001_add_tgl_ho_sartrans_and_koli_to_shipments_table.php.md)
