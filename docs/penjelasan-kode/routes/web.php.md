# routes/web.php

> **Path:** `routes/web.php`  
> **Jenis:** Route  
> **Dipakai oleh:** Aplikasi web (HTTP)  
> **Memanggil:** Controller (`DashboardController`, `ClusteringController`, `ShipmentsController`, `ShipmentApiController`, `ProfileController`, `UserController`), `App\Services\ShipmentSyncService`

## Ringkasan

Mendefinisikan route web utama aplikasi: dashboard, clustering, data pengiriman, API modal, sync manual, profil, dan manajemen user (resource) dengan middleware auth/admin.

## Peran dalam Sistem

Lapisan routing HTTP. Mengarahkan request ke controller/service yang tepat dengan proteksi middleware.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Group auth | `routes/web.php:12-39` | Semua route di dalam grup `auth`. |
| Dashboard | `:13` | `GET /` -> `DashboardController@index` name `dashboard`. |
| Clustering | `:14` | `GET /clustering` -> `ClusteringController@index` name `clustering`. |
| Shipments | `:15-16` | `GET /pengiriman` name `shipments`, `POST /pengiriman/export` name `shipments.export`. |
| API modal | `:17-19` | `GET /api/shipments/staging/{stagging}` name `api.staging`, `GET /api/shipments/bottleneck` name `api.bottleneck`, `GET /api/shipments/detail` name `api.shipment-detail`. |
| Sync manual | `:20-32` | `POST /sync` closure memanggil `ShipmentSyncService::sync()`, redirect back dengan status/error, middleware `admin` + name `sync`. |
| Profil | `:34-36` | `GET/PATCH/DELETE /profile` -> `ProfileController`. |
| Users resource | `:38` | `Route::middleware('admin')->resource('users', UserController::class)`. |
| Include auth | `:41` | `require __DIR__.'/auth.php'`. |

## Alur Data

```text
Request -> middleware auth -> route -> Controller/Closure -> Service/Model -> View/JSON/Redirect
Sync POST /sync (admin) -> ShipmentSyncService::sync() -> redirect back dengan flash
```

## Relasi

- Route: -
- Model/tabel: `users`, `shipments`, `regional_clusters`
- View: dashboard, clustering, shipments, profile, users
- Service: `App\Services\ShipmentSyncService`
- Test: -

## Catatan Teknis

- Route API menggunakan query parameter (`provinsi`) bukan segment path dengan titik untuk menghindari masalah routing PHP built-in server (sesuai 01-alur-data-dan-sinkronisasi).
- Route `/sync` dilindungi middleware `admin`; closure menangkap `Throwable` dan menampilkan pesan error.
- Resource users hanya untuk admin.
- Semua halaman operasional butuh auth.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Siapa yang bisa melakukan sync manual?  
**Jawab:** Hanya user dengan role admin (middleware `admin` pada route `/sync`).

**Tanya:** Mengapa endpoint staging/bottleneck beda pola?  
**Jawab:** Staging menggunakan path parameter `{stagging}`, bottleneck menggunakan query `provinsi` untuk menghindari masalah routing.

## File Terkait

- [03-peta-route-dan-halaman.md](../03-peta-route-dan-halaman.md)
- [bootstrap/app.php](../bootstrap/app.php.md)
- [routes/auth.php](../config/auth.php.md)
- [01-alur-data-dan-sinkronisasi.md](../01-alur-data-dan-sinkronisasi.md)
- [database/migrations/2026_09_03_000000_add_role_and_is_active_to_users_table.php.md](../database/migrations/2026_09_03_000000_add_role_and_is_active_to_users_table.php.md)
