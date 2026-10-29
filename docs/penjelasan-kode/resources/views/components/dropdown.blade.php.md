# resources/views/components/dropdown.blade.php

> **Path:** `resources/views/components/dropdown.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** Tidak ditemukan pemakaian langsung di file yang dibaca (komponen Breeze standar)  
> **Memanggil:** Alpine.js (`x-data`, `x-show`, `x-transition`, `@click`, `@click.outside`)

## Ringkasan

Komponen dropdown dengan Alpine.js untuk menampilkan menu dropdown. Mendukung penyesuaian alignment, lebar, dan kelas konten.

## Peran dalam Sistem

Komponen UI interaktif (dropdown) — tersedia di codebase (komponen Breeze). Bisa digunakan untuk menu pengguna/aksi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Props | `resources/views/components/dropdown.blade.php:1` | `align` (default right), `width` (default 48), `contentClasses` (default 'py-1 bg-white'). |
| PHP setup | `resources/views/components/dropdown.blade.php:3-14` | Menghitung class alignment dan lebar via match. |
| Container | `resources/views/components/dropdown.blade.php:16` | `<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">` |
| Trigger | `resources/views/components/dropdown.blade.php:17-19` | Slot `$trigger` ditampilkan, klik toggle `open`. |
| Dropdown panel | `resources/views/components/dropdown.blade.php:21-34` | Panel dengan transisi Alpine, posisi absolut, z-50, `x-show="open"`, klik menutup, konten dari slot `$content`. |

## Alur Data

```text
Komponen dipanggil dengan $trigger dan $content -> 
Alpine state open=false -> 
klik trigger toggle open -> 
panel muncul dengan transisi -> 
klik outside atau klik item (karena @click="open = false" di panel) menutup dropdown.
```

- **Props:** `align`, `width`, `contentClasses`.
- **Slots:** `$trigger`, `$content` (tidak dideklarasikan via @slot tapi digunakan).
- **State JS:** Alpine.js (`open`).

## Relasi

- **Komponen terkait:** `dropdown-link.blade.php`.
- **Framework:** Alpine.js (sudah dimuat via `resources/js/app.js` di layout).
- **Dipakai oleh:** Tidak terlihat di file view yang dibaca saat ini (bisa dipakai di tempat lain atau cadangan Breeze).

## Catatan Teknis

- Alignment: left/top/default (right).
- Width: mendukung '48' atau custom value.
- Transisi enter/leave dengan scale/opacity.
- `style="display: none;"` awal untuk mencegah flash.
- Ring dan shadow pada konten.

## Aksesibilitas

- Klik outside menutup dropdown.
- Perlu diperhatikan keyboard navigation (komponen ini tidak menampilkan full keyboard trap seperti modal; fokus bisa ditingkatkan).

## Edge Case

- Dropdown bisa keluar viewport tergantung posisi (class absolute).
- z-50 untuk layering.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana dropdown dibuka/ditutup?  
**Jawab:** Menggunakan Alpine.js state `open` di-toggle oleh trigger, ditutup oleh klik di luar atau klik pada panel.

**Tanya:** Slot apa yang digunakan?  
**Jawab:** `$trigger` untuk elemen pemicu, `$content` untuk isi dropdown.

**Tanya:** Bisa di-align ke kiri?  
**Jawab:** Ya, lewat prop `align="left"`.

## File Terkait

- [components/dropdown-link.blade.php](dropdown-link.blade.php.md)
- [layouts/app.blade.php](../layouts/app.blade.php.md)
- [resources/js/app.js](../../js/app.js.md) (Alpine)
