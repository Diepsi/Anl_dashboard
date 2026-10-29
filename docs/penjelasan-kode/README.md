# Dokumentasi Kode ANL Dashboard

Dokumentasi ini menjelaskan setiap file kode proyek yang relevan untuk sidang. Satu file
Markdown mewakili satu file sumber, dengan struktur folder yang sama seperti proyek.

- Sumber acuan baris: commit `6ea57ce`
- Cakupan: 121 file sumber
- Tidak termasuk: `vendor/`, `node_modules/`, `storage/`, `public/build/`,
  `bootstrap/cache/`, lock file, `.env`, backup SQL, dan file Excel sumber
- Dokumentasi ini tidak mengubah kode aplikasi

## Urutan Baca untuk Sidang

1. [Ringkasan arsitektur](00-ringkasan-arsitektur.md)
2. [Alur data dan sinkronisasi](01-alur-data-dan-sinkronisasi.md)
3. [Skema database](02-skema-database.md)
4. [Peta route dan halaman](03-peta-route-dan-halaman.md)
5. [Istilah dan rumus](04-istilah-dan-rumus.md)
6. [Konfigurasi environment](05-konfigurasi-environment.md)
7. [Bank pertanyaan sidang](99-pertanyaan-sidang.md)

Jika penguji langsung bertanya tentang implementasi, buka dokumen inti berikut:

- Dashboard: [DashboardController](app/Http/Controllers/DashboardController.php.md),
  [dashboard.blade.php](resources/views/dashboard.blade.php.md)
- Data pengiriman: [ShipmentsController](app/Http/Controllers/ShipmentsController.php.md),
  [shipments.blade.php](resources/views/shipments.blade.php.md)
- Modal dan API: [ShipmentApiController](app/Http/Controllers/ShipmentApiController.php.md)
- Sinkronisasi: [ShipmentSyncService](app/Services/ShipmentSyncService.php.md)
- Clustering: [ClusteringService](app/Services/ClusteringService.php.md),
  [ClusteringController](app/Http/Controllers/ClusteringController.php.md),
  [clustering.py](app/Console/Scripts/clustering.py.md),
  [clustering.blade.php](resources/views/clustering.blade.php.md)
- Aturan domain: [Shipment](app/Models/Shipment.php.md),
  [RegionalCluster](app/Models/RegionalCluster.php.md),
  [User](app/Models/User.php.md)

## Struktur Dokumentasi

| Lokasi | Isi |
|---|---|
| `app/` | Controller, request, middleware, model, service, command, view component |
| `bootstrap/` | Bootstrap aplikasi dan provider |
| `config/` | Konfigurasi Laravel yang relevan |
| `database/` | Migration, seeder, factory |
| `public/` | Front controller |
| `resources/` | View Blade, CSS, dan JavaScript |
| `routes/` | Route web, auth, dan console |
| `tests/` | Test feature dan unit |
| Root | File build, dependency, dan konfigurasi proyek |

## Indeks File Sumber

### Root

- [artisan](artisan.md)
- [composer.json](composer.json.md)
- [package.json](package.json.md)
- [phpunit.xml](phpunit.xml.md)
- [postcss.config.js](postcss.config.js.md)
- [tailwind.config.js](tailwind.config.js.md)
- [vite.config.js](vite.config.js.md)

### Bootstrap

- [bootstrap/app.php](bootstrap/app.php.md)
- [bootstrap/providers.php](bootstrap/providers.php.md)

### Routes

- [routes/auth.php](routes/auth.php.md)
- [routes/console.php](routes/console.php.md)
- [routes/web.php](routes/web.php.md)

### Public

- [public/index.php](public/index.php.md)

### App: Console

- [RunClusteringAnalysis](app/Console/Commands/RunClusteringAnalysis.php.md)
- [SyncShipments](app/Console/Commands/SyncShipments.php.md)
- [clustering.py](app/Console/Scripts/clustering.py.md)

### App: Controller

- [Controller](app/Http/Controllers/Controller.php.md)
- [DashboardController](app/Http/Controllers/DashboardController.php.md)
- [ShipmentsController](app/Http/Controllers/ShipmentsController.php.md)
- [ShipmentApiController](app/Http/Controllers/ShipmentApiController.php.md)
- [ClusteringController](app/Http/Controllers/ClusteringController.php.md)
- [UserController](app/Http/Controllers/UserController.php.md)
- [ProfileController](app/Http/Controllers/ProfileController.php.md)

### App: Controller Auth

