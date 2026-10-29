# Feature/Auth/EmailVerificationTest

> **Path:** `tests/Feature/Auth/EmailVerificationTest.php`
> **Jenis:** Test
> **Dipakai oleh:** Verifikasi email (Breeze)
> **Memanggil:** `App\Models\User`, `Illuminate\Auth\Events\Verified`, `Illuminate\Support\Facades\Event`, `Illuminate\Support\Facades\URL`, route verification

## Ringkasan

Menguji render halaman verifikasi email, verifikasi berhasil dengan signed URL valid, dan verifikasi gagal dengan hash tidak valid.

## Peran dalam Sistem

Memastikan alur verifikasi email berfungsi: user belum terverifikasi bisa akses halaman verifikasi, link bertanda tangan valid memverifikasi dan memicu event, link dengan hash salah tidak memverifikasi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `test_email_verification_screen_can_be_rendered()` | `tests/Feature/Auth/EmailVerificationTest.php:16-23` | User unverified `actingAs` GET `/verify-email` -> 200 |
| `test_email_can_be_verified()` | `tests/Feature/Auth/EmailVerificationTest.php:25-42` | Buat signed URL valid, GET -> `Verified` event dispatched, `hasVerifiedEmail()` true, redirect dashboard dengan `?verified=1` |
| `test_email_is_not_verified_with_invalid_hash()` | `tests/Feature/Auth/EmailVerificationTest.php:44-57` | Signed URL dengan hash salah -> email tidak terverifikasi |

## Alur Arrange-Act-Assert

1. **Render:** Arrange - `User::factory()->unverified()->create()` + actingAs; Act - GET `/verify-email`; Assert - 200
2. **Verifikasi sukses:** Arrange - user unverified, `Event::fake()`, buat `URL::temporarySignedRoute('verification.verify', ...)` dengan `hash => sha1($user->email)`; Act - GET URL; Assert - event dispatched, `fresh()->hasVerifiedEmail()`, redirect
3. **Hash salah:** Arrange - user unverified, buat URL dengan `sha1('wrong-email')`; Act - GET; Assert - tidak terverifikasi

## Relasi

- Route: `GET /verify-email`, `GET /verify-email/{id}/{hash}` (named `verification.verify`)
- Model/tabel: `users` (`email_verified_at`)
- Event: `Illuminate\Auth\Events\Verified`
- Test: Auth suite

## Catatan Teknis

- Menggunakan `RefreshDatabase`.
- Menggunakan `URL::temporarySignedRoute` untuk membuat signed URL yang valid (mimic link email).
- `Event::fake()` digunakan untuk assert dispatch event.
- Hash dihitung dengan `sha1($user->email)` seperti yang diharapkan verifikasi email Laravel.
- Redirect setelah verifikasi: `route('dashboard', absolute: false).'?verified=1'`.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa yang terjadi jika URL tidak signed/tidak valid?  
**Jawab:** Middleware signed akan menolak; test ini hanya menguji hash salah (masih signed route dengan hash berbeda).

**Tanya:** Apakah user harus sudah login untuk verifikasi?  
**Jawab:** Ya, `actingAs($user)` digunakan - verifikasi email biasanya memerlukan user terautentikasi.

**Tanya:** Mengapa event `Verified` penting?  
**Jawab:** Dapat digunakan untuk listener (logging, welcome email, dsb). Test memastikan event dipicu.

## File Terkait

- [tests/Feature/Auth/AuthenticationTest.php.md](AuthenticationTest.php.md)
- [tests/TestCase.php.md](../../TestCase.php.md)
