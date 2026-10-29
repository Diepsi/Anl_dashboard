# package.json

> **Path:** `package.json`  
> **Jenis:** Config  
> **Dipakai oleh:** NPM/Vite  
> **Memanggil:** -

## Ringkasan

Definisi dependensi frontend: Vite, Tailwind CSS, Alpine.js, Chart.js.

## Peran dalam Sistem

Build tool dan asset bundling.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| scripts | `package.json:5-8` | `build` (vite build), `dev` (vite). |
| devDependencies | `:9-19` | tailwindcss ^3.1.0, @tailwindcss/vite ^4.0.0, vite ^8.0.0, laravel-vite-plugin ^3.1, alpinejs, autoprefixer, postcss. |
| dependencies | `:23-25` | chart.js ^4.5.1. |

## Alur Data

```text
npm run dev -> Vite dev server
npm run build -> compile assets ke public/build
```

## Relasi

- Route: -
- Model/tabel: -
- View: views menggunakan Vite (@vite)
- Service: -
- Test: -

## Catatan Teknis

- Menggunakan Tailwind CSS 3.1.0 dengan forms plugin.
- Chart.js untuk grafik dashboard/clustering.
- Alpine.js untuk interaksi ringan (modal).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Library untuk grafik?  
**Jawab:** Chart.js ^4.5.1.

## File Terkait

- [vite.config.js.md](vite.config.js.md)
- [tailwind.config.js.md](tailwind.config.js.md)
- [postcss.config.js.md](postcss.config.js.md)
- [composer.json.md](composer.json.md)
