# Konfigurasi Environment

## Prinsip

File `.env` tidak boleh di-commit. Dokumentasi ini hanya menjelaskan nama variabel dan
dampaknya, bukan nilai rahasia. Template tersedia di `.env.example`.

## Variabel Aplikasi

| Variabel | Default | Fungsi |
|---|---|---|
| `APP_NAME` | `ANL Dashboard` | Nama aplikasi di UI dan email |
| `APP_ENV` | `local` | Environment Laravel |
| `APP_KEY` | - | Kunci enkripsi; wajib diisi `php artisan key:generate` |
| `APP_DEBUG` | `true` | Tampilkan detail error; matikan di produksi |
| `APP_URL` | `http://localhost:3000` | Basis URL untuk CLI dan tautan |
| `APP_LOCALE` | `en` | Locale aplikasi |
| `APP_FALLBACK_LOCALE` | `en` | Locale cadangan |
| `APP_FAKER_LOCALE` | `en_US` | Locale data factory |
| `ADMIN_PASSWORD` | kosong | Password admin saat seeding; kosong = acak |

Zona waktu aplikasi di `config/app.php` adalah **UTC**. Tanggal dari sumber diproses
sebagai tanggal murni, sehingga tidak ada konversi zona waktu pada field `date`.

## Database

| Variabel | Default | Fungsi |
|---|---|---|
| `DB_CONNECTION` | `mysql` | Driver database |
| `DB_HOST` | `127.0.0.1` | Host MySQL |
| `DB_PORT` | `3306` | Port MySQL |
| `DB_DATABASE` | `anl_dashboard` | Nama database |
| `DB_USERNAME` | `root` | User database |
| `DB_PASSWORD` | kosong | Password database |
| `DB_CHARSET` | `utf8mb4` | Charset koneksi |
| `DB_COLLATION` | `utf8mb4_unicode_ci` | Collation |

Catatan: `config/database.php` memiliki default framework `sqlite`, tetapi
`.env.example` proyek ini menggunakan MySQL.

## Session, Cache, Queue

| Variabel | Default | Fungsi |
|---|---|---|
| `SESSION_DRIVER` | `database` | Sesi disimpan di tabel `sessions` |
| `SESSION_LIFETIME` | `120` | Menit idle sebelum sesi kedaluwarsa |
| `SESSION_ENCRYPT` | `false` | Enkripsi payload sesi |
| `SESSION_PATH` | `/` | Cookie path |
| `SESSION_DOMAIN` | `null` | Domain cookie |
| `CACHE_STORE` | `database` | Cache statistik sync dan clustering |
| `QUEUE_CONNECTION` | `database` | Queue Laravel; sync tidak memakai queue |
| `BROADCAST_CONNECTION` | `log` | Broadcasting tidak dipakai |

Cache penting:

- `shipments.last_synced_at`
- `shipments.source_stats`
- `shipments.source_fingerprint`
- `clustering.last_run`

Jika cache dibersihkan, dashboard kehilangan waktu sync terakhir dan metadata clustering
sampai sync/klaster berikutnya berjalan.

## Google Sheets

| Variabel | Fungsi |
|---|---|
| `GOOGLE_SHEETS_CSV_URL` | URL CSV publik sheet ANL Publikasi |

Contoh bentuk:

```env
GOOGLE_SHEETS_CSV_URL="https://docs.google.com/spreadsheets/d/e/.../pub?output=csv"
```

Jika URL kosong, `ShipmentSyncService::sync()` melempar error. Jika URL berubah,
fingerprint berubah dan service memaksa `replace` agar data sheet lama tidak tercampur.

## Python dan Clustering

| Variabel | Default | Fungsi |
|---|---|---|
| `PYTHON_BIN` | `python` | Executable Python |
| `PYTHON_TIMEOUT` | `120` | Timeout proses Python dalam detik |

Script yang dijalankan:

```text
app/Console/Scripts/clustering.py
```

Dependensi Python yang dibutuhkan: `pandas`, `numpy`, `scikit-learn`.

## Email

| Variabel | Default | Fungsi |
|---|---|---|
| `MAIL_MAILER` | `log` | Email ditulis ke log, tidak dikirim |
| `MAIL_HOST` | `127.0.0.1` | SMTP host |
| `MAIL_PORT` | `2525` | SMTP port |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Alamat pengirim |
| `MAIL_FROM_NAME` | `${APP_NAME}` | Nama pengirim |

Fitur reset password membutuhkan konfigurasi email yang benar. Dalam mode `log`, tautan
reset hanya terlihat di file log.

## Logging

| Variabel | Default | Fungsi |
|---|---|---|
| `LOG_CHANNEL` | `stack` | Channel log |
| `LOG_STACK` | `single` | Stack channel |
| `LOG_LEVEL` | `debug` | Level minimum |
| `LOG_DEPRECATIONS_CHANNEL` | `null` | Channel deprecation |

## Setup Lokal

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Untuk pengembangan dengan Vite:

```bash
composer run dev
```

## Scheduler di Windows/Laragon

Laravel scheduler membutuhkan `php artisan schedule:run` setiap menit.

1. Buka **Task Scheduler** > **Create Task**.
2. **General**: jalankan sebagai user yang bisa mengeksekusi PHP.
3. **Triggers**: daily, ulangi setiap 1 menit.
4. **Actions**:
   - Program: `C:\laragon\bin\php\php-8.3.30\php.exe` (sesuaikan versi)
   - Arguments: `artisan schedule:run`
   - Start in: `C:\laragon\www\anl-dashboard`
5. **Settings**: aktifkan restart jika task gagal.

Verifikasi:

```bash
php artisan schedule:list
php artisan shipments:sync
```

## Konfigurasi Produksi

- Set `APP_ENV=production`, `APP_DEBUG=false`.
- Gunakan password database yang kuat.
- Arahkan `APP_URL` ke domain sebenarnya.
- Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis.
- Jalankan `php artisan config:cache`, `route:cache`, dan `view:cache` setelah konfigurasi stabil.
- Jangan commit `.env`, backup SQL, atau file Excel sumber.
