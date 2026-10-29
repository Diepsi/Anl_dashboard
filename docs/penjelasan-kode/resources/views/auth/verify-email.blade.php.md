# resources/views/auth/verify-email.blade.php

> **Path:** `resources/views/auth/verify-email.blade.php`  
> **Jenis:** View  
> **Dipakai oleh:** Route `verification.notice` (GET), route `verification.send` (POST), route `logout` (POST)  
> **Memanggil:** Komponen `<x-guest-layout>`, `<x-primary-button>`, route `verification.send`, `logout`

## Ringkasan

Halaman pemberitahuan verifikasi email. Menampilkan pesan instruksi, pesan sukses jika link verifikasi baru dikirim, tombol kirim ulang, dan tombol logout.

## Peran dalam Sistem

Lapisan presentasi untuk alur verifikasi email (Breeze). Muncul saat pengguna perlu memverifikasi alamat email.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Layout tamu | `resources/views/auth/verify-email.blade.php:1` | Menggunakan `<x-guest-layout>`. |
| Instruksi | `resources/views/auth/verify-email.blade.php:2-4` | Pesan meminta verifikasi email. |
| Status sukses kirim ulang | `resources/views/auth/verify-email.blade.php:6-10` | Jika `session('status') == 'verification-link-sent'` tampilkan pesan hijau. |
| Aksi | `resources/views/auth/verify-email.blade.php:12-31` | Container flex dengan dua form. |
| Form kirim ulang | `resources/views/auth/verify-email.blade.php:13-21` | POST ke `route('verification.send')`, `@csrf`, tombol `<x-primary-button>` "Resend Verification Email". |
| Form logout | `resources/views/auth/verify-email.blade.php:23-29` | POST ke `route('logout')`, `@csrf`, tombol link-style "Log Out". |

## Alur Data

```text
User terdaftar/login tapi email belum terverifikasi -> 
middleware verification.notice mengarahkan ke halaman ini -> 
view menampilkan instruksi -> 
user bisa klik "Resend Verification Email" (POST verification.send) -> 
jika berhasil redirect kembali dengan session('status')='verification-link-sent' -> 
view menampilkan pesan sukses.
```

- **Data dari controller/session:** `session('status')`.
- **Form:** Dua form POST dengan `@csrf`.
- **State JS:** Tidak ada.

## Relasi

- **Route:** `GET /verify-email` (`verification.notice`), `POST /email/verification-notification` (`verification.send`), `POST /logout`.
- **Middleware:** `auth`, throttle pada `verification.send` (lihat `03-peta-route-dan-halaman.md`).
- **Controller:** EmailVerificationNotificationController (Breeze), logout.

## Catatan Teknis

- Menggunakan pengecekan status sesi untuk menampilkan notifikasi sukses.
- Menyediakan opsi logout langsung dari halaman verifikasi.
- Tombol kirim ulang menggunakan komponen primary-button.
- Tombol logout berbentuk button link-style (bukan komponen button Breeze) dengan styling underline.

## Aksesibilitas

- Teks instruksi jelas.
- Tombol aksi terpisah dengan jelas.

## Edge Case

- Rate limiting pada pengiriman ulang (throttle:6,1).
- Jika email sudah terverifikasi, middleware bisa mengarahkan keluar dari halaman ini.
- Link verifikasi bisa kedaluwarsa/signed (route verify menggunakan `signed` middleware).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan halaman ini ditampilkan?  
**Jawab:** Ketika pengguna terautentikasi namun alamat email belum diverifikasi dan middleware `verified` membatasi akses.

**Tanya:** Bagaimana cara mengirim ulang link verifikasi?  
**Jawab:** Submit form POST ke `route('verification.send')`.

**Tanya:** Apa yang terjadi setelah kirim ulang berhasil?  
**Jawab:** Halaman dimuat ulang dan menampilkan pesan "A new verification link has been sent..." karena `session('status') == 'verification-link-sent'`.

## File Terkait

- [layouts/guest.blade.php](../layouts/guest.blade.php.md)
- [components/primary-button.blade.php](../components/primary-button.blade.php.md)
- [routes/auth.php (peta route)](../../../03-peta-route-dan-halaman.md)
- [03-peta-route-dan-halaman.md](../../../03-peta-route-dan-halaman.md)
