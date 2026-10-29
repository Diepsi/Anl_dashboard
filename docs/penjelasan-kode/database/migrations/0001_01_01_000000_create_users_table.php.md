# 0001_01_01_000000_create_users_table.php

> **Path:** `database/migrations/0001_01_01_000000_create_users_table.php`  
> **Jenis:** Migration  
> **Dipakai oleh:** Migrasi Laravel (skema users, password_reset_tokens, sessions)  
> **Memanggil:** `Illuminate\Database\Migrations\Migration`, `Illuminate\Database\Schema\Blueprint`, `Illuminate\Support\Facades\Schema`

## Ringkasan

File ini membuat tiga tabel autentikasi awal Laravel: `users`, `password_reset_tokens`, dan `sessions`. Ketiganya menjadi fondasi sistem autentikasi (login berbasis sesi database) pada ANL Dashboard.

## Peran dalam Sistem

Berada di lapisan database/migrasi. Menentukan skema tabel untuk pengguna, token reset password, dan penyimpanan sesi login.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `up()` - create users | `0001_01_01_000000_create_users_table.php:14-23` | Membuat tabel `users` dengan `id`, `name`, `email` unik, `email_verified_at` nullable, `password`, `remember_token`, `timestamps`. |
| `up()` - create password_reset_tokens | `0001_01_01_000000_create_users_table.php:24-28` | Membuat tabel `password_reset_tokens` dengan `email` sebagai primary key, `token`, `created_at` nullable. |
| `up()` - create sessions | `0001_01_01_000000_create_users_table.php:30-37` | Membuat tabel `sessions` dengan `id` primary, `user_id` nullable + index, `ip_address` (45), `user_agent` text, `payload` longText, `last_activity` integer + index. |
| `down()` | `0001_01_01_000000_create_users_table.php:43-48` | Drop `users`, `password_reset_tokens`, `sessions` (urutan kebalikan). |

## Alur Data

```text
migrate -> up() -> buat users, password_reset_tokens, sessions -> siap autentikasi
rollback -> down() -> drop ketiga tabel
```

## Relasi

- Route: routes/auth.php (login/logout/reset password)
- Model/tabel: `users`, `password_reset_tokens`, `sessions`
- View: auth/* (Breeze)
- Service: -
- Test: -

## Catatan Teknis

- `sessions.user_id` nullable (memungkinkan sesi tamu).
- `ip_address` dibatasi 45 karakter (IPv6).
- `payload` menggunakan `longText` (payload sesi bisa besar).
- `down()` drop ketiganya tanpa konfirmasi; rollback akan menghapus data sesi/token/reset.
- Tabel `users` awal belum memiliki kolom `role`/`is_active` (ditambahkan migrasi terpisah).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa `sessions` disimpan di database, bukan file?  
**Jawab:** Sesuai `SESSION_DRIVER=database` di `.env` (lihat config/session.php). Penyimpanan database mempermudah inspeksi dan konsistensi di lingkungan bersama.

**Tanya:** Apa konsekuensi rollback migrasi ini?  
**Jawab:** Semua pengguna, token reset, dan sesi akan hilang (`dropIfExists`) — destruktif, hanya aman di dev.

## File Terkait

- [03-peta-route-dan-halaman.md](../../03-peta-route-dan-halaman.md)
- [02-skema-database.md](../../02-skema-database.md)
- [05-konfigurasi-environment.md](../../05-konfigurasi-environment.md)
- [config/session.php](../../config/session.php.md) (setelah dibuat)
- [routes/auth.php](../../config/auth.php.md)
