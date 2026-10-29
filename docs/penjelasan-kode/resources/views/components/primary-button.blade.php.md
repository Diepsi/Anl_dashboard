# resources/views/components/primary-button.blade.php

> **Path:** `resources/views/components/primary-button.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** Hampir semua form auth, profile  
> **Memanggil:** Tidak ada

## Ringkasan

Tombol utama dengan styling gelap untuk aksi primer.

## Peran dalam Sistem

Komponen tombol standar untuk aksi utama (submit form).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Button | `resources/views/components/primary-button.blade.php:1-3` | `<button>` default `type="submit"`, class bg-gray-800, merge atribut, isi `$slot`. |

## Alur Data

```text
Dipanggil sebagai <x-primary-button>Label</x-primary-button> -> merender tombol submit utama.
```

- **Props/atribut:** `$attributes`.
- **State JS:** Tidak ada.

## Relasi

- **Dipakai oleh:** Login, register, forgot/reset password, confirm password, profile forms.
- **Terkait:** `secondary-button.blade.php`, `danger-button.blade.php`.

## Catatan Teknis

- Default `type="submit"`.
- Fokus ring indigo.
- Dapat digunakan di luar form dengan override type.

## Aksesibilitas

- Tombol semantik.

## Edge Case

- Warna bisa berbeda dari tema aplikasi (gray-800) — konsisten dengan Breeze default.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan menggunakan primary-button?  
**Jawab:** Untuk aksi utama seperti Login, Register, Save, Reset Password.

**Tanya:** Apa default type-nya?  
**Jawab:** `submit`.

## File Terkait

- [components/secondary-button.blade.php](secondary-button.blade.php.md)
- [components/danger-button.blade.php](danger-button.blade.php.md)
- [auth/login.blade.php](../auth/login.blade.php.md)
- [profile/partials/update-profile-information-form.blade.php](../profile/partials/update-profile-information-form.blade.php.md)