- [AuthenticatedSessionController](app/Http/Controllers/Auth/AuthenticatedSessionController.php.md)
- [ConfirmablePasswordController](app/Http/Controllers/Auth/ConfirmablePasswordController.php.md)
- [EmailVerificationNotificationController](app/Http/Controllers/Auth/EmailVerificationNotificationController.php.md)
- [EmailVerificationPromptController](app/Http/Controllers/Auth/EmailVerificationPromptController.php.md)
- [NewPasswordController](app/Http/Controllers/Auth/NewPasswordController.php.md)
- [PasswordController](app/Http/Controllers/Auth/PasswordController.php.md)
- [PasswordResetLinkController](app/Http/Controllers/Auth/PasswordResetLinkController.php.md)
- [RegisteredUserController](app/Http/Controllers/Auth/RegisteredUserController.php.md)
- [VerifyEmailController](app/Http/Controllers/Auth/VerifyEmailController.php.md)

### App: Request

- [LoginRequest](app/Http/Requests/Auth/LoginRequest.php.md)
- [ProfileUpdateRequest](app/Http/Requests/ProfileUpdateRequest.php.md)
- [StoreUserRequest](app/Http/Requests/StoreUserRequest.php.md)
- [UpdateUserRequest](app/Http/Requests/UpdateUserRequest.php.md)

### App: Middleware

- [EnsureUserIsAdmin](app/Http/Middleware/EnsureUserIsAdmin.php.md)

### App: Model

- [Shipment](app/Models/Shipment.php.md)
- [RegionalCluster](app/Models/RegionalCluster.php.md)
- [User](app/Models/User.php.md)

### App: Provider

- [AppServiceProvider](app/Providers/AppServiceProvider.php.md)

### App: Service

- [ShipmentSyncService](app/Services/ShipmentSyncService.php.md)
- [ClusteringService](app/Services/ClusteringService.php.md)

### App: View Component

- [AppLayout](app/View/Components/AppLayout.php.md)
- [GuestLayout](app/View/Components/GuestLayout.php.md)

### Config

- [app.php](config/app.php.md)
- [auth.php](config/auth.php.md)
- [cache.php](config/cache.php.md)
- [database.php](config/database.php.md)
- [filesystems.php](config/filesystems.php.md)
- [logging.php](config/logging.php.md)
- [mail.php](config/mail.php.md)
- [queue.php](config/queue.php.md)
- [services.php](config/services.php.md)
- [session.php](config/session.php.md)

### Database: Migration

- [0001_01_01_000000_create_users_table](database/migrations/0001_01_01_000000_create_users_table.php.md)
- [0001_01_01_000001_create_cache_table](database/migrations/0001_01_01_000001_create_cache_table.php.md)
- [0001_01_01_000002_create_jobs_table](database/migrations/0001_01_01_000002_create_jobs_table.php.md)
- [2026_09_02_020542_create_shipments_table](database/migrations/2026_09_02_020542_create_shipments_table.php.md)
- [2026_09_02_030346_add_stagging_sla_to_shipments](database/migrations/2026_09_02_030346_add_stagging_sla_to_shipments.php.md)
- [2026_09_03_000000_add_role_and_is_active_to_users_table](database/migrations/2026_09_03_000000_add_role_and_is_active_to_users_table.php.md)
- [2026_09_03_000001_add_bast_columns_to_shipments_table](database/migrations/2026_09_03_000001_add_bast_columns_to_shipments_table.php.md)
- [2026_09_29_000001_add_tgl_ho_sartrans_and_koli_to_shipments_table](database/migrations/2026_09_29_000001_add_tgl_ho_sartrans_and_koli_to_shipments_table.php.md)
- [2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults](database/migrations/2026_09_29_000002_add_sla_due_date_and_relax_fabricated_defaults.php.md)
- [2026_09_29_000003_allow_duplicate_no_resi_and_mark_them](database/migrations/2026_09_29_000003_allow_duplicate_no_resi_and_mark_them.php.md)
- [2026_09_29_000004_create_regional_clusters_table](database/migrations/2026_09_29_000004_create_regional_clusters_table.php.md)
- [2026_10_27_000001_widen_status_instalasi_on_shipments_table](database/migrations/2026_10_27_000001_widen_status_instalasi_on_shipments_table.php.md)

### Database: Seeder dan Factory

- [DatabaseSeeder](database/seeders/DatabaseSeeder.php.md)
- [ShipmentSeeder](database/seeders/ShipmentSeeder.php.md)
- [UserFactory](database/factories/UserFactory.php.md)

### Resources: CSS dan JavaScript

- [resources/css/app.css](resources/css/app.css.md)
- [resources/js/app.js](resources/js/app.js.md)

### Resources: Layout dan Navigasi

- [layouts/app.blade.php](resources/views/layouts/app.blade.php.md)
- [layouts/guest.blade.php](resources/views/layouts/guest.blade.php.md)
- [layouts/navigation.blade.php](resources/views/layouts/navigation.blade.php.md)

