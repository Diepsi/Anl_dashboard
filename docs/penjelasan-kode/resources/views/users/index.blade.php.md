# resources/views/users/index.blade.php

> **Path:** `resources/views/users/index.blade.php`  
> **Jenis:** View  
> **Dipakai oleh:** Route `users.index` (GET)  
> **Memanggil:** `<x-app-layout>`, route `users.create`, `users.edit`, `users.destroy`, `users.index`, `$users`, `$search`

## Ringkasan

Halaman daftar pengguna untuk manajemen user oleh admin. Menampilkan tabel user dengan pencarian, indikator role/status, aksi edit/hapus, dan pagination.

## Peran dalam Sistem

Halaman utama manajemen user.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Layout | `resources/views/users/index.blade.php:1` | Menggunakan `<x-app-layout>`. |
| Header | `resources/views/users/index.blade.php:6-18` | Judul, deskripsi, tombol "Tambah User" ke `users.create`. |
| Search | `resources/views/users/index.blade.php:21-36` | Form GET ke `users.index` untuk mencari nama/email, tombol Cari dan Reset. |
| Flash messages | `resources/views/users/index.blade.php:38-48` | Menampilkan `session('status')` (sukses) dan error `delete` jika ada. |
| Tabel | `resources/views/users/index.blade.php:51-129` | Tabel user dengan kolom #, Nama, Email, Role, Status, Dibuat, Aksi. |
| Row user | `resources/views/users/index.blade.php:70-118` | Menampilkan data user, badge role (admin/cyan, user/gray), badge status (Aktif/Nonaktif), highlight "(Anda)" jika user sendiri. |
| Aksi | `resources/views/users/index.blade.php:96-115` | Tombol Edit dan form Delete dengan konfirmasi `confirm()`. |
| Pagination | `resources/views/users/index.blade.php:126-128` | `{{ $users->links() }}`. |

## Alur Data

```text
Admin akses /users -> UserController@index -> 
query users dengan filter search, paginate -> 
kirim $users dan $search ke view -> 
render tabel dengan aksi.
```

- **Data dari controller:** `$users` (LengthAwarePaginator), `$search` (string).
- **Form search:** `method="GET"`, `action="{{ route('users.index') }}"`.
- **Form delete:** `method="POST"`, `action="{{ route('users.destroy', $user) }}"`, `@csrf`, `@method('DELETE')` dengan `onsubmit` confirm.
- **Middleware:** `auth`, `admin`.

## Relasi

- **Route:** `GET /users` (`users.index`), `GET /users/create`, `GET /users/{user}/edit`, `DELETE /users/{user}` (`users.destroy`).
- **Controller:** `UserController`.
- **View terkait:** `users/form.blade.php`.
- **Middleware:** `auth`, `admin`.

## Catatan Teknis

- **Pencarian:** Mencari di nama/email (berdasarkan parameter `q`).
- **Proteksi UI:** Menandai user saat ini dengan "(Anda)", konfirmasi hapus via JavaScript.
- **Status visual:** Badge berwarna untuk role dan status aktif.
- **Pagination:** Laravel default pagination.
- **Flash & error:** Menampilkan pesan sukses dan error hapus dari controller.

## Aksesibilitas

- Tabel semantik dengan thead/tbody.
- Konfirmasi sebelum hapus.

## Edge Case

- Tidak bisa hapus admin terakhir atau akun sendiri — dicek di controller (pesan error ditampilkan via `errors->first('delete')`).
- Empty state jika tidak ada user.
- Search bisa kosong.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa saja yang bisa dilakukan admin di halaman ini?  
**Jawab:** Mencari user, menambah user baru, mengedit user, menghapus user.

**Tanya:** Bagaimana proteksi penghapusan diterapkan?  
**Jawab:** Ada konfirmasi JavaScript di form delete, dan controller melarang menghapus admin terakhir atau akun sendiri (error ditampilkan di view).

**Tanya:** Parameter pencarian apa yang digunakan?  
**Jawab:** Parameter `q` untuk mencari nama atau email.

## File Terkait

- [users/form.blade.php](form.blade.php.md)
- [layouts/app.blade.php](../layouts/app.blade.php.md)
- [layouts/navigation.blade.php](../layouts/navigation.blade.php.md)
- [03-peta-route-dan-halaman.md](../../../03-peta-route-dan-halaman.md)
- [00-ringkasan-arsitektur.md](../../../00-ringkasan-arsitektur.md)
