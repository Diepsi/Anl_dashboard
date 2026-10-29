# app/Http/Requests/ProfileUpdateRequest.php

> **Path:** `app/Http/Requests/ProfileUpdateRequest.php`  
> **Jenis:** Form Request  
> **Dipakai oleh:** `ProfileController@update`  
> **Memanggil:** `App\Models\User`, `Illuminate\Validation\Rule`

## Ringkasan

Validasi update profil: `name` required|max:255, `email` required|lowercase|email|max:255 unique users kecuali user saat ini.

## Peran dalam Sistem

Validasi profil terautentikasi.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `rules(): array` | `17-30` | Aturan name/email dengan unique ignore id user |

## Alur Data

```text
PATCH profile -> validasi -> controller
```

## Relasi

- Controller: ProfileController
- Model: User

## Catatan Teknis

- Menggunakan `Rule::unique(User::class)->ignore($this->user()->id)`.
- Email di-lowercase.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa email di-lowercase?  
**Jawab:** Untuk konsistensi penyimpanan dan pencocokan. (`ProfileUpdateRequest.php:24`)

## File Terkait

- [ProfileController](../Controllers/ProfileController.php.md)
