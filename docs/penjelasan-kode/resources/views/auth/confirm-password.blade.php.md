# resources/views/auth/confirm-password.blade.php

> **Path:** `resources/views/auth/confirm-password.blade.php`  
> **Jenis:** View  
> **Dipakai oleh:** Route `password.confirm` (GET/POST), middleware `auth` (halaman konfirmasi password)  
> **Memanggil:** Komponen `<x-guest-layout>`, `<x-input-label>`, `<x-text-input>`, `<x-input-error>`, `<x-primary-button>`, route `password.confirm`

## Ringkasan

File ini menampilkan form konfirmasi password untuk area aman aplikasi (Laravel Breeze). Pengguna diminta memasukkan password saat ini sebelum melanjutkan tindakan sensitif.

## Peran dalam Sistem

Lapisan presentasi (view). Digunakan saat aplikasi membutuhkan konfirmasi ulang password pengguna yang sedang login.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Layout tamu | `resources/views/auth/confirm-password.blade.php:1` | Menggunakan `<x-guest-layout>` untuk tampilan terpusat bertema tamu. |
| Teks penjelasan | `resources/views/auth/confirm-password.blade.php:2-4` | Menjelaskan bahwa ini area aman dan perlu konfirmasi password. |
| Form konfirmasi | `resources/views/auth/confirm-password.blade.php:6-26` | Form POST ke `route('password.confirm')` dengan `@csrf`. |
| Field password | `resources/views/auth/confirm-password.blade.php:10-19` | Input password dengan `autocomplete="current-password"`, wajib (`required`), label, dan error dari `errors->get('password')`. |
| Tombol konfirmasi | `resources/views/auth/confirm-password.blade.php:21-25` | Tombol `<x-primary-button>` berlabel `Confirm`. |

## Alur Data

```text
User yang login mengakses route password.confirm -> 
view ini dirender dalam guest-layout -> 
submit POST ke route('password.confirm') dengan password -> 
controller validasi password saat ini -> 
jika valid, izin ke action sensitif diberikan / redirect sesuai logic Breeze.
```

- **Data dari controller:** Tidak ada variabel khusus yang dikirim (umum di Breeze).
- **Form:** `method="POST"`, `action="{{ route('password.confirm') }}"`, `@csrf`.
- **State JS:** Tidak ada Alpine/JS kustom di file ini.

## Relasi

- **Route:** `GET/POST /confirm-password` (`password.confirm`) — lihat `routes/auth.php`.
- **Controller:** Controller password.confirm dari Laravel Breeze.
- **View/Layout:** `layouts/guest.blade.php`, komponen auth/Breeze.
- **Middleware:** Biasanya `auth`.

## Catatan Teknis

- Menggunakan komponen form standar Breeze (konsisten dengan auth lain).
- `autocomplete="current-password"` membantu manajer password.
- Menampilkan error per field via `<x-input-error>`.
- Area aman, akses dibatasi untuk pengguna terautentikasi.

## Aksesibilitas

- Label terhubung ke input via `for="password"`.
- Pesan error dibaca melalui komponen input-error.

## Edge Case

- Password salah -> error ditampilkan di `password`.
- Sudah dikonfirmasi dalam sesi -> bisa langsung diizinkan tergantung implementasi Breeze.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan halaman ini muncul?  
**Jawab:** Saat pengguna mencoba mengakses route/action yang dilindungi dengan middleware `password.confirm` (konfirmasi password ulang).

**Tanya:** Field apa yang wajib diisi?  
**Jawab:** Hanya password saat ini dengan `required`.

**Tanya:** Mengapa menggunakan `guest-layout` padahal butuh login?  
**Jawab:** Karena tampilan form auth (kartu terpusat) sama; perlindungan akses diatur oleh route/middleware, bukan oleh layout.

## File Terkait

- [layouts/guest.blade.php](../layouts/guest.blade.php.md)
- [components/input-label.blade.php](../components/input-label.blade.php.md)
- [components/text-input.blade.php](../components/text-input.blade.php.md)
- [components/input-error.blade.php](../components/input-error.blade.php.md)
- [components/primary-button.blade.php](../components/primary-button.blade.php.md)
- [routes/auth.php (peta route)](../../../03-peta-route-dan-halaman.md)
