# resources/views/components/input-error.blade.php

> **Path:** `resources/views/components/input-error.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** Hampir semua form auth, profile, users  
> **Memanggil:** Tidak ada

## Ringkasan

Komponen untuk menampilkan pesan error validasi. Menerima array/list pesan error dan merender sebagai list berwarna merah.

## Peran dalam Sistem

Komponen utilitas untuk menampilkan error validasi Laravel secara konsisten.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Props | `resources/views/components/input-error.blade.php:1` | `@props(['messages'])` menerima pesan error. |
| Kondisional | `resources/views/components/input-error.blade.php:3-9` | Jika `$messages` ada, render `<ul>` dengan styling merah, loop `foreach ((array)$messages as $message)`, tampilkan tiap pesan di `<li>`. |

## Alur Data

```text
Validasi gagal -> controller share $errors (ViewErrorBag) -> 
view memanggil <x-input-error :messages="$errors->get('field')" /> -> 
komponen merender pesan error.
```

- **Props:** `messages` (bisa string atau array).
- **State JS:** Tidak ada.

## Relasi

- **Dipakai oleh:** Semua form di auth, profile, users/form.
- **Terkait:** Laravel validation errors.

## Catatan Teknis

- Menggunakan `((array)$messages)` untuk menangani baik string maupun array.
- Styling: `text-sm text-red-600 space-y-1`.
- `$attributes->merge()` memungkinkan penambahan class/margin.

## Aksesibilitas

- Pesan error dalam list, jelas dibaca.

## Edge Case

- Jika messages kosong/null -> tidak dirender.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana cara pakai komponen ini?  
**Jawab:** `<x-input-error :messages="$errors->get('email')" class="mt-2" />`

**Tanya:** Apakah bisa menerima multiple error?  
**Jawab:** Ya, jika ada multiple pesan akan dirender sebagai list item terpisah.

**Tanya:** Mengapa dikonversi jadi array?  
**Jawab:** Untuk fleksibilitas, bisa menerima string atau array pesan.

## File Terkait

- [auth/login.blade.php](../auth/login.blade.php.md)
- [auth/register.blade.php](../auth/register.blade.php.md)
- [profile/partials/update-profile-information-form.blade.php](../profile/partials/update-profile-information-form.blade.php.md)
- [users/form.blade.php](../users/form.blade.php.md)
- [components/input-label.blade.php](input-label.blade.php.md)
- [components/text-input.blade.php](text-input.blade.php.md)
