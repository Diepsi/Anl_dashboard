# ANL Dashboard

Dashboard operasional manajemen pengiriman (shipment) dengan sumber data dari publikasi Google Sheets (vendor **BOMA**). Aplikasi ini menampilkan KPI, tren harian, pipeline stagging, dan analisis bottleneck Over SLA secara real-time.

## Fitur

- **KPI Operasional** — Total, Outbound, On Process, Completed, dan Over SLA.
- **Tren Harian** — visualisasi grafik harian menggunakan Chart.js.
- **Pipeline Stagging 8 tahap** — memantau progress pengiriman tiap tahap.
- **Bottleneck Over SLA per provinsi** — identifikasi titik hambatan pengiriman.
- **Data Pengiriman** — tabel lengkap dengan pencarian, filter (status, provinsi, stagging, SLA), dan pagination.
- **Sinkronisasi Otomatis** — import data dari Google Sheets setiap jam + tombol "Sync Sekarang".
- **Autentikasi** — login via Laravel Breeze dengan peran user (role & active toggle).

## Arsitektur Data

Data pengiriman disinkronkan dari **CSV publik Google Sheets** ke tabel database `shipments`.

- URL CSV dikonfigurasi lewat `GOOGLE_SHEETS_CSV_URL` di `.env`.
- Sinkronisasi dilakukan via `php artisan shipments:sync` (`App\Services\ShipmentSyncService`) — **upsert** berdasarkan `no_resi`.
- Jika URL sheet diubah, service otomatis **truncate + import ulang** (deteksi via cache fingerprint `shipments.source_fingerprint`) sehingga dashboard mengikuti sheet terbaru.
- Gunakan `--replace` untuk memaksa truncate manual.
- Waktu sinkronisasi terakhir (`shipments.last_synced_at`) ditampilkan di dashboard.

## Persyaratan

- PHP ^8.3
- Composer
- MySQL (atau DB lain yang didukung Laravel)
- Node.js & npm (untuk asset build)

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Konfigurasi `.env`:

```env
APP_NAME="ANL Dashboard"
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=anl_dashboard
DB_USERNAME=root
DB_PASSWORD=

# URL publik CSV Google Sheets (sheet "ANL Publikasi" vendor BOMA)
GOOGLE_SHEETS_CSV_URL="https://docs.google.com/spreadsheets/d/e/.../pub?output=csv"
```

Jalankan migrasi & build asset:

```bash
php artisan migrate --seed
npm install
npm run build
```

## Menjalankan Aplikasi

```bash
php artisan serve
```

Atau dengan Vite untuk hot-reload saat development:

```bash
composer run dev
```

## Sinkronisasi Data

Import data secara manual dari Google Sheets:

```bash
php artisan shipments:sync
```

Force truncate & import ulang:

```bash
php artisan shipments:sync --replace
```

### Auto-sync setiap jam

Scheduler Laravel memerlukan `php artisan schedule:run` aktif tiap menit. Di Linux gunakan crontab:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Di Windows/Laragon gunakan **Task Scheduler** untuk menjalankan `php artisan schedule:run` setiap 1 menit.

`routes/console.php` mendaftarkan `Schedule::command('shipments:sync')->hourly()`.
Verifikasi dengan `php artisan schedule:list`.

Dari dashboard juga tersedia tombol **"Sync Sekarang"** yang memanggil service secara manual.

## Halaman

| Route | Deskripsi |
|-------|-----------|
| `/` | Dashboard operasional (KPI, tren, pipeline, bottleneck, pengiriman terbaru) |
| `/pengiriman` | Data Pengiriman (tabel + search + filter + pagination) |
| `POST /sync` | Manual sync dari Google Sheets (autentikasi) |

Endpoint modal:

- `GET /api/shipments/staging/{stagging}`
- `GET /api/shipments/bottleneck?provinsi=...`

## Pengujian

```bash
composer test
```

## Lisensi

Proyek ini open-sourced dengan lisensi [MIT](https://opensource.org/licenses/MIT).
