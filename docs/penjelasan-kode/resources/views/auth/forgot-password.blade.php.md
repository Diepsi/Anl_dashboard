# resources/views/auth/forgot-password.blade.php

> **Path:** `resources/views/auth/forgot-password.blade.php`  
> **Jenis:** View  
> **Dipakai oleh:** Route `password.request` (GET), route `password.email` (POST)  
> **Memanggil:** Komponen `<x-guest-layout>`, `<x-auth-session-status>`, `<x-input-label>`, `<x-text-input>`, `<x-input-error>`, `<x-primary-button>`, route `password.email`

## Ringkasan

Form untuk meminta link reset password. Pengguna mengisi alamat email, lalu aplikasi mengirim email berisi tautan reset jika akun terdaftar.

## Peran dalam Sistem

Lapisan presentasi untuk alur lupa password (Breeze).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Layout tamu | `resources/views/auth/forgot-password.blade.php:1` | Menggunakan `<x-guest-layout>`. |
| Penjelasan | `resources/views/auth/forgot-password.blade.php:2-4` | Instruksi cara kerja reset password. |
| Session status | `resources/views/auth/forgot-password.blade.php:7` | Menampilkan status sesi via `<x-auth-session-status :status="session('status')" />` (mis. link terkirim). |
| Form | `resources/views/auth/forgot-password.blade.php:9-24` | POST ke `route('password.email')`, `@csrf`. |
| Email field | `resources/views/auth/forgot-password.blade.php:13-17` | Input email wajib, `autofocus`, nilai dari `old('email')`, error dari `errors->get('email')`. |
| Tombol kirim | `resources/views/auth/forgot-password.blade.php:19-23` | `<x-primary-button>` berlabel `Email Password Reset Link`. |

## Alur Data

```text
Guest membuka /forgot-password -> 
render view -> 
submit form dengan email -> 
POST password.email -> 
controller kirim link reset (jika valid) -> 
redirect dengan session('status') -> 
view menampilkan status sukses via auth-session-status.
```

- **Data dari controller:** Tidak ada variabel wajib; menggunakan `old()` dan `session('status')`, `$errors`.
- **Form:** `method="POST"`, `action="{{ route('password.email') }}"`, `@csrf`.
- **State JS:** Tidak ada.

## Relasi

- **Route:** `GET /forgot-password` (`password.request`), `POST /forgot-password` (`password.email`).
- **Controller:** Password reset link controller (Breeze).
- **View:** `layouts/guest.blade.php`, `components/auth-session-status.blade.php`.
- **Middleware:** `guest`.

## Catatan Teknis

- Menampilkan pesan sukses via session status (komponen khusus).
- Validasi error email ditampilkan per field.
- Menggunakan `old('email')` agar nilai tidak hilang saat validasi gagal.
- `autofocus` pada field email untuk UX.

## Aksesibilitas

- Label untuk email terhubung.
- Pesan status dan error jelas.

## Edge Case

- Email tidak terdaftar -> Breeze biasanya tetap mengirim respons sukses untuk mencegah enumerasi user (perilaku default), status bisa tetap menampilkan pesan sukses tergantung konfigurasi.
- Rate limiting bisa berlaku pada pengiriman link.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa yang ditampilkan saat link berhasil dikirim?  
**Jawab:** Pesan dari `session('status')` melalui `<x-auth-session-status>`.

**Tanya:** Route apa yang dipanggil form ini?  
**Jawab:** `POST` ke `route('password.email')`.

**Tanya:** Mengapa field email menggunakan `old('email')`?  
**Jawab:** Agar nilai tetap terisi jika validasi gagal.

## File Terkait

- [layouts/guest.blade.php](../layouts/guest.blade.php.md)
- [components/auth-session-status.blade.php](../components/auth-session-status.blade.php.md)
- [components/input-label.blade.php](../components/input-label.blade.php.md)
- [components/text-input.blade.php](../components/text-input.blade.php.md)
- [components/input-error.blade.php](../components/input-error.blade.php.md)
- [components/primary-button.blade.php](../components/primary-button.blade.php.md)
- [routes/auth.php (peta route)](../../../03-peta-route-dan-halaman.md)
