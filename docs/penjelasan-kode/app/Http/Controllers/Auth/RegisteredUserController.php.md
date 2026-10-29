# app/Http/Controllers/Auth/RegisteredUserController.php

> **Path:** `app/Http/Controllers/Auth/RegisteredUserController.php`  
> **Jenis:** Controller (Auth)  
> **Dipakai oleh:** Route register  
> **Memanggil:** `App\Http\Controllers\Controller`, `App\Models\User`, `Illuminate\Auth\Events\Registered`, `Illuminate\Support\Facades\Auth`, `Illuminate\Support\Facades\Hash`, `Illuminate\Validation\Rules`

## Ringkasan

Menampilkan form registrasi dan membuat user baru, fire event `Registered`, login otomatis, redirect ke dashboard.

## Peran dalam Sistem

Bagian registrasi Laravel Breeze.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `create(): View` | `21-24` | Form register |
| `store(Request $request): RedirectResponse` | `31-50` | Validasi, buat user ter-hash, event, login, redirect |

## Alur Data

```text
GET/POST register -> validasi -> User::create(Hash::make) -> event Registered -> Auth::login -> redirect dashboard
```

## Relasi

- Route: register
- Event: Registered
- Model: User

## Catatan Teknis

- Password di-hash manual.
- Langsung login setelah registrasi.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah role/is_active di-set saat registrasi?  
**Jawab:** Tidak secara eksplisit di controller; mengandalkan default dari migration/model. (`RegisteredUserController.php:39-43`)

## File Terkait

- [AuthenticatedSessionController](AuthenticatedSessionController.php.md)
