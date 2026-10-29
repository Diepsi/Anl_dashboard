# 2026_09_02_030346_add_stagging_sla_to_shipments.php

> **Path:** `database/migrations/2026_09_02_030346_add_stagging_sla_to_shipments.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (penambahan pipeline SLA)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`

## Ringkasan

Menambahkan kolom `stagging`, `sla`, `sla_result` ke tabel `shipments` beserta indeks untuk mendukung pipeline dan informasi SLA dari sumber.

## Peran dalam Sistem

Lapisan database/migrasi. Mendukung tampilan pipeline stagging di dashboard dan modal staging.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` | `2026_09_02_030346_add_stagging_sla_to_shipments.php:14-21` | Tambah `stagging` nullable after `status_instalasi` + index, `sla` nullable after `stagging`, `sla_result` nullable after `sla` + index. |
| `down()` | `:27-34` | Drop index `stagging`, `sla_result` lalu drop kolom. |

## Alur Data

```text
migrate -> tambahkan kolom pipeline/SLA -> digunakan untuk agregasi stagging dan diagnosa verdict
```

## Relasi

- Route: -
- Model/tabel: `shipments`
- View: dashboard (pipeline stagging), shipments (filter)
- Service: `App\Services\ShipmentSyncService`
- Test: -

## Catatan Teknis

- `stagging` diindeks karena dipakai untuk grouping di dashboard dan query modal API.
- `sla_result` diindeks untuk diagnosa (verdict asli dari sheet).
- `sla` awalnya diasumsikan angka hari; migrasi selanjutnya menangani format tanggal pada kolom `sla` (lihat 000002).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa perbedaan `sla_result` dengan verdict SLA yang dihitung aplikasi?  
**Jawab:** `sla_result` adalah nilai asli dari sheet sumber; aplikasi menghitung ulang verdict berdasarkan `completed_date` vs `sla_due_date` (prinsip "SLA dihitung ulang" di 00-ringkasan-arsitektur).

**Tanya:** Mengapa `stagging` penting?  
**Jawab:** Digunakan untuk pipeline 9 tahap di dashboard dan endpoint `GET /api/shipments/staging/{stagging}` untuk modal detail.

## File Terkait

- [00-ringkasan-arsitektur.md](../../00-ringkasan-arsitektur.md)
- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [02-skema-database.md](../../02-skema-database.md)
- [routes/web.php](../../routes/web.php.md)
- [2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php.md](2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php.md)
