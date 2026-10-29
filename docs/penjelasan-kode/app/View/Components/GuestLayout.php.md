# app/View/Components/GuestLayout.php

> **Path:** `app/View/Components/GuestLayout.php`  
> **Jenis:** View Component  
> **Dipakai oleh:** Blade views (halaman guest/login)  
> **Memanggil:** `Illuminate\View\Component`, `Illuminate\View\View`, view `layouts.guest`

## Ringkasan

Komponen Blade untuk layout tamu/guest. Method `render()` mengembalikan view `layouts.guest`.

## Peran dalam Sistem

Berada di lapisan **Presentasi/View Component**. Menyediakan komponen `<x-guest-layout>` untuk halaman login/register/reset password.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `render()` | `GuestLayout.php:13-16` | Return `view('layouts.guest')`. |

## Alur Data (input -> proses -> output)

```text
Blade: <x-guest-layout> -> Component render
  -> layouts.guest
Output: HTML layout guest
```

## Relasi

- **View:** `resources/views/layouts/guest.blade.php`.
- **Route:** digunakan halaman auth guest (login, dll).

## Catatan Teknis, Edge Case, Keputusan Desain

- **Standar Breeze:** pola komponen layout Laravel Breeze.

## Kemungkinan Pertanyaan Penguji + Jawaban

**Tanya:** Kapan GuestLayout digunakan?  
**Jawab:** Untuk halaman tanpa autentikasi seperti login/register menggunakan komponen `<x-guest-layout>` yang merender `layouts.guest`.

## File Terkait

- [AppLayout](AppLayout.php.md)
- [00-ringkasan-arsitektur](../../../00-ringkasan-arsitektur.md)
