# config/mail.php

> **Path:** `config/mail.php`  
> **Jenis:** Config  
> **Dipakai oleh:** Laravel Mail  
> **Memanggil:** `env()`

## Ringkasan

Konfigurasi mailer: default log, berbagai mailer, pengaturan from address.

## Peran dalam Sistem

Lapisan konfigurasi pengiriman email.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| default | `config/mail.php:17` | `env('MAIL_MAILER','log')`. |
| mailers.log | `:73-76` | Transport log (email ditulis ke log). |
| from | `:113-116` | Alamat dan nama pengirim dari env. |

## Alur Data

```text
Mail facade -> mailer sesuai konfigurasi
```

## Relasi

- Route: routes/auth.php (password reset)
- Model/tabel: -
- View: -
- Service: -
- Test: phpunit.xml menggunakan array mailer

## Catatan Teknis

- Default `log` — tautan reset password muncul di log, bukan dikirim email.
- Untuk produksi perlu konfigurasi SMTP/driver lain.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Bagaimana reset password bekerja tanpa email dikirim?  
**Jawab:** Dengan `MAIL_MAILER=log`, link reset tercatat di log aplikasi sehingga bisa diambil manual untuk testing.

## File Terkait

- [05-konfigurasi-environment.md](../05-konfigurasi-environment.md)
- [routes/auth.php.md](../routes/auth.php.md)
- [phpunit.xml.md](../phpunit.xml.md)
