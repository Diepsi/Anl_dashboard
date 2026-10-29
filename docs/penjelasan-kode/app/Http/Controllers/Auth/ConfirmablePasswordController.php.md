# app/Http/Controllers/Auth/ConfirmablePasswordController.php

> **Path:** `app/Http/Controllers/Auth/ConfirmablePasswordController.php`  
> **Jenis:** Controller (Auth)  
> **Dipakai oleh:** Route password confirmation (Breeze), view `auth.confirm-password`  
> **Memanggil:** `App\Http\Controllers\Controller`, `Illuminate\Support\Facades\Auth`, view `auth.confirm-password`

## Ringkasan

Menampilkan form konfirmasi password dan memvalidasi password saat ini untuk mengakses area sensitif. Setelah berhasil, menyimpan timestamp `auth.password_confirmed_at` di session.

## Peran dalam Sistem

Bagian dari Laravel Breeze password confirmation flow. Opsional dipakai untuk proteksi route sensitif.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `show(): View` | `ConfirmablePasswordController.php:17-20` | Tampilkan form konfirmasi password |
| `store(Request $request): RedirectResponse` | `ConfirmablePasswordController.php:25-39` | Validasi kredensial user saat ini via `Auth::guard('web')->validate()`, jika gagal lempar ValidationException dengan pesan `auth.password`, jika sukses set session timestamp, redirect intended dashboard |

## Alur Data

```text
GET /confirm-password -> show() -> view
POST /confirm-password -> validate dengan email user + password input -> gagal -> ValidationException auth.password
-> sukses -> session put auth.password_confirmed_at = time() -> redirect intended dashboard
```

## Relasi

- Route: password.confirm, password.confirm.store
- Middleware: `auth`, `password.confirm` (jika dipakai)
- View: `auth/confirm-password.blade.php`

## Catatan Teknis

- Menggunakan `Auth::guard('web')->validate()` (bukan attempt) untuk verifikasi tanpa login ulang.
- Pesan error menggunakan `__('auth.password')` (lokalisasi Laravel).
- Timestamp disimpan di session untuk pengecekan oleh middleware `password.confirm`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa perbedaan dengan login biasa?  
**Jawab:** Tidak membuat sesi baru atau memanggil `attempt()`, hanya memvalidasi password user yang sudah terautentikasi dan menandai konfirmasi di session. (`ConfirmablePasswordController.php:27-37`)

## File Terkait

- [AuthenticatedSessionController](AuthenticatedSessionController.php.md)
- [PasswordController](PasswordController.php.md)
