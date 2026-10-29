# resources/views/layouts/navigation.blade.php

> **Path:** `resources/views/layouts/navigation.blade.php`  
> **Jenis:** View/Layout (partial/navigation)  
> **Dipakai oleh:** `layouts/app.blade.php` via `@include('layouts.navigation')`  
> **Memanggil:** Route `dashboard`, `clustering`, `shipments`, `users.index`, `logout`, `Auth::user()`, Alpine.js

## Ringkasan

Navigasi kustom berbentuk sidebar untuk aplikasi ANL Dashboard (bukan navbar Breeze default). Mendukung mobile dengan top bar dan sidebar overlay, menampilkan menu Operasional/Analitik/Pengiriman/Sistem (khusus admin), info user, dan tombol logout.

## Peran dalam Sistem

Navigasi utama aplikasi terautentikasi. Mengarahkan ke halaman operasional dashboard, clustering, data pengiriman, manajemen user, dan profil/logout.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Root nav | `resources/views/layouts/navigation.blade.php:1` | `<nav x-data="{ sidebarOpen: false }">` — Alpine state untuk toggle sidebar mobile. |
| Mobile top bar | `resources/views/layouts/navigation.blade.php:3-16` | Bar atas di mobile dengan logo dan tombol toggle sidebar. |
| Backdrop mobile | `resources/views/layouts/navigation.blade.php:19` | Overlay hitam saat sidebar terbuka di mobile, klik menutup. |
| Sidebar | `resources/views/layouts/navigation.blade.php:22-23` | Aside fixed dengan transisi translate, width w-64, bg-white. |
| Brand | `resources/views/layouts/navigation.blade.php:26-36` | Link ke `route('dashboard')` dengan logo dan nama aplikasi. |
| Menu Operasional | `resources/views/layouts/navigation.blade.php:40-77` | Link Dashboard Operasional (aktif jika `request()->routeIs('dashboard')`), plus anchor links ke `#tren-pengiriman`, `#stagging-pengiriman`, `#bottleneck-wide`, `#pengiriman-terbaru`. |
| Menu Analitik | `resources/views/layouts/navigation.blade.php:80-92` | Link Clustering Wilayah ke `route('clustering')`. |
| Menu Pengiriman | `resources/views/layouts/navigation.blade.php:95-107` | Link Data Pengiriman ke `route('shipments')`. |
| Menu Sistem (admin) | `resources/views/layouts/navigation.blade.php:109-124` | Hanya tampil jika `auth()->user()?->isAdmin()`, link Manajemen User ke `route('users.index')`. |
| Footer profil | `resources/views/layouts/navigation.blade.php:128-148` | Avatar inisial dari nama user, nama user, form logout POST ke `route('logout')`. |

## Alur Data

```text
User login -> app layout include navigation -> 
Alpine init sidebarOpen=false -> 
user klik menu navigasi atau anchor link -> 
route dipanggil (dashboard/clustering/shipments/users) -> 
halaman dirender dengan layout app.
```

- **Data dari session/auth:** `Auth::user()->name`, `auth()->user()->isAdmin()`.
- **Route yang dipakai:** `dashboard`, `clustering`, `shipments`, `users.index`, `logout`.
- **State JS:** Alpine.js (`sidebarOpen`).
- **Permintaan:** Menu Sistem hanya untuk admin.

## Relasi

- **Include oleh:** `layouts/app.blade.php`.
- **Route terkait:** Semua route web utama (lihat `03-peta-route-dan-halaman.md`).
- **Auth:** Menggunakan `Auth::user()`, cek role admin.

## Catatan Teknis

- **Kustom:** Sangat berbeda dari navigasi Breeze default (sidebar vs top nav).
- **Active state:** Menggunakan `request()->routeIs()` untuk highlight menu aktif dengan warna berbeda (cyan/emerald).
- **Anchor links:** Beberapa link mengarah ke anchor di halaman dashboard (smooth scroll).
- **Mobile UX:** Toggle sidebar, backdrop click to close, transisi.
- **Admin only:** Menu Sistem dibungkus `@if (auth()->user()?->isAdmin())`.
- **Logout:** Form POST dengan `@csrf`.

## Aksesibilitas

- Tombol toggle memiliki SVG dengan path berganti (hamburger/close).
- Link semantik.
- Focus states pada tombol.

## Edge Case

- Jika user tidak admin, menu Sistem tidak muncul.
- Avatar menggunakan inisial pertama nama.
- Pada mobile, sidebar bisa ditutup dengan backdrop atau tombol.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa perbedaan navigation ini dengan Breeze default?  
**Jawab:** Menggunakan sidebar kustom (fixed left) bukan horizontal navbar, dengan grup menu, anchor links ke dashboard, dan penampilan khusus untuk admin.

**Tanya:** Bagaimana menu Sistem ditampilkan?  
**Jawab:** Hanya jika `auth()->user()->isAdmin()` true.

**Tanya:** Bagaimana navigasi mobile bekerja?  
**Jawab:** Menggunakan Alpine `sidebarOpen`, tombol toggle menampilkan/hide sidebar, backdrop menutup saat diklik.

**Tanya:** Link anchor ke mana?  
**Jawab:** Beberapa link di grup Operasional mengarah ke anchor di halaman dashboard (`#tren-pengiriman`, `#stagging-pengiriman`, dll).

## File Terkait

- [layouts/app.blade.php](app.blade.php.md)
- [routes/web.php (peta route)](../../../03-peta-route-dan-halaman.md)
- [03-peta-route-dan-halaman.md](../../../03-peta-route-dan-halaman.md)
- [resources/js/app.js](../../js/app.js.md)
