# Istilah dan Rumus

## Istilah Domain

| Istilah | Arti dalam proyek |
|---|---|
| **Shipment** | Satu baris pengiriman dari sheet; dapat berupa satu resi atau baris tanpa resi yang masih punya data |
| **No Resi valid** | Nomor resi 13-18 digit atau format alfanumerik BOMA seperti `26P296011490159`; baris tanpa resi valid tidak dihitung di KPI |
| **No Resi** | Nomor resi pengiriman; boleh NULL dan boleh kembar |
| **Duplikat Resi** | Resi yang muncul pada lebih dari satu baris; semua baris tetap disimpan |
| **Stagging** | Tahap perjalanan pengiriman, dari crossdock sampai process dooring |
| **Manifest** | Tanggal HO ke vendor / titik mulai last-mile |
| **HO SarTrans** | Tanggal handover dari SarTrans, dipakai sebagai anchor deadline SLA berbasis hari |
| **Completed Date** | Tanggal pengiriman dinyatakan selesai |
| **SLA** | Service Level Agreement; di sumber bisa berupa jumlah hari atau tanggal batas |
| **SLA Due Date** | Deadline hasil normalisasi dari dua bentuk SLA |
| **SLA Vendor** | Ambang hari dari kolom sheet `SLA FOR VENDOR`; dipakai untuk membandingkan hitungan vendor |
| **RESULT FOR VENDOR** | Label verdict vendor di sheet (`Meet SLA` / `Over SLA` / `Out SLA`); sumber utama KPI SLA dashboard |
| **Meet SLA** | Mode customer: `completed_date <= sla_due_date`; mode vendor: label sheet `Meet SLA` |
| **Out SLA** | Mode customer: `completed_date > sla_due_date`; mode vendor: label sheet `Over SLA`/`Out SLA` |
| **Tidak terverifikasi** | Baris tanpa `sla_due_date`/`completed_date` (customer) atau tanpa label vendor; tidak dihitung Meet/Out |
| **Bottleneck** | Provinsi dengan jumlah Out SLA tinggi |
| **Koli** | Jumlah koli dalam pengiriman |
| **BAST** | Berita Acara Serah Terima; tanggal balik dan tanggal ke finance |
| **Aging** | Umur kiriman aktif sejak manifest |
| **Lead Time** | Selisih hari antara manifest dan completed date |
| **Vendor LM** | Vendor last mile |
| **Funder** | Pihak pendana/pemilik program |
| **Regional Cluster** | Pengelompokan kota/kabupaten berdasarkan aging, Out SLA, dan volume |
| **Risk Index** | Skor 0–100 dari komponen aging dan Out SLA yang telah distandardisasi |
| **Upsert** | Insert jika belum ada, update jika sudah ada |
| **Truncate/Replace** | Kosongkan tabel lalu import ulang |
| **Fingerprint** | Hash URL CSV untuk mendeteksi pergantian sumber |

## Status Operasional

Status yang dianggap aktif untuk aging dan perhatian:

```text
On Process
On Process Delivery
Dikirim
On Delivery
Undelivered
Hold
Retur
```

`Completed` tidak termasuk aktif.

## Rumus KPI

### Completion Rate

```text
completion_rate = completed / total_shipment * 100
```

Dibulatkan tanpa desimal.

### SLA Compliance

```text
sla_denom = within_sla + over_sla
sla_pct   = within_sla / sla_denom * 100
```

Pembagi hanya baris yang terverifikasi, bukan seluruh shipment. Baris tanpa deadline
tidak boleh menurunkan atau menaikkan persentase.

Mode SLA dashboard:

- **Vendor (Sheet)** — default. `within_sla` = label `RESULT FOR VENDOR` bernilai
  `Meet SLA`; `over_sla` = `Over SLA`/`Out SLA`.
- **Customer (Tanggal)** — hitung ulang `completed_date` vs `sla_due_date`.
- Jika mode vendor dipilih tetapi tidak ada satu pun label vendor pada range aktif,
  dashboard otomatis fallback ke mode customer dan menampilkan catatan.

### Baris Tidak Terverifikasi

```text
sla_unverified = total_shipment - sla_covered
```

### Out SLA Aktif

```text
out_sla_active = jumlah Out SLA dengan status_akhir != Completed
out_sla_done   = over_sla - out_sla_active
```

## Lead Time

Untuk setiap baris Completed dengan tanggal konsisten:

```text
lead_time = DATEDIFF(completed_date, tanggal_manifest)
```

Baris dengan `lead_time < 0` dibuang. Statistik yang dipakai:

- **Median**: nilai persentil ke-50.
- **P90**: nilai persentil ke-90.
- **Mean**: rata-rata, ditampilkan sebagai pembanding.

Median dan P90 dipilih karena satu batch tanggal korup dapat membuat mean negatif
atau sangat besar, sementara median tetap stabil.

Implementasi persentil di `DashboardController::percentile()`:

```text
index = floor(percentile / 100 * (n - 1))
```

## Tren Harian

