# resources/views/components/text-input.blade.php

> **Path:** `resources/views/components/text-input.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** Semua form auth, profile, users  
> **Memanggil:** Tidak ada

## Ringkasan

Komponen input text dengan styling Tailwind konsisten dan dukungan disabled.

## Peran dalam Sistem

Komponen input standar untuk form.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Props | `resources/views/components/text-input.blade.php:1` | `@props(['disabled' => false])`. |
| Input | `resources/views/components/text-input.blade.php:3` | `<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>` — merge class, teruskan atribut lain (type, name, id, value, etc). |

## Alur Data

```text
Dipanggil dengan atribut input standar -> merender input dengan styling konsisten.
```

- **Props:** `disabled`.
- **State JS:** Tidak ada.

## Relasi

- **Dipakai oleh:** Hampir semua form di aplikasi.
- **Terkait:** `input-label.blade.php`, `input-error.blade.php`.

## Catatan Teknis

- Menggunakan `@disabled($disabled)` directive.
- `$attributes->merge()` untuk menggabungkan class tanpa menimpa sepenuhnya.
- Styling fokus ring indigo.

## Aksesibilitas

- Input semantik, atribut seperti required/autocomplete diteruskan.

## Edge Case

- Dapat digunakan untuk berbagai type (text, email, password) lewat atribut `type`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana cara disable input?  
**Jawab:** Lewat prop `:disabled="true"` atau atribut `disabled`.

**Tanya:** Apakah bisa ganti type input?  
**Jawab:** Ya, lewat atribut `type="email"`, `type="password"`, dll karena `$attributes` diteruskan.

## File Terkait

- [components/input-label.blade.php](input-label.blade.php.md)
- [components/input-error.blade.php](input-error.blade.php.md)
- [auth/login.blade.php](../auth/login.blade.php.md)
- [users/form.blade.php](../users/form.blade.php.md)
- [profile/partials/update-profile-information-form.blade.php](../profile/partials/update-profile-information-form.blade.php.md)
