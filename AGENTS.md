<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# ANL Dashboard — Project Notes

## Data source
- `GOOGLE_SHEETS_CSV_URL` di `.env` menunjuk ke CSV publik Google Sheets (sheet "ANL Publikasi", vendor **BOMA**).
- Sinkronisasi data ke tabel `shipments` dilakukan lewat `php artisan shipments:sync` (`App\Services\ShipmentSyncService`), **upsert** by `no_resi`. Saat `GOOGLE_SHEETS_CSV_URL` di `.env` berganti, service otomatis **truncate + import ulang** (deteksi via cache fingerprint `shipments.source_fingerprint`), sehingga data sheet lama otomatis dibuang dan dashboard mengikuti sheet yang dipakai. Gunakan `--replace` untuk memaksa truncate manual.
- `database/seeders/ShipmentSeeder.php` memanggil service dengan `replace: true` (untuk `migrate:fresh --seed`).
- `last_synced_at` disimpan di cache (`shipments.last_synced_at`) dan ditampilkan di dashboard.

## Auto-sync setiap jam (Windows/Laragon)
Scheduler Laravel membutuhkan `php artisan schedule:run` aktif setiap menit. Di-cron Linux biasanya via crontab; di Windows gunakan **Task Scheduler**:

1. Buka **Task Scheduler** → *Create Task*.
2. **General**: jalankan sebagai user yang punya akses menjalankan PHP; *Run only when user is logged on*.
3. **Triggers**: *On a schedule*, ulangi **every 1 minute**, tanpa batas.
4. **Actions**:
   - Program/script: `C:\laragon\bin\php\php-8.3.30<versi>\php.exe`
   - Add arguments: `7z a "C:\laragon\www\anl-dashboard\storage\logs\schedule-run.log"` — **tidak perlu**; cukup:
   - Arguments: `artisan schedule:run`
   - Start in: `C:\laragon\www\anl-dashboard`
   - (Opsional) Redirect output/error ke file log.
5. *Settings*: cek "If the task fails, restart every 1 minute".

> `routes/console.php` mendaftarkan `Schedule::command('shipments:sync')->hourly()`.
> Jalankan `php artisan schedule:list` untuk verifikasi; `php artisan shipments:sync` untuk uji manual.

## Manual sync
- Tombol **"Sync Sekarang"** di dashboard & halaman Data Pengiriman → `POST /sync` (auth) → memanggil service sekali, flash status.

## Halaman
- `/` dashboard operasional: KPI (Total/Outbound/On Process/Completed/Over SLA), tren harian (Chart.js), pipeline Stagging 8 tahap, Bottleneck Over SLA per provinsi, pengiriman terbaru.
- `/pengiriman` (Data Pengiriman): tabel + search + filter (status, provinsi, stagging, SLA) + pagination.
- Endpoint modal: `GET /api/shipments/staging/{stagging}` dan `GET /api/shipments/bottleneck?provinsi=...` (pakai query param karena PHP built-in server mem-404 segment path yang mengandung titik).
