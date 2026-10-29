# resources/views/users/form.blade.php

> **Path:** `resources/views/users/form.blade.php`  
> **Jenis:** View  
> **Dipakai oleh:** Route `users.create` (GET), `users.edit` (GET)  
> **Memanggil:** `<x-app-layout>`, route `users.index`, `users.store`, `users.update`, `$user` (opsional)

## Ringkasan

Form untuk menambah atau mengedit user. Hanya bisa diakses oleh admin (route dilindungi middleware admin). Mendukung pengaturan nama, email, role, status aktif, dan password (wajib saat tambah, opsional saat edit).

## Peran dalam Sistem

Halaman manajemen user untuk CRUD user oleh administrator.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Layout | `resources/views/users/form.blade.php:1` | Menggunakan `<x-app-layout>`. |
| Header | `resources/views/users/form.blade.php:6-21` | Judul dinamis "Tambah User"/"Edit User", deskripsi, tombol kembali ke daftar. |
| Form | `resources/views/users/form.blade.php:25-110` | Form dinamis: action ke `users.store` jika baru, `users.update` jika edit; method PUT jika edit. |
| Nama | `resources/views/users/form.blade.php:32-39` | Input nama, nilai dari `old('name', $user?->name ?? '')`. |
| Email | `resources/views/users/form.blade.php:41-48` | Input email. |
| Role | `resources/views/users/form.blade.php:50-59` | Select role user/admin, default 'user'. |
| Status aktif | `resources/views/users/form.blade.php:61-68` | Checkbox is_active, default true saat tambah. |
| Password | `resources/views/users/form.blade.php:70-98` | Saat edit: password opsional (placeholder "Kosongkan jika tidak diubah") + konfirmasi. Saat tambah: password wajib + konfirmasi. |
| Tombol aksi | `resources/views/users/form.blade.php:101-108` | Tombol submit dinamis dan tombol batal. |

## Alur Data

```text
Admin akses /users/create atau /users/{user}/edit -> 
UserController@create/edit mengirim data $user jika edit -> 
view merender form dinamis -> 
submit ke store/update -> 
validasi dan simpan -> redirect ke index dengan status.
```

- **Data dari controller:** `$user` (opsional, ada saat edit).
- **Form:** Dinamis action dan method, `@csrf`, `@method('PUT')` saat edit.
- **Validasi:** Error ditampilkan per field via `@error`.
- **Nilai lama:** Menggunakan `old()` dengan fallback ke data user.

## Relasi

- **Route:** `GET /users/create` (`users.create`), `POST /users` (`users.store`), `GET /users/{user}/edit` (`users.edit`), `PUT/PATCH /users/{user}` (`users.update`).
- **Controller:** `UserController` (resource).
- **Middleware:** `auth`, `admin`.
- **View terkait:** `users/index.blade.php`.

## Catatan Teknis

- **Dinamis:** Menggunakan `isset($user)` untuk membedakan tambah/edit.
- **Password opsional saat edit:** Logika aman (hanya update jika diisi).
- **Checkbox is_active:** Mengirim value '1' jika dicentang.
- **Role management:** Bisa set admin/user.
- **UI konsisten:** Menggunakan styling Tailwind seperti halaman lain.

## Aksesibilitas

- Label untuk setiap field.
- Required fields diberi `required`.

## Edge Case

- Saat edit, password bisa dikosongkan (tidak diubah).
- Validasi email unik perlu diperhatikan saat edit (exclude user saat ini).
- Proteksi admin terakhir saat hapus diatur di controller, bukan di view ini.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan password wajib diisi?  
**Jawab:** Hanya saat menambah user baru; saat edit bersifat opsional (kosongkan jika tidak ingin mengubah).

**Tanya:** Siapa yang bisa mengakses halaman ini?  
**Jawab:** Hanya user dengan role admin (dilindungi middleware admin).

**Tanya:** Bagaimana form membedakan tambah vs edit?  
**Jawab:** Dengan `isset($user)`: action berbeda, method PUT ditambahkan saat edit, field password berbeda.

## File Terkait

- [users/index.blade.php](index.blade.php.md)
- [layouts/app.blade.php](../layouts/app.blade.php.md)
- [layouts/navigation.blade.php](../layouts/navigation.blade.php.md)
- [03-peta-route-dan-halaman.md](../../../03-peta-route-dan-halaman.md)
- [00-ringkasan-arsitektur.md](../../../00-ringkasan-arsitektur.md)
