# Feature/ExampleTest

> **Path:** `tests/Feature/ExampleTest.php`
> **Jenis:** Test
> **Dipakai oleh:** PHPUnit test suite
> **Memanggil:** `Tests\TestCase`, route `GET /`

## Ringkasan

Test feature minimal yang memanggil route root `/` dan mengharapkan status 200. Merupakan contoh bawaan; perilakunya bergantung pada route yang terdaftar saat ini.

## Peran dalam Sistem

Placeholder untuk memastikan aplikasi dapat di-boot. Namun pada proyek ini, halaman root biasanya dilindungi auth (dashboard). Dengan setup test saat ini, bisa gagal jika middleware auth diterapkan.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `test_the_application_returns_a_successful_response()` | `tests/Feature/ExampleTest.php:13-18` | GET `/` lalu `assertStatus(200)` |

## Alur Arrange-Act-Assert

1. **Arrange:** Tidak ada setup khusus (tidak membuat user, tidak menggunakan `RefreshDatabase`).
2. **Act:** `$this->get('/')`
3. **Assert:** `$response->assertStatus(200)`

## Relasi

- Route: `GET /` (kemungkinan `DashboardController@index` atau route web)
- Model/tabel: -
- View: -
- Service: -
- Test: Contoh

## Catatan Teknis

- Menggunakan `Tests\TestCase` (Laravel test case) tetapi **tidak** menggunakan trait `RefreshDatabase` dan barisnya dikomentari.
- Tidak login/auth. Jika route `/` dilindungi `auth` middleware, Laravel akan mengarahkan ke `/login` (redirect 302) dan test ini akan **gagal** (`assertStatus(200)` tidak terpenuhi).
- Berdasarkan `routes/console.php` dan deskripsi proyek, dashboard berada di `/` dan halaman operasional memakai middleware `auth`. Ini menjelaskan potensi kegagalan.
- Tidak relevan dengan perilaku bisnis ANL Dashboard yang diuji oleh test lain.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah test ini masih relevan?  
**Jawab:** Saat ini kurang relevan. Karena `/` dilindungi autentikasi, test bisa return 302 redirect ke login, bukan 200. Perlu disesuaikan (login user) atau dihapus.

**Tanya:** Kenapa bisa gagal (redirect/404)?  
**Jawab:** 
- **Redirect 302:** Middleware `auth` memaksa redirect ke route login jika tidak diautentikasi.
- **404:** Jika route belum terdaftar atau berbeda.
- **500:** Jika ada dependency yang belum di-setup di lingkungan test.

**Tanya:** Bagaimana seharusnya diperbaiki?  
**Jawab:** Jika ingin tetap ada, buat user via factory, `actingAs($user)`, lalu akses `/` dan sesuaikan assertion sesuai perilaku (bisa 200 untuk dashboard).

## File Terkait

- [tests/TestCase.php.md](../TestCase.php.md)
- [tests/Unit/ExampleTest.php.md](../Unit/ExampleTest.php.md)
- [00-ringkasan-arsitektur.md](../../00-ringkasan-arsitektur.md)
- [03-peta-route-dan-halaman.md](../../03-peta-route-dan-halaman.md) (jika ada)
