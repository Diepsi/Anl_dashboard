# resources/views/components/responsive-nav-link.blade.php

> **Path:** `resources/views/components/responsive-nav-link.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** Komponen Breeze standar (untuk responsive menu)  
> **Memanggil:** Tidak ada

## Ringkasan

Link navigasi untuk menu responsive/mobile dengan state aktif/inaktif (border kiri).

## Peran dalam Sistem

Versi mobile dari nav-link.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Props | `resources/views/components/responsive-nav-link.blade.php:1` | `@props(['active'])`. |
| Classes | `resources/views/components/responsive-nav-link.blade.php:3-7` | Aktif: border-l-4 border-indigo-400, bg-indigo-50, text-indigo-700. Inaktif: border-transparent, text-gray-600 dengan hover. |
| Anchor | `resources/views/components/responsive-nav-link.blade.php:9-11` | Render link dengan styling vertikal. |

## Alur Data

```text
Sama seperti nav-link, pakai :active berdasarkan route.
```

- **Props:** `active`.
- **State JS:** Tidak ada.

## Relasi

- **Komponen terkait:** `nav-link.blade.php`.
- **Perbedaan:** Border kiri vs border bawah, full width block, background saat aktif.
- **Dipakai oleh:** Breeze default responsive menu; di aplikasi ini `layouts/navigation.blade.php` custom menangani mobile (sidebar dengan Alpine `sidebarOpen`), jadi komponen ini mungkin tidak dipakai.

## Catatan Teknis

- `block w-full ps-3 pe-4 py-2` untuk full width.
- `text-start` untuk alignment.

## Aksesibilitas

- Link semantik.

## Edge Case

- Cocok untuk stacked menu di mobile.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Perbedaan dengan nav-link?  
**Jawab:** Vertikal dengan border kiri dan background saat aktif, bukan horizontal border bawah.

**Tanya:** Dipakai di navigation aplikasi ini?  
**Jawab:** Navigation aplikasi ini menggunakan sidebar custom dengan markup sendiri, jadi kemungkinan tidak menggunakan komponen ini.

## File Terkait

- [components/nav-link.blade.php](nav-link.blade.php.md)
- [layouts/navigation.blade.php](../layouts/navigation.blade.php.md)
- [layouts/app.blade.php](../layouts/app.blade.php.md)
