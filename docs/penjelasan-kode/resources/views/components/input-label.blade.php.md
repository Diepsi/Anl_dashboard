# resources/views/components/input-label.blade.php

> **Path:** `resources/views/components/input-label.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** Semua form auth, profile, users  
> **Memanggil:** Tidak ada

## Ringkasan

Komponen label untuk input form dengan styling konsisten.

## Peran dalam Sistem

Komponen label standar untuk aksesibilitas form.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Props | `resources/views/components/input-label.blade.php:1` | `@props(['value'])` menerima nilai label. |
| Label element | `resources/views/components/input-label.blade.php:3-5` | `<label>` dengan class, menampilkan `$value ?? $slot` sehingga bisa dipakai via prop atau slot. |

## Alur Data

```text
Dipanggil dengan :value atau isi slot -> merender label terhubung ke input via for (ditentukan pemanggil).
```

- **Props:** `value` (opsional).
- **State JS:** Tidak ada.

## Relasi

- **Dipakai oleh:** Hampir semua input di form aplikasi.
- **Terkait:** `input-error.blade.php`, `text-input.blade.php`.

## Catatan Teknis

- Fleksibel: bisa pakai `:value="__('Email')"` atau langsung isi slot.
- `$attributes->merge()` memungkinkan menambah class.
- Styling: `block font-medium text-sm text-gray-700`.

## Aksesibilitas

- Label semantik untuk menghubungkan dengan input (perlu `for` yang sesuai dari pemanggil).

## Edge Case

- Jika tidak ada value dan slot kosong, label akan kosong.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bisa dipakai dengan cara apa saja?  
**Jawab:** Via prop `:value` atau via slot content.

**Tanya:** Apakah otomatis terhubung ke input?  
**Jawab:** Tidak otomatis, pemanggil harus mengatur `for` pada label dan `id` pada input.

## File Terkait

- [components/text-input.blade.php](text-input.blade.php.md)
- [components/input-error.blade.php](input-error.blade.php.md)
- [auth/login.blade.php](../auth/login.blade.php.md)
- [profile/partials/update-profile-information-form.blade.php](../profile/partials/update-profile-information-form.blade.php.md)
