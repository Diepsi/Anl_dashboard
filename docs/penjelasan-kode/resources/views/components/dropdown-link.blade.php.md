# resources/views/components/dropdown-link.blade.php

> **Path:** `resources/views/components/dropdown-link.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** Biasanya bersama `dropdown.blade.php` (komponen Breeze)  
> **Memanggil:** Tidak ada

## Ringkasan

Link styled untuk item di dalam dropdown menu.

## Peran dalam Sistem

Komponen item menu dropdown dengan styling konsisten.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Anchor | `resources/views/components/dropdown-link.blade.php:1` | `<a>` dengan class block, padding, text-sm, hover/focus states, merge atribut, isi `$slot`. |

## Alur Data

```text
Dipanggil dalam slot $content dropdown -> merender link menu.
```

- **Props/atribut:** `$attributes` digabungkan.
- **State JS:** Tidak ada.

## Relasi

- **Komponen terkait:** `dropdown.blade.php`.
- **Penggunaan umum:** Link profil/logout di dropdown pengguna (Breeze default).

## Catatan Teknis

- Styling hover/focus konsisten dengan tema.
- `w-full text-start` agar penuh lebar dropdown.

## Aksesibilitas

- Link semantik.

## Edge Case

- Dapat digunakan dengan `href` atau bisa dikombinasikan dengan form (seperti logout via form submit).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Digunakan bersama komponen apa?  
**Jawab:** Bersama `<x-dropdown>` sebagai item di slot `$content`.

**Tanya:** Apa bedanya dengan `nav-link`?  
**Jawab:** `dropdown-link` untuk menu dropdown vertikal, `nav-link` untuk navigasi horizontal di navbar.

## File Terkait

- [components/dropdown.blade.php](dropdown.blade.php.md)
- [components/nav-link.blade.php](nav-link.blade.php.md)
- [components/responsive-nav-link.blade.php](responsive-nav-link.blade.php.md)
