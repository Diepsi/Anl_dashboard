# Unit/ExampleTest

> **Path:** `tests/Unit/ExampleTest.php`
> **Jenis:** Test
> **Dipakai oleh:** PHPUnit test suite
> **Memanggil:** `PHPUnit\Framework\TestCase`

## Ringkasan

Test unit minimal yang hanya mengassert `true === true`. Merupakan contoh bawaan Laravel/PHPUnit.

## Peran dalam Sistem

Berfungsi sebagai placeholder/unit test contoh. Tidak menguji kode aplikasi ANL Dashboard.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `test_that_true_is_true()` | `tests/Unit/ExampleTest.php:12-15` | Assertion dasar `assertTrue(true)` |

## Alur Data

```text
PHPUnit menjalankan test -> assertTrue(true) -> lulus
```

## Relasi

- Route: -
- Model/tabel: -
- View: -
- Service: -
- Test: Independent

## Catatan Teknis

- Menggunakan `PHPUnit\Framework\TestCase` langsung (bukan `Tests\TestCase` Laravel), jadi tidak bootstrapping aplikasi Laravel penuh.
- Tidak menggunakan `RefreshDatabase`, tidak membuat model, tidak melakukan HTTP request.
- Sangat minimal; tidak relevan dengan logika bisnis ANL Dashboard.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah test ini menguji fungsionalitas aplikasi?  
**Jawab:** Tidak. Ini hanya contoh PHPUnit dasar.

**Tanya:** Perlu dipertahankan atau bisa dihapus?  
**Jawab:** Bisa dihapus jika ingin test suite lebih bersih. Saat ini tidak merusak, tapi tidak memberikan nilai pengujian untuk aplikasi ini.

**Tanya:** Mengapa berbeda dari Feature/ExampleTest?  
**Jawab:** Unit test tidak memuat framework Laravel; Feature test memuat aplikasi Laravel via `Tests\TestCase`.

## File Terkait

- [tests/TestCase.php.md](../TestCase.php.md)
- [tests/Feature/ExampleTest.php.md](../Feature/ExampleTest.php.md)
