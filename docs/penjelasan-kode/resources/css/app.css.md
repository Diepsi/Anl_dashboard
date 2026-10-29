# resources/css/app.css

> **Path:** `resources/css/app.css`  
> **Jenis:** CSS (Tailwind directives)  
> **Dipakai oleh:** Vite build, dimuat oleh layout Blade  
> **Memanggil:** Tailwind CSS

## Ringkasan

File CSS entry point dengan direktif Tailwind CSS (`@tailwind base`, `@tailwind components`, `@tailwind utilities`).

## Peran dalam Sistem

Lapisan styling entry point. Menghasilkan CSS Tailwind yang digunakan seluruh aplikasi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Tailwind base | `app.css:1` | Reset/normalize dasar Tailwind |
| Tailwind components | `app.css:2` | Komponen Tailwind dan custom components |
| Tailwind utilities | `app.css:3` | Utility classes Tailwind |

## Alur Data

```text
Vite/PostCSS -> proses Tailwind directives -> generate CSS
Browser load CSS dari build -> styling Blade views
```

## State Alpine (modal, filter, loading, error, chart)

Tidak ada.

## Relasi route/controller/API

Tidak berhubungan langsung. Digunakan oleh semua view.

## Catatan Teknis

- Sangat minimal - hanya directives Tailwind.
- Styling kustom sebagian besar ditulis inline di Blade (kelas Tailwind + style tag kecil).
- PostCSS/Tailwind dikonfigurasi di `postcss.config.js` dan `tailwind.config.js`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa isi file app.css?
**Jawab:** Hanya tiga direktif Tailwind: base, components, utilities.

**Tanya:** Bagaimana styling diterapkan di aplikasi?
**Jawab:** Melalui utility classes Tailwind di Blade, dengan sedikit CSS kustom di style tag pada view (misalnya animasi intro, x-cloak).

## File Terkait

- [tailwind.config.js](../../tailwind.config.js.md)
- [postcss.config.js](../../postcss.config.js.md)
- [vite.config.js](../../vite.config.js.md)
- [resources/js/app.js.md](../js/app.js.md)
