# app/Models/Shipment.php

> **Path:** `app/Models/Shipment.php`  
> **Jenis:** Model (Eloquent)  
> **Dipakai oleh:** `ShipmentSyncService`, seluruh controller/dashboard/export, `ClusteringService`, query agregasi  
> **Memanggil:** `Illuminate\Database\Eloquent\Model`, `Illuminate\Support\Carbon`

## Ringkasan

Model Eloquent untuk tabel `shipments`. Mendefinisikan `fillable/guarded`, casting tipe data, accessor `sla_verdict` (customer) dan `sla_vendor_verdict` (sheet), scope resi valid, SLA verifiability vendor/customer, Out/Meet SLA, konsistensi tanggal, dan range berbasis anchor terakhir yang tepercaya.

## Peran dalam Sistem

Berada di lapisan **Model/Domain**. Menjaga aturan domain dekat data: SLA verdict dihitung ulang (bukan menyalin sheet), membedakan "tidak terverifikasi" dari Out SLA, mencegah tanggal tidak konsisten merusak agregasi, dan menghitung range dari anchor tepercaya.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `$fillable` | `Shipment.php:10-42` | Daftar kolom yang bisa diisi massal (termasuk duplikat, BAST, SLA, staging, dll). |
| `$guarded = ['id']` | `Shipment.php:44` | Lindungi id. |
| `$appends = ['sla_verdict', 'sla_vendor_verdict']` | `Shipment.php:46` | Selalu sertakan kedua accessor di array/JSON. |
| `casts()` | `Shipment.php:48-64` | Casting tanggal (date), integer (koli, sla, sla_threshold_days), decimal:2 (harga_per_shipment), boolean (is_duplicate_no_resi). |
| `getSlaVerdictAttribute()` | `Shipment.php:78-87` | Verdict dihitung ulang: null jika `sla_due_date` atau `completed_date` null; else `Meet SLA` jika `completed_date <= sla_due_date`, else `Out SLA`. |
| `getSlaVendorVerdictAttribute()` | `Shipment.php:92-99` | Normalisasi label sheet: `Meet SLA` / `Over SLA` atau `Out SLA`; label lain null. |
| `scopeHasValidResi($query)` | `Shipment.php:101-105` | `no_resi` tidak null dan tidak string kosong. |
| `scopeSlaVendorVerifiable($query)` | `Shipment.php:113-116` | `sla_vendor_result` berisi label vendor yang dikenali. |
| `scopeOutSlaVendor($query)` | `Shipment.php:118-121` | Label vendor `Over SLA` atau `Out SLA`. |
| `scopeMeetSlaVendor($query)` | `Shipment.php:123-126` | Label vendor `Meet SLA`. |
| `scopeSlaVerifiable($query)` | `Shipment.php:90-93` | `sla_due_date` dan `completed_date` tidak null. |
| `scopeOutSla($query)` | `Shipment.php:95-98` | Verifiable dan `completed_date > sla_due_date`. |
| `scopeMeetSla($query)` | `Shipment.php:100-103` | Verifiable dan `completed_date <= sla_due_date`. |
| `scopeWithConsistentDates($query)` | `Shipment.php:109-116` | Exclude jika `tanggal_manifest > completed_date` (hanya berlaku saat keduanya ada). |
| `scopeInRange($query, int $days)` | `Shipment.php:118-144` | Range mundur dari `MAX(completed_date)` terakhir (tepercaya), bukan today(); jika `$days<=0` return query; baris tanpa `completed_date` (masih berjalan) selalu ikut. |

## Alur Data (input -> proses -> output)

```text
Data hasil sync -> Shipment model
  -> accessor sla_verdict menghitung ulang verdict
  -> scope dipakai controller untuk KPI/SLA/tren/range
Output: atribut ter-cast + verdict terhitung + hasil query terfilter
```

## Relasi

- **Route:** digunakan semua route operasional (dashboard, pengiriman, API, export).
- **Model/tabel:** tabel `shipments` (lihat 02-skema-database).
- **View:** data dikirim ke Blade, `sla_verdict` tersedia di JSON/API.
- **Service:** diisi oleh `ShipmentSyncService`, dibaca oleh `ClusteringService`.

## Catatan Teknis, Edge Case, Keputusan Desain

- **Verdict customer dihitung ulang:** `sla_due_date` sudah dinormalisasi (dua format) di sync; `sla_verdict` tidak bergantung pada `sla_result` sheet.
- **Verdict vendor mengikuti sheet:** `sla_vendor_verdict` membaca `sla_vendor_result`; ini sumber KPI default dashboard.
- **Resi valid:** baris tanpa `no_resi` tetap disimpan untuk audit, tetapi dikecualikan dari KPI dashboard dan default halaman pengiriman lewat `hasValidResi`.
- **Null = tidak terverifikasi:** baris tanpa deadline/completed_date (customer) atau tanpa label vendor bukan Out SLA.
- **Konsistensi tanggal:** `scopeWithConsistentDates` mencegah `tanggal_manifest > completed_date` merusak agregasi (lead time/aging).
- **Anchor range tepercaya:** `scopeInRange` pakai `MAX(completed_date)` karena bisa jadi sheet belum update; juga menghindari dominasi `tanggal_manifest` korup. Baris belum selesai tetap ikut (penting untuk "Perlu Perhatian").
- **Casting tepat:** tanggal jadi Carbon/Date, boolean/integer/decimal sesuai skema.
- **fillable luas:** mencakup semua kolom dari mapping sync (aman karena diisi oleh service terkontrol).

## Kemungkinan Pertanyaan Penguji + Jawaban

**Tanya:** Mengapa `sla_verdict` dihitung ulang, bukan diambil dari kolom `sla_result`?  
**Jawab:** Karena `sla_due_date` disatukan dari dua format SLA saat sync dan telah divalidasi; mengambil langsung dari sheet bisa salah (ada kasus BOMA) sehingga verdict dihitung dari `completed_date <= sla_due_date` (L66-87).

**Tanya:** Mengapa baris tanpa `completed_date` tetap masuk saat filter range 30/90 hari?  
**Jawab:** Karena `scopeInRange` menambahkan `orWhereNull('completed_date')` - panel "Perlu Perhatian" justru memantau kiriman yang masih berjalan (L137-143).

**Tanya:** Kapan `scopeWithConsistentDates` memfilter baris?  
**Jawab:** Hanya saat keduanya ada dan `tanggal_manifest > completed_date` (baris dikecualikan) karena secara logis mustahil dan merusak agregasi berbasis tanggal (L109-116).

## File Terkait

- [ShipmentSyncService](../Services/ShipmentSyncService.php.md)
- [ClusteringService](../Services/ClusteringService.php.md)
- [02-skema-database](../../02-skema-database.md)
- [04-istilah-dan-rumus](../../04-istilah-dan-rumus.md)