### Resources: Halaman Utama

- [dashboard.blade.php](resources/views/dashboard.blade.php.md)
- [shipments.blade.php](resources/views/shipments.blade.php.md)
- [clustering.blade.php](resources/views/clustering.blade.php.md)
- [welcome.blade.php](resources/views/welcome.blade.php.md)

### Resources: Auth

- [login.blade.php](resources/views/auth/login.blade.php.md)
- [register.blade.php](resources/views/auth/register.blade.php.md)
- [forgot-password.blade.php](resources/views/auth/forgot-password.blade.php.md)
- [reset-password.blade.php](resources/views/auth/reset-password.blade.php.md)
- [confirm-password.blade.php](resources/views/auth/confirm-password.blade.php.md)
- [verify-email.blade.php](resources/views/auth/verify-email.blade.php.md)

### Resources: Profile dan User

- [profile/edit.blade.php](resources/views/profile/edit.blade.php.md)
- [profile/partials/delete-user-form.blade.php](resources/views/profile/partials/delete-user-form.blade.php.md)
- [profile/partials/update-password-form.blade.php](resources/views/profile/partials/update-password-form.blade.php.md)
- [profile/partials/update-profile-information-form.blade.php](resources/views/profile/partials/update-profile-information-form.blade.php.md)
- [users/index.blade.php](resources/views/users/index.blade.php.md)
- [users/form.blade.php](resources/views/users/form.blade.php.md)

### Resources: Komponen Blade

- [application-logo.blade.php](resources/views/components/application-logo.blade.php.md)
- [auth-session-status.blade.php](resources/views/components/auth-session-status.blade.php.md)
- [danger-button.blade.php](resources/views/components/danger-button.blade.php.md)
- [dropdown.blade.php](resources/views/components/dropdown.blade.php.md)
- [dropdown-link.blade.php](resources/views/components/dropdown-link.blade.php.md)
- [input-error.blade.php](resources/views/components/input-error.blade.php.md)
- [input-label.blade.php](resources/views/components/input-label.blade.php.md)
- [modal.blade.php](resources/views/components/modal.blade.php.md)
- [nav-link.blade.php](resources/views/components/nav-link.blade.php.md)
- [primary-button.blade.php](resources/views/components/primary-button.blade.php.md)
- [responsive-nav-link.blade.php](resources/views/components/responsive-nav-link.blade.php.md)
- [secondary-button.blade.php](resources/views/components/secondary-button.blade.php.md)
- [text-input.blade.php](resources/views/components/text-input.blade.php.md)

### Tests

- [TestCase](tests/TestCase.php.md)
- [Feature/ExampleTest](tests/Feature/ExampleTest.php.md)
- [Feature/ProfileTest](tests/Feature/ProfileTest.php.md)
- [Feature/ClusteringPipelineTest](tests/Feature/ClusteringPipelineTest.php.md)
- [Feature/DashboardMetricIntegrityTest](tests/Feature/DashboardMetricIntegrityTest.php.md)
- [Feature/ShipmentDuplicateMarkingTest](tests/Feature/ShipmentDuplicateMarkingTest.php.md)
- [Feature/ShipmentExportTest](tests/Feature/ShipmentExportTest.php.md)
- [Feature/ShipmentSyncGuardTest](tests/Feature/ShipmentSyncGuardTest.php.md)
- [Feature/ShipmentSyncMappingTest](tests/Feature/ShipmentSyncMappingTest.php.md)
- [Feature/SlaScopeConsistencyTest](tests/Feature/SlaScopeConsistencyTest.php.md)
- [Unit/ExampleTest](tests/Unit/ExampleTest.php.md)
- [Auth/AuthenticationTest](tests/Feature/Auth/AuthenticationTest.php.md)
- [Auth/EmailVerificationTest](tests/Feature/Auth/EmailVerificationTest.php.md)
- [Auth/PasswordConfirmationTest](tests/Feature/Auth/PasswordConfirmationTest.php.md)
- [Auth/PasswordResetTest](tests/Feature/Auth/PasswordResetTest.php.md)
- [Auth/PasswordUpdateTest](tests/Feature/Auth/PasswordUpdateTest.php.md)
- [Auth/RegistrationTest](tests/Feature/Auth/RegistrationTest.php.md)

## Catatan

- Inventaris lengkap 121 file ada di [`_inventaris.txt`](_inventaris.txt).
- Format penulisan setiap dokumen ada di [`_template.md`](_template.md).
- Dokumen test menjelaskan skenario yang diuji, bukan hanya daftar method.
- Jika kode berubah, perbarui dokumen terkait dan verifikasi ulang referensi baris.
