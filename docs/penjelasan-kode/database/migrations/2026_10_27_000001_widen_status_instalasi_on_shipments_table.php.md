# 2026_10_27_000001_widen_status_instalasi_on_shipments_table.php

> **Path:** `database/migrations/2026_10_27_000001_widen_status_instalasi_on_shipments_table.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (perluasan kolom status_instalasi)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`, `Illuminate\Support\Facades\DB`

## Ringkasan

Memperlebar kolom `status_instalasi` dari varchar(20) menjadi varchar(255) karena sumber baru memiliki nilai lebih panjang (contoh "belum update pada TMS" 21 karakter) yang menyebabkan sync gagal di tengah transaksi.

## Peran dalam Sistem

Lapisan database/migrasi. Menjaga agar seluruh nilai sumber bisa masuk tanpa dipotong.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` | `2026_10_27_000001_widen_status_instalasi_on_shipments_table.php:18-21` | Ubah `status_instalasi` menjadi varchar(255) nullable. |
| `down()` | `:23-32` | Potong nilai > 20 karakter dengan `SUBSTR` lalu ubah menjadi varchar(20) nullable. |

## Alur Data

```text
up: perlebar kolom -> sync tidak gagal karena panjang string
down: potong nilai panjang -> kembali ke batas 20
```

## Relasi

- Route: -
- Model/tabel: `shipments`
- View: shipments
- Service: `App\Services\ShipmentSyncService`
- Test: -

## Catatan Teknis

- Komentar migration menjelaskan alasan: sync gagal di tengah transaksi karena panjang string.
- Down memotong data (destruktif) untuk memenuhi constraint lama.
- Kolom sudah menjadi nullable sejak migrasi 000002.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa perlu diperlebar?  
**Jawab:** Untuk mengakomodasi nilai `Status Instalasi` dari sumber yang lebih panjang dari 20 karakter tanpa memotong atau gagal saat import.

**Tanya:** Apa efek rollback?  
**Jawab:** Nilai lebih dari 20 karakter dipotong jadi 20 karakter pertama.

## File Terkait

- [02-skema-database.md](../../02-skema-database.md)
- [2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php.md](2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php.md)
- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
