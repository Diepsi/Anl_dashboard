# resources/views/components/danger-button.blade.php

> **Path:** `resources/views/components/danger-button.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** `profile/partials/delete-user-form.blade.php`  
> **Memanggil:** Tidak ada

## Ringkasan

Tombol bergaya bahaya (merah) untuk aksi destruktif, seperti menghapus akun.

## Peran dalam Sistem

Komponen tombol konsisten untuk aksi berisiko tinggi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Button element | `resources/views/components/danger-button.blade.php:1-3` | `<button>` dengan class merah, default `type="submit"`, merge atribut via `$attributes->merge()`, isi dari `$slot`. |

## Alur Data

```text
Dipanggil sebagai <x-danger-button>...</x-danger-button> -> 
merender button dengan styling danger.
```

- **Props/atribut:** Menggunakan `$attributes` (default type submit).
- **State JS:** Tidak ada.

## Relasi

- **Dipakai oleh:** Form hapus akun di profil.

## Catatan Teknis

- Default `type="submit"` — cocok untuk form hapus.
- Styling fokus/ring merah.
- Transisi halus.
- Dapat dioverride type via atribut jika perlu.

## Aksesibilitas

- Tombol semantik dengan teks dari slot.
- Fokus terlihat (focus ring).

## Edge Case

- Jika digunakan tanpa form, perlu memastikan type sesuai kebutuhan.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Kapan menggunakan danger-button?  
**Jawab:** Untuk aksi destruktif/permanen seperti menghapus akun.

**Tanya:** Apa default type-nya?  
**Jawab:** `submit`.

**Tanya:** Bisa digunakan untuk membuka modal?  
**Jawab:** Bisa, dengan menambahkan `type="button"` dan event handler (seperti di delete-user-form menggunakan `x-on:click.prevent`).

## File Terkait

- [profile/partials/delete-user-form.blade.php](../profile/partials/delete-user-form.blade.php.md)
- [components/secondary-button.blade.php](secondary-button.blade.php.md)
- [components/primary-button.blade.php](primary-button.blade.php.md)
