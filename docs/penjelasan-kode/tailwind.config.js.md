# tailwind.config.js

> **Path:** `tailwind.config.js`  
> **Jenis:** Config  
> **Dipakai oleh:** Tailwind CSS  
> **Memanggil:** `tailwindcss/defaultTheme`, `@tailwindcss/forms`

## Ringkasan

Konfigurasi Tailwind: content paths untuk purge, font family Figtree, plugin forms.

## Peran dalam Sistem

Styling configuration.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| content | `tailwind.config.js:6-10` | Scan blade views dan framework views. |
| theme.extend.fontFamily | `:13-17` | Font sans menggunakan Figtree. |
| plugins | `:20` | `forms` plugin. |

## Alur Data

```text
Tailwind scan content -> generate CSS
```

## Relasi

- Route: -
- Model/tabel: -
- View: semua blade views
- Service: -
- Test: -

## Catatan Teknis

- Menggunakan Tailwind CSS 3.x config style.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Plugin apa yang digunakan?  
**Jawab:** `@tailwindcss/forms`.

## File Terkait

- [postcss.config.js.md](postcss.config.js.md)
- [package.json.md](package.json.md)
