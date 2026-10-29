# app/Console/Commands/SyncShipments.php

> **Path:** `app/Console/Commands/SyncShipments.php`  
> **Jenis:** Console Command  
> **Dipakai oleh:** CLI, scheduler, operator  
> **Memanggil:** `App\Services\ShipmentSyncService`

## Ringkasan

Command Artisan untuk sinkronisasi pengiriman dari Google Sheets. Menerima opsi `--replace` dan `--force`, memanggil service, menampilkan jumlah baris dan waktu sync terakhir, mengembalikan exit code.

## Peran dalam Sistem

Berada di lapisan **Console/CLI**. Entry point untuk operasi sync manual dan terjadwal.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Signature/Description (Attribute) | `SyncShipments.php:10-11` | `shipments:sync {--replace : Kosongkan tabel sebelum import} {--force : Izinkan sync meski baris valid nol atau menyusut drastis}` |
| `handle(ShipmentSyncService $service)` | `SyncShipments.php:14-35` | Tampilkan pesan, panggil `$service->sync((bool)$replace, (bool)$force)`, tampilkan count dan `lastSyncedAt()`, return SUCCESS/FAILURE. |

## Alur Data (input -> proses -> output)

```text
Input: opsi CLI
  -> handle(): service.sync
  -> output: info jumlah baris + warn terakhir sync
Exit code: 0 (SUCCESS) atau 1 (FAILURE)
```

## Relasi

- **Route/Console:** terdaftar di console routes/scheduler (`Schedule::command('shipments:sync')->hourly()`).
- **Service:** `ShipmentSyncService`.
- **Model:** mempengaruhi `Shipment`.

## Catatan Teknis, Edge Case, Keputusan Desain

- **Opsi:** `--replace` meneruskan flag replace ke service; `--force` melewati guard 0/50%.
- **Error handling:** catch Throwable, tampilkan pesan error, return FAILURE.
- **Informasi user:** menampilkan waktu terakhir sync dari cache.

## Kemungkinan Pertanyaan Penguji + Jawaban

**Tanya:** Apa perbedaan `--replace` dan `--force`?  
**Jawab:** `--replace` memaksa mengosongkan tabel sebelum import (service juga memaksa replace jika fingerprint URL berubah). `--force` melewati guard "0 baris valid" atau "menyusut >50%" agar operator bisa melakukan sync terpaksa.

**Tanya:** Apa return code command ini?  
**Jawab:** `self::SUCCESS` (0) jika berhasil, `self::FAILURE` (1) jika exception (L29, L33).

## File Terkait

- [ShipmentSyncService](../../Services/ShipmentSyncService.php.md)
- [01-alur-data-dan-sinkronisasi](../../../01-alur-data-dan-sinkronisasi.md)
