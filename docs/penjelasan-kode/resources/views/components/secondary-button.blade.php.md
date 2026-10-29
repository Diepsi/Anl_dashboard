# resources/views/components/secondary-button.blade.php

> **Path:** `resources/views/components/secondary-button.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** `profile/partials/delete-user-form.blade.php`  
> **Memanggil:** Tidak ada

## Ringkasan

Tombol sekunder (putih dengan border) untuk aksi netral seperti batal.

## Peran dalam Sistem

Komponen tombol untuk aksi sekunder/cancel.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Button | `resources/views/components/secondary-button.blade.php:1-3` | `<button>` default `type="button"`, class putih border, shadow-sm, merge atribut, isi `$slot`. |

## Alur Data

```text
Dipakai untuk aksi non-primer seperti menutup modal/cancel.
```

- **Props/atribut:** `$attributes`.
- **State JS:** Tidak ada.

## Relasi

- **Dipakai oleh:** Modal konfirmasi hapus akun (tombol Cancel).
- **Terkait:** `primary-button.blade.php`, `danger-button.blade.php`.

## Catatan Teknis

- Default `type="button"` (berbeda dari primary-button yang default submit) — cocok untuk tombol yang memicu JS/event, bukan submit form.
- Bisa di-override jadi submit jika perlu.

## Aksesibilitas

- Tombol semantik.

## Edge Case

- Sering dipakai bersamaan dengan event Alpine seperti `x-on:click="$dispatch('close')"`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bedanya dengan primary-button?  
**Jawab:** Secondary button default `type="button"` dan styling putih/border (aksi netral), primary button default `type="submit"` dan styling gelap (aksi utama).

**Tanya:** Kapan digunakan?  
**Jawab:** Untuk aksi seperti Batal, Tutup modal, atau aksi sekunder.

## File Terkait

- [components/primary-button.blade.php](primary-button.blade.php.md)
- [components/danger-button.blade.php](danger-button.blade.php.md)
- [profile/partials/delete-user-form.blade.php](../profile/partials/delete-user-form.blade.php.md)
- [components/modal.blade.php](modal.blade.php.md)
