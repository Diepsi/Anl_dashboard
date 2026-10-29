# routes/console.php

> **Path:** `routes/console.php`  
> **Jenis:** Route/Console  
> **Dipakai oleh:** Artisan CLI, Laravel Scheduler  
> **Memanggil:** `Illuminate\Support\Facades\Artisan`, `Illuminate\Support\Facades\Schedule`

## Ringkasan

Mendefinisikan command Artisan sederhana (`inspire`) dan jadwal scheduler: `shipments:sync` setiap jam yang diikuti pemanggilan `analytics:run-clustering` saat sukses.

## Peran dalam Sistem

Lapisan CLI/scheduler. Mengotomatisasi sinkronisasi dan clustering.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Command inspire | `routes/console.php:7-9` | Menampilkan quote inspiratif. |
| Schedule sync + clustering | `:11-12` | `Schedule::command('shipments:sync')->hourly()->onSuccess(fn () => Artisan::call('analytics:run-clustering'))`. |

## Alur Data

```text
Scheduler (cron/Task Scheduler) -> php artisan schedule:run tiap menit
-> jika jam berganti / sesuai jadwal -> shipments:sync
-> jika sukses -> analytics:run-clustering
```

## Relasi

- Route: -
- Model/tabel: `shipments`, `regional_clusters`
- View: -
- Service: `App\Services\ShipmentSyncService`, `App\Services\ClusteringService`
- Test: -

## Catatan Teknis

- Scheduler membutuhkan `php artisan schedule:run` dijalankan setiap menit (Windows Task Scheduler sesuai 00-ringkasan-arsitektur).
- Clustering dijalankan otomatis setelah sync berhasil, bukan terjadwal terpisah.
- Verifikasi dengan `php artisan schedule:list`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan clustering berjalan?  
**Jawab:** Otomatis setelah `shipments:sync` berhasil dijalankan oleh scheduler (hook `onSuccess`).

**Tanya:** Bagaimana menjalankan di Windows?  
**Jawab:** Konfigurasi Task Scheduler menjalankan `php artisan schedule:run` setiap menit (detail di 00-ringkasan-arsitektur dan 05-konfigurasi-environment).

## File Terkait

- [00-ringkasan-arsitektur.md](../00-ringkasan-arsitektur.md)
- [01-alur-data-dan-sinkronisasi.md](../01-alur-data-dan-sinkronisasi.md)
- [05-konfigurasi-environment.md](../05-konfigurasi-environment.md)
- [03-peta-route-dan-halaman.md](../03-peta-route-dan-halaman.md)
