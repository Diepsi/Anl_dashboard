# vite.config.js

> **Path:** `vite.config.js`  
> **Jenis:** Config  
> **Dipakai oleh:** Vite  
> **Memanggil:** `vite`, `laravel-vite-plugin`

## Ringkasan

Konfigurasi Vite untuk Laravel: input assets CSS/JS, refresh, HMR.

## Peran dalam Sistem

Build tool configuration.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| server | `vite.config.js:5-10` | host 0.0.0.0, hmr host localhost. |
| plugins.laravel | `:12-16` | Input `resources/css/app.css`, `resources/js/app.js`, refresh true. |

## Alur Data

```text
Vite compile assets -> manifest untuk Laravel
```

## Relasi

- Route: -
- Model/tabel: -
- View: -
- Service: -
- Test: -

## Catatan Teknis

- Konfigurasi standar Laravel Vite plugin.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Assets apa yang di-build?  
**Jawab:** `resources/css/app.css` dan `resources/js/app.js`.

## File Terkait

- [package.json.md](package.json.md)
- [resources/js/app.js], [resources/css/app.css]
