# resources/views/components/nav-link.blade.php

> **Path:** `resources/views/components/nav-link.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** Komponen Breeze standar (bisa dipakai di navbar)  
> **Memanggil:** Tidak ada

## Ringkasan

Link navigasi untuk navbar horizontal dengan state aktif/inaktif.

## Peran dalam Sistem

Komponen navigasi atas dengan indikator halaman aktif (border bottom).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Props | `resources/views/components/nav-link.blade.php:1` | `@props(['active'])` menerima status aktif. |
| Classes | `resources/views/components/nav-link.blade.php:3-7` | Tentukan class berdasarkan `$active ?? false`: aktif = border-indigo-400 text-gray-900, inaktif = border-transparent text-gray-500 dengan hover/focus. |
| Anchor | `resources/views/components/nav-link.blade.php:9-11` | Render `<a>` dengan class yang digabung, isi `$slot`. |

## Alur Data

```text
View mengirim :active="request()->routeIs('...')" -> 
komponen menerapkan styling aktif/inaktif.
```

- **Props:** `active` (boolean).
- **State JS:** Tidak ada.

## Relasi

- **Komponen terkait:** `responsive-nav-link.blade.php` (versi mobile/sidebar).
- **Perbedaan:** `nav-link` untuk top navigation horizontal (border bottom), `responsive-nav-link` untuk mobile menu vertikal (border left).
- **Dipakai oleh:** Breeze default; di aplikasi ini navigasi utama menggunakan `layouts/navigation.blade.php` custom (sidebar), bukan komponen ini.

## Catatan Teknis

- Menggunakan border bottom sebagai indikator aktif.
- Transisi smooth.
- `$attributes->merge()` untuk href/class tambahan.

## Aksesibilitas

- Link semantik.

## Edge Case

- Jika active tidak dikirim, default false.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa perbedaan nav-link dan responsive-nav-link?  
**Jawab:** `nav-link` untuk horizontal navbar (border bottom), `responsive-nav-link` untuk menu responsive/mobile vertikal (border left, background berbeda saat aktif).

**Tanya:** Bagaimana menandai halaman aktif?  
**Jawab:** Lewat prop `:active="request()->routeIs('route.name')"`.

**Tanya:** Apakah dipakai di navigation.blade.php aplikasi ini?  
**Jawab:** Tidak secara langsung; `layouts/navigation.blade.php` menggunakan markup custom untuk sidebar. Komponen ini tetap ada sebagai bagian Breeze.

## File Terkait

- [components/responsive-nav-link.blade.php](responsive-nav-link.blade.php.md)
- [layouts/navigation.blade.php](../layouts/navigation.blade.php.md)
- [layouts/app.blade.php](../layouts/app.blade.php.md)
