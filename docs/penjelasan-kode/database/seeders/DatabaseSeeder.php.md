# DatabaseSeeder.php

> **Path:** `database/seeders/DatabaseSeeder.php`  
> **Jenis:** Seeder  
> **Dipakai oleh:** `php artisan db:seed`, `migrate:fresh --seed`  
> **Memanggil:** `App\Models\User`, `Illuminate\Database\Seeder`, `Illuminate\Support\Str`, `Database\Seeders\ShipmentSeeder`

## Ringkasan

Seeder utama yang membuat akun admin dan memanggil `ShipmentSeeder` untuk mengimpor data pengiriman dari sumber Google Sheets.

## Peran dalam Sistem

Lapisan database/seeding. Menyiapkan data awal untuk lingkungan development/instalasi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `use` | `database/seeders/DatabaseSeeder.php:5-9` | Import User model, WithoutModelEvents, Seeder, Str. |
| `run()` | `:17-33` | Ambil password admin dari env `ADMIN_PASSWORD` atau generate acak 16 karakter; buat user admin via factory; tampilkan kredensial; panggil `ShipmentSeeder`. |

## Alur Data

```text
db:seed -> DatabaseSeeder::run()
  -> buat User admin@anl.com role=admin password dari env/acak
  -> log kredensial
  -> call ShipmentSeeder
```

## Relasi

- Route: -
- Model/tabel: `users`, `shipments`
- View: -
- Service: `App\Services\ShipmentSyncService` (via ShipmentSeeder)
- Test: -

## Catatan Teknis

- Menggunakan `WithoutModelEvents` trait.
- Password admin diambil dari `env('ADMIN_PASSWORD')` — jika kosong, generate `Str::password(16)`. Kredensial ditampilkan di console (`$this->command?->info`).
- Membuat user dengan `User::factory()->create()` lalu override `name/email/password/role`.
- `UserFactory` secara default mengatur `role` tidak di-set (hanya name/email/password/verified_at/remember_token); seeder override role jadi `admin`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana kredensial admin default?  
**Jawab:** Email `admin@anl.com`, role `admin`. Password dari `ADMIN_PASSWORD` di `.env` atau password acak 16 karakter yang ditampilkan saat seeding.

**Tanya:** Apakah seeding aman di produksi?  
**Jawab:** Bergantung. Membuat user admin dan memanggil ShipmentSeeder yang melakukan sync bisa berdampak; biasanya digunakan untuk setup awal/dev.

## File Terkait

- [database/factories/UserFactory.php.md](../factories/UserFactory.php.md)
- [database/seeders/ShipmentSeeder.php.md](ShipmentSeeder.php.md)
- [database/migrations/2026_09_03_000000_add_role_and_is_active_to_users_table.php.md](../migrations/2026_09_03_000000_add_role_and_is_active_to_users_table.php.md)
- [05-konfigurasi-environment.md](../../05-konfigurasi-environment.md)
