# resources/js/app.js

> **Path:** `resources/js/app.js`  
> **Jenis:** JavaScript (Entry point)  
> **Dipakai oleh:** Vite build, dimuat oleh layout Blade  
> **Memanggil:** `alpinejs`, `chart.js/auto`

## Ringkasan

Entry point JavaScript aplikasi. Mengimport Alpine.js dan Chart.js, mengekspose ke global window, memulai Alpine.

## Peran dalam Sistem

Lapisan frontend entry point. Menyediakan library global yang digunakan oleh Blade views (Alpine directives dan Chart.js).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Import Alpine | `app.js:1` | `import Alpine from 'alpinejs'` |
| Import Chart.js | `app.js:2` | `import Chart from 'chart.js/auto'` |
| Global assignment | `app.js:4-5` | `window.Alpine = Alpine`, `window.Chart = Chart` |
| Start Alpine | `app.js:7` | `Alpine.start()` |

## Alur Data

```text
Vite bundle app.js -> browser load
  -> import modules -> assign to window -> Alpine.start()
Views menggunakan Alpine.data(), x-data, dll dan window.Chart
```

## State Alpine (modal, filter, loading, error, chart)

Tidak mendefinisikan state. Hanya menginisialisasi Alpine dan membuat Chart tersedia global.

## Relasi route/controller/API

Tidak berhubungan langsung dengan route/controller/API. Digunakan oleh semua view yang memakai Alpine/Chart.js.

## Catatan Teknis

- **Chart.js auto**: mengimport semua tipe chart yang dibutuhkan.
- **Alpine global**: diperlukan karena beberapa komponen didefinisikan di inline script Blade (`Alpine.data`).
- **Vite**: file ini adalah entry point (sesuai standar Laravel Vite).

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa Alpine dan Chart.js diekspos ke window?
**Jawab:** Agar bisa digunakan oleh inline script di Blade views (misalnya `Alpine.data(...)` didefinisikan di `@push('scripts')` pada view).

**Tanya:** Kapan Alpine.start() dipanggil?
**Jawab:** Langsung saat module load, memulai Alpine untuk memproses directive di DOM.

**Tanya:** Apa yang diimport dari Chart.js?
**Jawab:** `chart.js/auto` yang mengimport semua chart type secara otomatis.

## File Terkait

- [resources/views/layouts/app.blade.php](../views/layouts/app.blade.php.md) (tempat asset dimuat)
- [vite.config.js](../../vite.config.js.md)
- [package.json](../../package.json.md)
- [resources/css/app.css](../css/app.css.md)
