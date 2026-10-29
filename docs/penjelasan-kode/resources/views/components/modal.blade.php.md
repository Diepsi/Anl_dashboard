# resources/views/components/modal.blade.php

> **Path:** `resources/views/components/modal.blade.php`  
> **Jenis:** View/Component (anonymous component)  
> **Dipakai oleh:** `profile/partials/delete-user-form.blade.php`  
> **Memanggil:** Alpine.js (`x-data`, `x-show`, `x-transition`, `x-init`, `x-watch`, event `open-modal`, `close-modal`)

## Ringkasan

Komponen modal dengan Alpine.js, dukungan fokus trap, penutupan via ESC/click outside, animasi transisi, dan penguncian scroll body.

## Peran dalam Sistem

Komponen dialog modal untuk konfirmasi aksi sensitif.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Props | `resources/views/components/modal.blade.php:1-5` | `name` (wajib), `show` (default false), `maxWidth` (default '2xl'). |
| Max width mapping | `resources/views/components/modal.blade.php:7-15` | Mapping maxWidth ke class Tailwind. |
| Alpine state & logic | `resources/views/components/modal.blade.php:18-33` | State `show`, fungsi `focusables()`, `firstFocusable()`, `lastFocusable()`, navigasi tab. |
| Init & watchers | `resources/views/components/modal.blade.php:34-41` | Watch `show`: tambahkan/hapus class `overflow-y-hidden` pada body; fokus otomatis jika ada atribut `focusable`. |
| Event listeners | `resources/views/components/modal.blade.php:42-47` | Dengarkan `open-modal`/`close-modal` window events (filter by name), tutup via ESC/stop propagation, trap tab. |
| Overlay | `resources/views/components/modal.blade.php:52-64` | Backdrop semi-transparan, klik untuk tutup. |
| Modal panel | `resources/views/components/modal.blade.php:66-77` | Panel putih rounded, shadow, transisi scale/translate, isi dari `$slot`. |

## Alur Data

```text
Trigger dispatch event('open-modal', 'confirm-user-deletion') -> 
modal mendengarkan dan set show=true -> 
body overflow hidden, fokus dipindah jika focusable -> 
user interaksi (tab/ESC/klik overlay/tombol) -> 
dispatch close atau set show=false -> 
body overflow dikembalikan.
```

- **Props:** `name`, `show`, `maxWidth`.
- **Atribut khusus:** bisa menambahkan `focusable` pada komponen untuk auto-focus.
- **Slots:** `$slot` untuk isi modal.
- **State JS:** Alpine.js kompleks (focus management).

## Relasi

- **Dipakai oleh:** `profile/partials/delete-user-form.blade.php` (modal konfirmasi hapus akun).
- **Event:** Menggunakan custom events `open-modal` dan `close-modal` dengan detail nama modal.
- **Framework:** Alpine.js.

## Catatan Teknis

- Fokus trap untuk aksesibilitas keyboard.
- Mencegah scroll body saat modal terbuka.
- Transisi smooth enter/leave.
- Dapat dibuka via event global atau prop `show`.
- `style="display: {{ $show ? 'block' : 'none' }};"` untuk initial state.

## Aksesibilitas

- Fokus otomatis ke elemen focusable pertama jika diminta.
- Trap tab (circular) dalam modal.
- Penutupan dengan ESC.
- Overlay bisa diklik untuk tutup.
- Perlu memastikan elemen focusable ada saat `focusable` digunakan.

## Edge Case

- Jika tidak ada elemen focusable, fungsi focus bisa error (perlu hati-hati).
- Multiple modal dengan nama berbeda bisa dikontrol terpisah.
- Scroll lock pada body.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana cara membuka modal ini?  
**Jawab:** Dispatch event `window.dispatchEvent(new CustomEvent('open-modal', { detail: 'nama-modal' }))` atau set prop `:show="true"`.

**Tanya:** Bagaimana fokus dikelola?  
**Jawab:** Alpine mencari semua elemen focusable, melakukan trap tab, dan bisa auto-focus ke elemen pertama jika atribut `focusable` ada.

**Tanya:** Apa yang terjadi saat modal terbuka?  
**Jawab:** Class `overflow-y-hidden` ditambahkan ke body untuk mencegah scroll background.

## File Terkait

- [profile/partials/delete-user-form.blade.php](../profile/partials/delete-user-form.blade.php.md)
- [components/danger-button.blade.php](danger-button.blade.php.md)
- [components/secondary-button.blade.php](secondary-button.blade.php.md)
- [resources/js/app.js](../../js/app.js.md)
- [layouts/app.blade.php](../layouts/app.blade.php.md)
