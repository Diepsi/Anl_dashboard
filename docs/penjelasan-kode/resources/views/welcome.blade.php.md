# resources/views/welcome.blade.php

> **Path:** `resources/views/welcome.blade.php`  
> **Jenis:** View  
> **Dipakai oleh:** Tidak ada — view default Laravel, **tidak dipakai di aplikasi ini**  
> **Memanggil:** `@vite`, route `login`, `register`, `url('/dashboard')`

## Ringkasan

Halaman welcome default Laravel 13. Menampilkan informasi starter Laravel dengan link dokumentasi, Laracasts, deploy, dan navigasi login/register/dashboard. File ini ada di codebase tetapi **tidak digunakan** sebagai halaman utama aplikasi ANL Dashboard.

## Peran dalam Sistem

Halaman default framework. Bukan bagian dari alur operasional ANL Dashboard.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| HTML head | `resources/views/welcome.blade.php:1-19` | Meta tags, judul config app, `@fonts`, conditional `@vite` atau inline Tailwind style fallback. |
| Header nav | `resources/views/welcome.blade.php:21-49` | Jika route `login` ada, tampilkan nav: jika `@auth` link ke `url('/dashboard')`, else link ke `route('login')` dan `route('register')` jika ada. |
| Main content | `resources/views/welcome.blade.php:50-217` | Konten starter Laravel (instruksi, links). |
| Laravel branding | `resources/views/welcome.blade.php:142-214` | SVG logo Laravel dan angka 13. |

## Alur Data

```text
Jika route / mengarah ke welcome.blade.php -> 
tampilkan halaman default Laravel -> 
user bisa klik Login/Register atau Dashboard jika sudah login.
```

- **Data dari framework:** `app()->version()`, route checks.
- **Route checks:** `Route::has('login')`, `Route::has('register')`, `@auth`.
- **Assets:** `@vite` dengan fallback inline style (Tailwind v4 inline).
- **State JS:** Tidak ada.

## Relasi

- **Route:** Tidak ada route di `routes/web.php` yang mengembalikan view ini. Route `GET /` diarahkan ke `DashboardController@index` dengan middleware `auth`.
- **Aplikasi ANL Dashboard:** Sesuai `03-peta-route-dan-halaman.md`, route utama aplikasi adalah `GET /` -> `DashboardController@index` dengan middleware `auth`. Jadi `welcome.blade.php` **tidak dipakai** sebagai halaman default aplikasi.
- **Auth routes:** Terkait dengan `login`, `register`.

## Catatan Teknis

- **Tidak digunakan:** Sangat penting untuk dicatat — halaman utama aplikasi adalah dashboard terproteksi auth, bukan welcome page.
- **Legacy/default:** File bawaan Laravel, mungkin tersisa setelah instalasi.
- **Fallback assets:** Memiliki inline Tailwind style jika build assets tidak ada.
- **Link ke dashboard:** Jika sudah login, link mengarah ke `url('/dashboard')` (bisa berbeda dari route `dashboard`).

## Aksesibilitas

- Struktur dasar HTML.

## Edge Case

- File ini tidak diakses oleh route aplikasi saat ini.
- Link register hanya tampil jika route register tersedia.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah welcome.blade.php dipakai di ANL Dashboard?  
**Jawab:** Tidak. Halaman utama aplikasi adalah `GET /` yang diarahkan ke `DashboardController@index` (terproteksi auth), seperti tercantum di `03-peta-route-dan-halaman.md`. File ini adalah halaman default Laravel yang tersisa.

**Tanya:** Kemana link "Dashboard" di halaman ini mengarah jika user sudah login?  
**Jawab:** Ke `url('/dashboard')`.

**Tanya:** Apakah file ini aman dibiarkan?  
**Jawab:** Ya. Tidak ada route aktif yang mengembalikan view ini; `routes/web.php` mengarahkan `/` ke `DashboardController@index` dengan middleware `auth`.

## File Terkait

- [03-peta-route-dan-halaman.md](../../03-peta-route-dan-halaman.md)
- [00-ringkasan-arsitektur.md](../../00-ringkasan-arsitektur.md)
- [routes/web.php (peta route)](../../03-peta-route-dan-halaman.md)
- [auth/login.blade.php](auth/login.blade.php.md)
