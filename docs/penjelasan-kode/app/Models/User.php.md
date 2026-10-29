# app/Models/User.php

> **Path:** `app/Models/User.php`  
> **Jenis:** Model (Authenticatable)  
> **Dipakai oleh:** Auth (Breeze), middleware admin, manajemen user  
> **Memanggil:** `Illuminate\Foundation\Auth\User as Authenticatable`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `Illuminate\Notifications\Notifiable`, `Database\Factories\UserFactory`

## Ringkasan

Model autentikasi pengguna. Menggunakan atribut `#[Fillable]` dan `#[Hidden]`, casting password menjadi hashed, casting boolean `is_active`, serta method helper `isAdmin()`.

## Peran dalam Sistem

Berada di lapisan **Auth/Model**. Mendukung login, role-based access (`admin`/`user`), dan status aktif/nonaktif (user nonaktif ditolak saat login).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Atribut Fillable/Hidden | `User.php:13-15` | `fillable=['name','email','password','role','is_active']`, `hidden=['password','remember_token']`. |
| Traits | `User.php:18` | `HasFactory`, `Notifiable`. |
| `casts()` | `User.php:25-32` | `email_verified_at` datetime, `password` hashed, `is_active` boolean. |
| `isAdmin()` | `User.php:34-37` | Return true jika `role === 'admin'`. |

## Alur Data (input -> proses -> output)

```text
Input: kredensial/login
  -> Auth memvalidasi, mengecek is_active
  -> User model menyediakan role/is_active
Output: user terautentikasi dengan otorisasi
```

## Relasi

- **Route:** dilindungi oleh middleware auth/admin.
- **Model/tabel:** tabel `users` (lihat 02-skema-database).
- **View:** navbar/user info.

## Catatan Teknis, Edge Case, Keputusan Desain

- **Password hashed:** cast `password` => `hashed` (Laravel akan hash otomatis saat set).
- **Role & is_active:** `role` default `user`, `is_active` default true; akun nonaktif ditolak login (dijelaskan di arsitektur).
- **Atribut PHP 8:** menggunakan Attribute untuk fillable/hidden.

## Kemungkinan Pertanyaan Penguji + Jawaban

**Tanya:** Bagaimana penolakan user nonaktif saat login?  
**Jawab:** Berdasarkan arsitektur, jika `is_active = false`, sesi langsung logout dan login ditolak (logika ada di alur autentikasi/middleware). Model menyediakan field `is_active` dengan cast boolean.

**Tanya:** Apa perbedaan role admin vs user?  
**Jawab:** `isAdmin()` mengembalikan true jika `role === 'admin'`; middleware `admin` melindungi route manajemen user dan tombol sync manual.

## File Terkait

- [00-ringkasan-arsitektur](../../00-ringkasan-arsitektur.md)
- [02-skema-database](../../02-skema-database.md)
