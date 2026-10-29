# ShipmentSeeder.php

> **Path:** `database/seeders/ShipmentSeeder.php`  
> **Jenis:** Seeder  
> **Dipakai oleh:** `DatabaseSeeder`  
> **Memanggil:** `App\Services\ShipmentSyncService`, `Illuminate\Database\Seeder`

## Ringkasan

Seeder yang memanggil `ShipmentSyncService::sync()` dengan `replace: true` untuk mengisi tabel `shipments` dari Google Sheets saat seeding.

## Peran dalam Sistem

Lapisan database/seeding. Menyediakan data awal dashboard melalui proses sinkronisasi yang sama seperti runtime.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `run()` | `database/seeders/ShipmentSeeder.php:10-24` | Resolve service via container, panggil `sync(replace: true)`, tampilkan jumlah baris dan waktu sync terakhir, tangkap error dan log. |

## Alur Data

```text
DatabaseSeeder -> ShipmentSeeder::run()
  -> app(ShipmentSyncService::class)->sync(replace: true)
  -> truncate/import ulang dari GOOGLE_SHEETS_CSV_URL
  -> tampilkan info/warning/error
```

## Relasi

- Route: -
- Model/tabel: `shipments`
- View: -
- Service: `App\Services\ShipmentSyncService`
- Test: -

## Catatan Teknis

- Menggunakan `replace: true` (sesuai komentar di 00-ringkasan-arsitektur: untuk `migrate:fresh --seed`).
- Mengambil `lastSyncedAt()` dari service untuk ditampilkan.
- Menangkap `Throwable` dan menampilkan pesan error tanpa menghentikan seeding secara fatal (hanya log error).
- Bergantung pada `GOOGLE_SHEETS_CSV_URL` yang valid di environment.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa menggunakan service sync, bukan insert langsung?  
**Jawab:** Agar seeding mengikuti logika bisnis yang sama (pemetaan header, normalisasi, pre-flight guard, penanganan duplikat, fingerprint, cache) seperti sync runtime.

**Tanya:** Apa arti `replace: true`?  
**Jawab:** Memaksa truncate tabel `shipments` dan import ulang (digunakan saat URL berubah atau saat fresh seed).

## File Terkait

- [01-alur-data-dan-sinkronisasi.md](../../01-alur-data-dan-sinkronisasi.md)
- [00-ringkasan-arsitektur.md](../../00-ringkasan-arsitektur.md)
- [database/seeders/DatabaseSeeder.php.md](DatabaseSeeder.php.md)
- [05-konfigurasi-environment.md](../../05-konfigurasi-environment.md)
