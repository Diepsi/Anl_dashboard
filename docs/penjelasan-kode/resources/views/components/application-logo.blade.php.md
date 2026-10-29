# resources/views/components/application-logo.blade.php

> **Path:** `resources/views/components/application-logo.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** `layouts/guest.blade.php`  
> **Memanggil:** Tidak ada (SVG statis)

## Ringkasan

Komponen SVG logo aplikasi (grafis berbentuk bangunan/struktur) yang dapat menerima atribut tambahan via `$attributes`.

## Peran dalam Sistem

Komponen presentasi untuk menampilkan logo di layout tamu.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| SVG root | `resources/views/components/application-logo.blade.php:1` | `<svg viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>` — menerima class/atribut dari pemanggil. |
| Path grafis | `resources/views/components/application-logo.blade.php:2-3` | Definisi path SVG logo (isi panjang). |

## Alur Data

```text
Komponen dipanggil <x-application-logo class="..."> -> 
merender SVG dengan atribut yang digabungkan -> 
ditampilkan di view pemanggil.
```

- **Props/atribut:** Menggunakan `$attributes` (tanpa `@props` eksplisit) untuk meneruskan class/style.
- **State JS:** Tidak ada.

## Relasi

- **Dipakai oleh:** `layouts/guest.blade.php` baris 21: `<x-application-logo class="w-20 h-20 fill-current text-gray-500" />`
- **Terkait:** Branding aplikasi.

## Catatan Teknis

- Menggunakan `{{ $attributes }}` sehingga pemanggil bisa mengatur ukuran/warna via class Tailwind.
- Viewbox 316x316.
- Konten SVG cukup panjang (path kompleks).

## Aksesibilitas

- SVG dekoratif atau informatif tergantung konteks; saat dipakai sebagai logo link di guest layout, konteks link sudah ada (`<a href="/">`).

## Edge Case

- Jika tidak ada class, akan merender SVG default tanpa styling khusus.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana cara mengatur ukuran logo?  
**Jawab:** Lewat atribut class, mis. `class="w-20 h-20 ..."`, karena `$attributes` diteruskan ke SVG.

**Tanya:** Di mana komponen ini dipakai?  
**Jawab:** Di `layouts/guest.blade.php` sebagai logo yang dilingkupi link ke `/`.

**Tanya:** Apakah komponen ini menerima props?  
**Jawab:** Tidak menggunakan `@props`, menggunakan `$attributes` untuk fleksibilitas.

## File Terkait

- [layouts/guest.blade.php](../layouts/guest.blade.php.md)
- [layouts/app.blade.php](../layouts/app.blade.php.md)
- [layouts/navigation.blade.php](../layouts/navigation.blade.php.md)
