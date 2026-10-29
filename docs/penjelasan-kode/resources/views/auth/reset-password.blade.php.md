# resources/views/auth/reset-password.blade.php

> **Path:** `resources/views/auth/reset-password.blade.php`  
> **Jenis:** View  
> **Dipakai oleh:** Route `password.reset` (GET), route `password.store` (POST)  
> **Memanggil:** Komponen `<x-guest-layout>`, `<x-input-label>`, `<x-text-input>`, `<x-input-error>`, `<x-primary-button>`, route `password.store`

## Ringkasan

Form untuk mengatur password baru setelah pengguna mengklik link reset dari email. Memuat token reset dan email dari request.

## Peran dalam Sistem

Lapisan presentasi alur reset password (Breeze).

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Layout tamu | `resources/views/auth/reset-password.blade.php:1` | Menggunakan `<x-guest-layout>`. |
| Form reset | `resources/views/auth/reset-password.blade.php:2-38` | POST ke `route('password.store')`, `@csrf`. |
| Token tersembunyi | `resources/views/auth/reset-password.blade.php:6` | `<input type="hidden" name="token" value="{{ $request->route('token') }}">` — token dari URL. |
| Email | `resources/views/auth/reset-password.blade.php:9-13` | Input email, nilai diisi dari `old('email', $request->email)`, `autofocus`, wajib. |
| Password baru | `resources/views/auth/reset-password.blade.php:16-20` | Password wajib, `autocomplete="new-password"`. |
| Konfirmasi password | `resources/views/auth/reset-password.blade.php:23-31` | Konfirmasi wajib, `autocomplete="new-password"`. |
| Tombol reset | `resources/views/auth/reset-password.blade.php:33-37` | `<x-primary-button>` berlabel `Reset Password`. |

## Alur Data

```text
User klik link reset dari email (/reset-password/{token}) -> 
route password.reset menampilkan view dengan $request (berisi token/email) -> 
form submit ke password.store dengan token, email, password, password_confirmation -> 
controller validasi token & simpan password baru.
```

- **Data dari controller/request:** `$request->route('token')`, `$request->email`, `old()`, `$errors`.
- **Form:** `method="POST"`, `action="{{ route('password.store') }}"`, `@csrf`.
- **State JS:** Tidak ada.

## Relasi

- **Route:** `GET /reset-password/{token}` (`password.reset`), `POST /reset-password` (`password.store`).
- **Controller:** NewPasswordController (Breeze).
- **View:** `layouts/guest.blade.php`.
- **Middleware:** `guest`.

## Catatan Teknis

- Token dikirim sebagai hidden input dari route parameter.
- Email di-prefill dari query/route jika tersedia (`$request->email`) dengan fallback `old()`.
- Menggunakan autocomplete yang tepat untuk password baru.
- Error ditampilkan per field.

## Aksesibilitas

- Label terhubung, field password menggunakan autocomplete yang benar.

## Edge Case

- Token tidak valid/kadaluarsa -> error dari controller.
- Email tidak cocok dengan token -> error.
- Password tidak sesuai konfirmasi -> error `password_confirmation`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Dari mana token didapat?  
**Jawab:** Dari parameter route `{token}` pada URL reset password, dimasukkan sebagai hidden input.

**Tanya:** Mengapa email bisa terisi otomatis?  
**Jawab:** Diambil dari `$request->email` (biasanya dikirim dalam link) dengan prioritas `old()` jika ada validasi gagal.

**Tanya:** Route tujuan submit form?  
**Jawab:** `POST` ke `route('password.store')`.

## File Terkait

- [layouts/guest.blade.php](../layouts/guest.blade.php.md)
- [components/input-label.blade.php](../components/input-label.blade.php.md)
- [components/text-input.blade.php](../components/text-input.blade.php.md)
- [components/input-error.blade.php](../components/input-error.blade.php.md)
- [components/primary-button.blade.php](../components/primary-button.blade.php.md)
- [routes/auth.php (peta route)](../../../03-peta-route-dan-halaman.md)
