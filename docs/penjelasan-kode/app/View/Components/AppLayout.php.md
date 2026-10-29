# app/View/Components/AppLayout.php

> **Path:** `app/View/Components/AppLayout.php`  
> **Jenis:** View Component  
> **Dipakai oleh:** Blade views (halaman terautentikasi)  
> **Memanggil:** `Illuminate\View\Component`, `Illuminate\View\View`, view `layouts.app`

## Ringkasan

Komponen Blade untuk layout aplikasi terautentikasi. Method `render()` mengembalikan view `layouts.app`.

## Peran dalam Sistem

Berada di lapisan **Presentasi/View Component**. Menyediakan komponen `<x-app-layout>` untuk halaman yang membutuhkan layout utama.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `render()` | `AppLayout.php:13-16` | Return `view('layouts.app')`. |

## Alur Data (input -> proses -> output)

```text
Blade: <x-app-layout> -> Component render
  -> layouts.app
Output: HTML layout
```

## Relasi

- **View:** `resources/views/layouts/app.blade.php`.
- **Route:** digunakan oleh halaman auth (dashboard, pengiriman, clustering, manajemen user, dll).

## Catatan Teknis, Edge Case, Keputusan Desain

- **Standar Breeze:** pola komponen layout Laravel Breeze.

## Kemungkinan Pertanyaan Penguji + Jawaban

**Tanya:** Apa fungsi AppLayout component?  
**Jawab:** Mengarahkan ke view layout `layouts.app` untuk halaman terautentikasi.

## File Terkait

- [GuestLayout](GuestLayout.php.md)
- [00-ringkasan-arsitektur](../../../00-ringkasan-arsitektur.md)
