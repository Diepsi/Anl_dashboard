# resources/views/profile/partials/delete-user-form.blade.php

> **Path:** `resources/views/profile/partials/delete-user-form.blade.php`  
> **Jenis:** View/Partial  
> **Dipakai oleh:** `profile/edit.blade.php`  
> **Memanggil:** `<x-danger-button>`, `<x-modal>`, `<x-input-label>`, `<x-text-input>`, `<x-input-error>`, `<x-secondary-button>`, route `profile.destroy`

## Ringkasan

Form untuk menghapus akun pengguna sendiri dengan konfirmasi password via modal. Aksi bersifat destruktif dan permanen.

## Peran dalam Sistem

Bagian profil untuk penghapusan akun dengan perlindungan konfirmasi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Section | `resources/views/profile/partials/delete-user-form.blade.php:1` | Wrapper section. |
| Header | `resources/views/profile/partials/delete-user-form.blade.php:2-10` | Judul "Delete Account" dan penjelasan konsekuensi penghapusan. |
| Tombol buka modal | `resources/views/profile/partials/delete-user-form.blade.php:12-15` | `<x-danger-button>` dengan Alpine `x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"`. |
| Modal konfirmasi | `resources/views/profile/partials/delete-user-form.blade.php:17` | `<x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>` — buka otomatis jika ada error di bag userDeletion. |
| Form hapus | `resources/views/profile/partials/delete-user-form.blade.php:18-53` | Form POST ke `route('profile.destroy')`, `@csrf`, `@method('delete')`. |
| Konfirmasi teks | `resources/views/profile/partials/delete-user-form.blade.php:22-28` | Penjelasan dan permintaan password. |
| Input password | `resources/views/profile/partials/delete-user-form.blade.php:30-42` | Input password dengan label sr-only, error dari `$errors->userDeletion->get('password')`. |
| Tombol aksi | `resources/views/profile/partials/delete-user-form.blade.php:44-52` | Tombol Cancel (`<x-secondary-button>` dengan `x-on:click="$dispatch('close')"`) dan Delete (`<x-danger-button>`). |

## Alur Data

```text
User klik "Delete Account" -> dispatch open-modal -> modal muncul -> 
user masukkan password konfirmasi -> submit form DELETE ke profile.destroy -> 
controller validasi password -> jika valid hapus akun -> logout/redirect.
```

- **Form:** `method="post"`, `action="{{ route('profile.destroy') }}"`, `@csrf`, `@method('delete')`.
- **Error bag:** `$errors->userDeletion` (Breeze menggunakan error bag terpisah untuk mencegah benturan).
- **State JS:** Alpine via modal component.
- **Validasi:** Membutuhkan password untuk konfirmasi.

## Relasi

- **Route:** `DELETE /profile` (`profile.destroy`).
- **Controller:** `ProfileController@destroy`.
- **Komponen:** `danger-button`, `modal`, `input-label`, `text-input`, `input-error`, `secondary-button`.
- **Partial induk:** `profile/edit.blade.php`.

## Catatan Teknis

- **Keamanan:** Konfirmasi password wajib, aksi destruktif.
- **Error handling:** Modal otomatis terbuka jika ada error validasi (via `:show="$errors->userDeletion->isNotEmpty()"`).
- **Aksesibilitas:** Label password pakai `class="sr-only"`, modal punya `focusable`.
- **UX:** Tombol cancel untuk menutup modal tanpa aksi.

## Aksesibilitas

- Label disembunyikan secara visual tapi tersedia untuk screen reader.
- Modal dengan fokus trap.
- Tombol jelas terpisah (Cancel vs Delete).

## Edge Case

- Password salah -> error ditampilkan di modal, modal tetap terbuka.
- User bisa membatalkan kapan saja.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa butuh password untuk menghapus akun?  
**Jawab:** Sebagai konfirmasi keamanan untuk aksi destruktif/permanen.

**Tanya:** Bagaimana modal bisa terbuka otomatis saat error?  
**Jawab:** Karena prop `:show="$errors->userDeletion->isNotEmpty()"` — jika ada error di error bag userDeletion, modal akan ditampilkan.

**Tanya:** Method HTTP apa yang digunakan?  
**Jawab:** DELETE (via `@method('delete')`).

## File Terkait

- [profile/edit.blade.php](../edit.blade.php.md)
- [components/modal.blade.php](../../components/modal.blade.php.md)
- [components/danger-button.blade.php](../../components/danger-button.blade.php.md)
- [components/secondary-button.blade.php](../../components/secondary-button.blade.php.md)
- [components/text-input.blade.php](../../components/text-input.blade.php.md)
- [components/input-error.blade.php](../../components/input-error.blade.php.md)
- [components/input-label.blade.php](../../components/input-label.blade.php.md)
- [03-peta-route-dan-halaman.md](../../../../03-peta-route-dan-halaman.md)
