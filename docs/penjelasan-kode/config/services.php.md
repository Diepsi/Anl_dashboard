# config/services.php

> **Path:** `config/services.php`  
> **Jenis:** Config  
> **Dipakai oleh:** Aplikasi (konfigurasi third-party)  
> **Memanggil:** `env()`, `base_path()`

## Ringkasan

Konfigurasi layanan pihak ketiga: Google Sheets CSV URL dan konfigurasi Python untuk clustering.

## Peran dalam Sistem

Lapisan konfigurasi integrasi eksternal.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| google_sheets.csv_url | `config/services.php:38-40` | `env('GOOGLE_SHEETS_CSV_URL')`. |
| python | `:42-46` | `bin` (default python), `timeout` (default 120), `script` `app/Console/Scripts/clustering.py`. |

## Alur Data

```text
ShipmentSyncService membaca config('services.google_sheets.csv_url')
ClusteringService membaca config('services.python.*')
```

## Relasi

- Route: -
- Model/tabel: -
- View: -
- Service: `App\Services\ShipmentSyncService`, `App\Services\ClusteringService`
- Test: -

## Catatan Teknis

- Jika `GOOGLE_SHEETS_CSV_URL` kosong, sync akan error.
- Perubahan URL memicu force replace via fingerprint.
- Python script dijalankan dengan timeout.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Darimana sumber data berasal?  
**Jawab:** Google Sheets CSV publik (sheet ANL Publikasi vendor BOMA) via `GOOGLE_SHEETS_CSV_URL`.

## File Terkait

- [05-konfigurasi-environment.md](../05-konfigurasi-environment.md)
- [01-alur-data-dan-sinkronisasi.md](../01-alur-data-dan-sinkronisasi.md)
