# Peta Route dan Halaman

## Route Web Utama

File: `routes/web.php`

| Method | URI | Nama | Controller/Aksi | Middleware | Fungsi |
|---|---|---|---|---|---|
| GET | `/` | `dashboard` | `DashboardController@index` | `auth` | Dashboard operasional |
| GET | `/clustering` | `clustering` | `ClusteringController@index` | `auth` | Analitik K-Means wilayah |
| GET | `/pengiriman` | `shipments` | `ShipmentsController@index` | `auth` | Tabel dan filter pengiriman |
| POST | `/pengiriman/export` | `shipments.export` | `ShipmentsController@export` | `auth` | Unduh Excel |
| GET | `/api/shipments/staging/{stagging}` | `api.staging` | `ShipmentApiController@staging` | `auth` | Data modal pipeline |
| GET | `/api/shipments/bottleneck` | `api.bottleneck` | `ShipmentApiController@bottleneck` | `auth` | Data modal bottleneck |
| GET | `/api/shipments/detail` | `api.shipment-detail` | `ShipmentApiController@detail` | `auth` | Detail satu resi |
| POST | `/sync` | `sync` | Closure + `ShipmentSyncService` | `auth`, `admin` | Sync manual |
| GET | `/profile` | `profile.edit` | `ProfileController@edit` | `auth` | Form profil |
| PATCH | `/profile` | `profile.update` | `ProfileController@update` | `auth` | Simpan profil |
| DELETE | `/profile` | `profile.destroy` | `ProfileController@destroy` | `auth` | Hapus akun sendiri |
| GET/POST/PUT/DELETE | `/users` | `users.*` | `UserController` resource | `auth`, `admin` | CRUD user |

Catatan:

- Route API memakai query parameter `range` dan `provinsi`, bukan path yang mengandung
  titik. Ini menghindari masalah routing pada server PHP built-in.
- Route `/sync` memakai middleware `admin`; user biasa tetap bisa melihat dashboard
  tetapi tidak bisa memicu sync.

## Route Autentikasi

File: `routes/auth.php`, disediakan Laravel Breeze.

### Guest

| Method | URI | Nama | Fungsi |
|---|---|---|---|
| GET | `/login` | `login` | Form login |
| POST | `/login` | - | Proses login |
| GET | `/forgot-password` | `password.request` | Form lupa password |
| POST | `/forgot-password` | `password.email` | Kirim link reset |
| GET | `/reset-password/{token}` | `password.reset` | Form reset |
| POST | `/reset-password` | `password.store` | Simpan password baru |

### Auth

| Method | URI | Nama | Middleware tambahan |
|---|---|---|---|
| GET | `/verify-email` | `verification.notice` | - |
| GET | `/verify-email/{id}/{hash}` | `verification.verify` | `signed`, `throttle:6,1` |
| POST | `/email/verification-notification` | `verification.send` | `throttle:6,1` |
| GET | `/confirm-password` | `password.confirm` | - |
| POST | `/confirm-password` | - | - |
| PUT | `/password` | `password.update` | - |
| POST | `/logout` | `logout` | - |

Catatan: registrasi publik tidak dipakai di navigasi utama; akun operasional dibuat
oleh admin melalui `/users`.

## Route Console

File: `routes/console.php`

| Command | Jadwal | Fungsi |
|---|---|---|
| `shipments:sync` | Setiap jam | Sinkronisasi CSV |
| `analytics:run-clustering` | Setelah sync sukses | Menjalankan K-Means |

Command manual:

```bash
php artisan shipments:sync
php artisan shipments:sync --replace
php artisan shipments:sync --force
php artisan analytics:run-clustering
php artisan schedule:list
```

## Peta Halaman

### `/` — Dashboard

Komponen utama:

- KPI: total, completed, on process, over SLA.
- Tren harian: volume kirim dan volume selesai.
- SLA: meet/out/unverified, tren kepatuhan.
- Pipeline stagging: 9 tahap yang ditampilkan jika total > 0.
- Bottleneck: provinsi dengan Out SLA terbanyak.
- Vendor: volume, on-time, median/p90 lead time.
- Aging: bucket umur kiriman aktif.
- Perlu perhatian: Hold, Undelivered, Retur, dan kiriman aktif tua.
- Pengiriman terbaru.
- Tombol sync untuk admin.

### `/pengiriman` — Data Pengiriman

- Pencarian `no_resi`, `nama_sekolah`, `nama_penerima`.
- Filter status, provinsi, stagging, SLA.
- Pagination 25 baris.
- Tombol export Excel mengikuti filter aktif.

### `/clustering` — Kinerja Wilayah

- Ringkasan 3 zona.
- Scatter plot Out SLA vs aging, ukuran berdasarkan volume.
- Tabel top risk.
- Metadata waktu clustering.

### `/users` — Manajemen User

Hanya admin:

- Daftar user + pencarian.
- Tambah user.
- Edit role, status aktif, dan password.
- Hapus user dengan proteksi admin terakhir dan akun sendiri.

### `/profile` — Profil

- Ubah nama/email.
- Ubah password.
- Hapus akun sendiri dengan konfirmasi password.

## Status HTTP Endpoint API

| Kondisi | Status |
|---|---|
| Data ditemukan | 200 |
| `provinsi` tidak dikirim | 422 |
| `no_resi` tidak dikirim | 422 |
| Resi tidak ditemukan | 404 |
| Belum login | 401 JSON |
| Bukan admin pada route admin | 403 |

## Middleware

| Alias/Class | Fungsi |
|---|---|
| `auth` | Wajib login |
| `guest` | Hanya untuk belum login |
| `admin` | `EnsureUserIsAdmin`, menolak non-admin dengan 403 |
| `signed` | Validasi URL verifikasi email |
| `throttle` | Rate limit login/verifikasi |
