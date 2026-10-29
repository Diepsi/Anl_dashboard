# TestCase

> **Path:** `tests/TestCase.php`
> **Jenis:** Test
> **Dipakai oleh:** Seluruh test (Feature/Unit) yang mewarisi `Tests\TestCase`
> **Memanggil:** `Illuminate\Foundation\Testing\TestCase`, `Illuminate\Support\Facades\DB`

## Ringkasan

`tests/TestCase.php` adalah base class untuk semua pengujian Laravel. Kelas ini memperluas `Illuminate\Foundation\Testing\TestCase` dan menambahkan penanganan kompatibilitas SQLite untuk fungsi `DATEDIFF` yang digunakan oleh query agregasi MySQL.

## Peran dalam Sistem

Bertindak sebagai fondasi test suite. Ketika menggunakan driver `sqlite` (in-memory/testing), kelas ini mendaftarkan fungsi SQLite kustom `DATEDIFF(left, right)` agar query reporting berbasis MySQL tetap berjalan tanpa perlu menulis ulang query per driver database.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `setUp()` | `tests/TestCase.php:10-41` | Inisialisasi test, deteksi driver, registrasi fungsi `DATEDIFF` untuk SQLite |
| SQLite `DATEDIFF` emulation | `tests/TestCase.php:23-40` | Menghitung selisih hari (integer) dengan membandingkan tanggal kalender (tanpa waktu) |

## Alur Data

```text
TestCase di-extend -> setUp() dipanggil -> cek driver SQLite -> jika ya, sqliteCreateFunction('DATEDIFF', ...) didaftarkan -> query menggunakan DATEDIFF tetap berjalan
```

## Relasi

- Route: -
- Model/tabel: -
- View: -
- Service: -
- Test: Semua test yang extends `Tests\TestCase`

## Catatan Teknis

- Hanya aktif untuk driver SQLite. Driver lain (MySQL) tidak dimodifikasi.
- Fungsi menghitung selisih berdasarkan hari kalender (`date('Y-m-d')`), sesuai perilaku MySQL `DATEDIFF` untuk tanggal.
- Mengembalikan `null` jika salah satu argumen kosong/tidak valid.
- Hasil dibulatkan dengan `round()` dan dikembalikan sebagai integer.
- Tidak menggunakan `RefreshDatabase` di base class; trait tersebut digunakan oleh test turunan sesuai kebutuhan.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Mengapa perlu emulasi `DATEDIFF` untuk SQLite?  
**Jawab:** Query laporan (dashboard/aggregasi) ditulis dengan fungsi MySQL `DATEDIFF`. Tes berjalan dengan SQLite in-memory; tanpa emulasi, query tersebut akan gagal. Fungsi ini menjaga query asli tetap diuji.

**Tanya:** Apakah `TestCase` otomatis membersihkan database?  
**Jawab:** Tidak. `RefreshDatabase` dipakai secara eksplisit oleh test yang membutuhkan migrasi/database bersih (contoh: semua Feature Auth dan banyak Feature test lainnya).

**Tanya:** Apakah emulasi ini mempengaruhi hasil tes?  
**Jawab:** Hanya menghitung selisih hari kalender, sama seperti MySQL `DATEDIFF`. Hal ini konsisten dengan logika yang diuji.

## File Terkait

- [00-ringkasan-arsitektur.md](../00-ringkasan-arsitektur.md)
- [01-alur-data-dan-sinkronisasi.md](../01-alur-data-dan-sinkronisasi.md)
- [04-istilah-dan-rumus.md](../04-istilah-dan-rumus.md)
