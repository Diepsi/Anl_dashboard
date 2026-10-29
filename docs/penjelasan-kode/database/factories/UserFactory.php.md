# UserFactory.php

> **Path:** `database/factories/UserFactory.php`  
> **Jenis:** Factory  
> **Dipakai oleh:** `DatabaseSeeder`, tests  
> **Memanggil:** `App\Models\User`, `Illuminate\Database\Eloquent\Factories\Factory`, `Illuminate\Support\Facades\Hash`, `Illuminate\Support\Str`

## Ringkasan

Factory untuk model `User` dengan state default (nama, email unik, verified, password terenkripsi, remember_token). Menyediakan state `unverified()`.

## Peran dalam Sistem

Lapisan database/factory. Membantu pembuatan data user untuk seeding dan pengujian.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `definition()` | `database/factories/UserFactory.php:25-34` | Default: `name` fake, `email` unique safeEmail, `email_verified_at` now(), `password` di-cache via `static::$password` (Hash::make('password')), `remember_token` random 10. |
| `unverified()` | `:40-44` | State mengubah `email_verified_at` menjadi null. |
| `$password` static | `:18` | Cache password yang di-hash agar konsisten dalam satu run factory. |

## Alur Data

```text
User::factory()->create() -> definition() -> buat user dengan default
User::factory()->unverified()->create() -> tanpa email_verified_at
```

## Relasi

- Route: -
- Model/tabel: `users`
- View: -
- Service: -
- Test: -

## Catatan Teknis

- Password default adalah `password` (di-hash). Seeder utama override password user admin.
- Factory tidak mengatur `role` atau `is_active` — nilai default dari migration/DB yang berlaku.
- Menggunakan Faker via `fake()`.
- `HasFactory` trait di model `User` diasumsikan digunakan oleh factory ini.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa password default factory?  
**Jawab:** `password` (terenkripsi dengan Hash::make).

**Tanya:** Apakah factory mengatur role admin?  
**Jawab:** Tidak. `DatabaseSeeder` secara eksplisit mengatur `'role' => 'admin'` saat membuat user admin.

## File Terkait

- [database/seeders/DatabaseSeeder.php.md](../seeders/DatabaseSeeder.php.md)
- [database/migrations/0001_01_01_000000_create_users_table.php.md](../migrations/0001_01_01_000000_create_users_table.php.md)
- [database/migrations/2026_09_03_000000_add_role_and_is_active_to_users_table.php.md](../migrations/2026_09_03_000000_add_role_and_is_active_to_users_table.php.md)
