# app/Http/Controllers/UserController.php

> **Path:** `app/Http/Controllers/UserController.php`  
> **Jenis:** Controller  
> **Dipakai oleh:** Route user management, view `users.index`, `users.form`, middleware `auth` + `admin` (via route/middleware)  
> **Memanggil:** `App\Http\Requests\StoreUserRequest`, `App\Http\Requests\UpdateUserRequest`, `App\Models\User`, view `users.*`

## Ringkasan

CRUD pengguna dengan proteksi: hanya admin yang bisa (dicek oleh Form Request `authorize()` dan middleware `admin`). Mencegah menghapus/mengubah role admin terakhir, serta mencegah menghapus akun sendiri. Mendukung pencarian nama/email, pagination.

## Peran dalam Sistem

Lapisan HTTP untuk manajemen akses. Enforces prinsip "jangan biarkan sistem tanpa admin".

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `index(Request $request): View` | `UserController.php:14-30` | List user dengan pencarian `q` (name/email) LIKE, paginate 15, `withQueryString()` |
| `create(): View` | `UserController.php:32-35` | Form tambah user |
| `store(StoreUserRequest $request): RedirectResponse` | `UserController.php:37-50` | Buat user (password di-handle model/cast; request tervalidasi) |
| `edit(User $user): View` | `UserController.php:52-55` | Form edit user (route model binding) |
| `update(UpdateUserRequest $request, User $user): RedirectResponse` | `UserController.php:57-77` | Cek proteksi admin terakhir saat mengubah role, update field, password opsional, simpan |
| `destroy(User $user): RedirectResponse` | `UserController.php:79-92` | Cek tidak menghapus diri sendiri, cek admin terakhir, hapus, redirect |
| `adminCount(): int` | `UserController.php:94-97` | Hitung jumlah user dengan role `admin` |

## Alur Data

```text
GET /users?q=... -> index() search -> paginate -> view
GET /users/create -> create() -> view form
POST /users -> StoreUserRequest (authorize admin + rules) -> User::create([...]) -> redirect index
GET /users/{user}/edit -> edit() -> view form
PUT/PATCH /users/{user} -> UpdateUserRequest (authorize admin + rules) -> cek adminCount() jika turun dari admin -> update fields (+password jika filled) -> save -> redirect
DELETE /users/{user} -> cek id == auth()->id() -> cek adminCount() jika admin -> delete -> redirect
```

## Relasi

- Route: user resource (protected admin)
- Model/tabel: `App\Models\User` -> `users`
- View: `resources/views/users/index.blade.php`, `users/form.blade.php`
- Requests: `StoreUserRequest`, `UpdateUserRequest`
- Middleware: `EnsureUserIsAdmin` (serta `auth`)

## Catatan Teknis

- **Proteksi ganda**: `authorize()` di Form Request mengembalikan `$this->user()?->isAdmin() ?? false` (403 jika tidak admin). Middleware `EnsureUserIsAdmin` juga melakukan `abort(403, ...)` jika bukan admin. Kedua lapisan ada.
- **Admin terakhir**: dicek di `update()` saat `$user->role === 'admin' && $request->role !== 'admin' && $this->adminCount() === 1` -> kembali dengan error `role`. Di `destroy()` jika user adalah admin dan `adminCount() === 1` -> error `delete`. Mencegah sistem tanpa admin.
- **Akun sendiri**: `destroy()` menolak jika `$user->id === auth()->id()`.
- **Password**: saat create wajib, saat update hanya diubah jika `filled('password')` (di-hash oleh model/cast atau setter).
- **Pencarian**: case-insensitive via LIKE `%...%` pada name/email.
- **Route model binding**: `edit/update/destroy` menerima `User $user`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana mencegah hapus admin terakhir?  
**Jawab:** Di `destroy()`, jika target adalah `admin` dan `adminCount() === 1`, kembalikan error dan tidak hapus. Juga dicek saat menurunkan role di `update()`. (`UserController.php:59-62, 85-88, 94-97`)

**Tanya:** Apakah bisa admin menghapus akunnya sendiri?  
**Jawab:** Tidak. Dicek `if ($user->id === auth()->id()) return back()->withErrors(['delete' => 'Tidak dapat menghapus akun sendiri.'])`. (`UserController.php:81-83`)

**Tanya:** Siapa yang bisa mengakses manajemen user?  
**Jawab:** Hanya admin. Diperiksa oleh `StoreUserRequest::authorize()` dan `UpdateUserRequest::authorize()` yang memanggil `$this->user()->isAdmin()`, serta middleware `EnsureUserIsAdmin` yang melakukan abort 403. (`UserController.php` + `StoreUserRequest.php:13-16`, `UpdateUserRequest.php:13-16`, `EnsureUserIsAdmin.php:16-19`)

## File Terkait

- [EnsureUserIsAdmin](../Middleware/EnsureUserIsAdmin.php.md)
- [StoreUserRequest](../Requests/StoreUserRequest.php.md)
- [UpdateUserRequest](../Requests/UpdateUserRequest.php.md)
- [AuthenticatedSessionController](Auth/AuthenticatedSessionController.php.md)
- [User Model](../../Models/User.php.md)
