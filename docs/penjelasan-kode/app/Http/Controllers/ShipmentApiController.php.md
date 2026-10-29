# app/Http/Controllers/ShipmentApiController.php

> **Path:** `app/Http/Controllers/ShipmentApiController.php`  
> **Jenis:** Controller (API JSON)  
> **Dipakai oleh:** Alpine.js di `dashboard.blade.php` (modal staging/bottleneck), JS frontend  
> **Memanggil:** `App\Models\Shipment`, `Illuminate\Http\Request`, model scopes (`inRange`, `outSla`)

## Ringkasan

Menyediakan endpoint JSON untuk modal dashboard: daftar pengiriman per tahap staging (maks 50), daftar pengiriman Out SLA per provinsi (maks 50), dan detail pengiriman berdasarkan `no_resi`. Default hanya resi valid; `include_invalid=1` membuka audit baris tanpa resi. Bottleneck mendukung `sla_mode=vendor|customer` dengan fallback otomatis.

## Peran dalam Sistem

Lapisan HTTP API untuk interaksi modal tanpa reload halaman. Menjaga konsistensi filter dengan dashboard (scope SLA hitung ulang).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `staging(Request $request, string $stagging)` | `ShipmentApiController.php:10-26` | Ambil 50 baris per stagging dengan `inRange(rangeDays)`; default `hasValidResi()`, `include_invalid=1` untuk audit |
| `bottleneck(Request $request)` | `ShipmentApiController.php:28-70` | Wajib `provinsi` (422). Mode vendor default dengan fallback customer; scope Out SLA sesuai mode; `include_invalid` opsional |
| `rangeDays(Request $request): int` | `ShipmentApiController.php:51-56` | Whitelist `0,30,90`, fallback `0` (sama dengan DashboardController) |
| `detail(Request $request)` | `ShipmentApiController.php:58-75` | Cari by `no_resi` (422 jika kosong, 404 jika tidak ditemukan), kembalikan seluruh model |

## Alur Data

```text
GET /api/shipments/staging/{stagging}?range=0|30|90 -> rangeDays whitelist -> inRange + where stagging -> limit 50 -> JSON
GET /api/shipments/bottleneck?provinsi=...&range=... -> validasi provinsi (422) -> inRange + outSla() + where provinsi -> limit 50 -> JSON
GET /api/shipments/detail?no_resi=... -> validasi (422) -> first by no_resi -> 404 jika null -> JSON model
```

## Relasi

- Route: API routes (staging/bottleneck/detail)
- Model/tabel: `App\Models\Shipment` -> `shipments`
- View/JS: `dashboard.blade.php` + Alpine.js modal
- Service: -
- Test: -

## Catatan Teknis

- **Kunci unik modal**: `staging()` mengembalikan `id` secara eksplisit agar frontend punya key unik untuk `x-for`, termasuk baris audit dengan `no_resi = NULL` saat `include_invalid=1`.
- **Konsistensi SLA**: `bottleneck()` memakai `outSlaVendor()` pada mode vendor dan `outSla()` pada mode customer, sama seperti dashboard. Fallback ke customer terjadi bila provinsi/range tidak punya label vendor.
- **Resi valid default**: kedua endpoint memakai `hasValidResi()` kecuali `include_invalid=1`; ini menjaga angka modal sama dengan KPI dashboard.
- **Whitelist range**: mencegah parameter tak dikenal mengubah definisi panel.
- **Kolom terbatas**: `staging()` memilih kolom ringkas untuk modal (mengurangi payload). `detail()` mengembalikan seluruh objek.
- **Sorting**: `tanggal_manifest desc, id desc` sebagai tie-breaker.
- **Error handling**: 422 untuk parameter wajib hilang, 404 untuk detail tidak ditemukan. Frontend menangani 401 (redirect login).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa mengembalikan `id` di endpoint staging?  
**Jawab:** Untuk menjamin key unik di Alpine.js (`x-for`). Beberapa baris bisa memiliki `no_resi = NULL` (saat sync baris template/format rusak tetap disimpan) sehingga tidak bisa dipakai sebagai key. (`ShipmentApiController.php:18`)

**Tanya:** Mengapa bottleneck menggunakan `outSla()` bukan filter manual?  
**Jawab:** Agar konsisten dengan perhitungan dashboard (hitung ulang dari `completed_date` vs `sla_due_date`). Scope memastikan hanya baris terverifikasi yang dihitung. (`ShipmentApiController.php:31-41`)

**Tanya:** Bagaimana perlakuan range?  
**Jawab:** Sama dengan dashboard - hanya `0,30,90`, fallback `0`. Diterapkan via `rangeDays()` dan scope `inRange()`. (`ShipmentApiController.php:51-56`)

## File Terkait

- [DashboardController](DashboardController.php.md)
- [01-alur-data-dan-sinkronisasi.md](../../../01-alur-data-dan-sinkronisasi.md)
- [Shipment Model](../../Models/Shipment.php.md)