```text
volume_kirim    = COUNT(*) per DATE(tanggal_manifest)
volume_selesai  = COUNT(status_akhir = 'Completed') per DATE(completed_date)
out_sla         = COUNT(Out SLA) per DATE(completed_date)
sla_compliance  = meet / (meet + out) * 100 per DATE(completed_date)
```

Mode vendor memakai label `RESULT FOR VENDOR`; mode customer memakai
`completed_date > sla_due_date`.

Sumbu tanggal dibuat kontinu dari tanggal pertama sampai terakhir; hari tanpa aktivitas diisi `0`. Compliance diisi `null` bila tidak ada baris terverifikasi pada hari itu, bukan `0%`.

## Aging

Jangkar aging adalah `completed_date` terakhir yang konsisten pada range aktif. Jika tidak
ada, dipakai tanggal hari ini.

```text
days_open = DATEDIFF(aging_anchor, tanggal_manifest)
```

Bucket:

| Bucket | Rentang |
|---|---|
| ≤ 14 hari | `days_open <= 14` |
| 15–30 hari | `15 <= days_open <= 30` |
| 31–60 hari | `31 <= days_open <= 60` |
| > 60 hari | `days_open > 60` |

Baris aktif tanpa `tanggal_manifest` dihitung sebagai `agingExcluded`, bukan dimasukkan
ke bucket.

## SLA Dua Format

### Format tanggal

Jika sel `SLA` berisi `25/08/2025`:

```text
sla_due_date = 2025-08-25
```

### Format hari

Jika sel `SLA` berisi `17`:

```text
sla_due_date = tgl_ho_sartrans + 17 hari
```

Hanya dihitung jika `tgl_ho_sartrans <= completed_date` atau `completed_date` kosong.
Jika anchor lebih baru dari completion, deadline dibuat NULL agar tidak selalu Meet SLA.

## SLA Vendor (Sheet)

Kolom `SLA FOR VENDOR` dan `RESULT FOR VENDOR` diimpor apa adanya:

```text
sla_vendor_days   = angka hari dari "SLA FOR VENDOR" (jika masuk akal)
sla_vendor_result = teks "RESULT FOR VENDOR" (Meet SLA / Over SLA / Out SLA)
```

`RESULT FOR VENDOR` adalah sumber KPI SLA default dashboard. Verdict customer tetap
dihitung dan ditampilkan sebagai pembanding. Baris tanpa label vendor dianggap tidak
terverifikasi pada mode vendor dan tidak masuk pembagi persentase.

## K-Means Clustering

### Fitur

| Fitur | Definisi |
|---|---|
| `avg_aging` | Rata-rata durasi Completed per kota/kabupaten |
| `out_sla_rate` | Persentase Out SLA dari baris terverifikasi |
| `total_shipment` | Jumlah observasi per wilayah |

### Praproses

1. Agregasi per `kota_kabupaten`.
2. Hanya wilayah dengan `avg_aging` dan `out_sla_rate` valid.
3. Minimal 9 wilayah.
4. `StandardScaler` menormalkan ketiga fitur.
5. K-Means `n_clusters=3`, `random_state=42`, `n_init="auto"`.

### Label Zona

Label ditentukan dari pusat cluster, bukan urutan angka:

```text
risk_score   = z_avg_aging + z_out_sla_rate
volume_score = z_total_shipment
```

1. Cluster dengan `risk_score` tertinggi = **High Risk / Bottleneck Zone**.
2. Dari dua sisanya, `volume_score` tertinggi = **High Volume Zone**.
3. Sisanya = **Standard / Low Risk Zone**.

### Risk Index

```text
raw = (z_avg_aging + z_out_sla_rate) / 2
risk_index = ((clip(raw, -2, 2) + 2) / 4) * 100
```

Hasil 0–100, satu desimal. Nilai ekstrem dipotong pada ±2 standar deviasi agar tidak
menghasilkan skor di luar rentang.

## Statistik Sumber

`ShipmentSyncService` mencatat:

| Statistik | Arti |
|---|---|
| `raw_rows` | Baris berisi data shipment setelah baris template dibuang |
| `skipped_empty` | Baris dengan `no_resi` NULL |
| `skipped_malformed` | Baris dengan resi yang tidak bisa dinormalisasi |
| `valid_rows` | Baris dengan resi valid |
| `imported_rows` | Total baris yang di-insert |
| `dup_extra` | Jumlah baris tambahan akibat resi kembar |
| `unique_rows` | Jumlah resi unik |
| `duplicates` | Contoh resi kembar dan jumlah kemunculannya |
| `malformed_resi` | Contoh resi rusak dan jumlah kemunculannya |

Resi alfanumerik BOMA (`26P` + 12 digit) sekarang diterima sebagai resi valid, sehingga
tidak lagi masuk `skipped_malformed`. Hanya nilai kosong/`#N/A`/formula yang tersisa
sebagai baris tanpa resi.

## Rumus Guard Sinkronisasi

```text
floor = floor(existing_rows * 0.5)
```

Sync ditolak jika:

- `incoming == 0`, atau
- `existing > 0 && incoming < floor`.

`--force` melewati guard.
