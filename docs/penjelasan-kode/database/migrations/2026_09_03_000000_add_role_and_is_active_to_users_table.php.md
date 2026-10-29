# 2026_09_03_000000_add_role_and_is_active_to_users_table.php

> **Path:** `database/migrations/2026_09_03_000000_add_role_and_is_active_to_users_table.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (otorisasi user)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`, `Illuminate\Support\Facades\DB`

## Ringkasan

Menambahkan kolom `role` (varchar(20), default 'user') dan `is_active` (boolean, default true) ke tabel `users`, lalu mempromosikan `admin@anl.com` menjadi admin.

## Peran dalam Sistem

Lapisan database/migrasi. Mendukung otorisasi berbasis peran dan penonaktifan akun.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` | `2026_09_03_000000_add_role_and_is_active_to_users_table.php:15-21` | Tambah `role` varchar(20) default 'user' + index after `password`, `is_active` boolean default true after `role`. |
| Promosi admin | `:20` | `DB::table('users')->where('email','admin@anl.com')->update(['role'=>'admin'])`. |
| `down()` | `:27-32` | Drop index `role`, drop kolom `role`, `is_active`. |

## Alur Data

```text
migrate -> tambahkan role/is_active -> set admin@anl.com sebagai admin
```

## Relasi

- Route: routes/web.php (middleware admin), routes/auth.php
- Model/tabel: `users`
- View: users/* (manajemen user), layout (navigasi)
- Service/Middleware: `App\Http\Middleware\EnsureUserIsAdmin`
- Test: -

## Catatan Teknis

- `role` diindeks untuk kemungkinan query filter.
- User nonaktif ditolak saat login (sesuai 00-ringkasan-arsitektur dan alur autentikasi).
- Jika user `admin@anl.com` belum ada saat migrasi dijalankan, update tidak berpengaruh (aman).
- Rollback menghapus kolom peran/status tanpa migrasi data kembali.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana proteksi admin di aplikasi?  
**Jawab:** Middleware alias `admin` (`App\Http\Middleware\EnsureUserIsAdmin`) melindungi route manajemen user dan tombol sync manual (routes/web.php:32, 38).

**Tanya:** Apa yang terjadi jika akun dinonaktifkan?  
**Jawab:** Ditolak saat login (prinsip di 00-ringkasan-arsitektur).

## File Terkait

- [00-ringkasan-arsitektur.md](../../00-ringkasan-arsitektur.md)
- [03-peta-route-dan-halaman.md](../../03-peta-route-dan-halaman.md)
- [bootstrap/app.php](../../bootstrap/app.php.md)
- [routes/web.php](../../routes/web.php.md)
- [database/seeders/DatabaseSeeder.php.md](../seeders/DatabaseSeeder.php.md)
