# config/filesystems.php

> **Path:** `config/filesystems.php`  
> **Jenis:** Config  
> **Dipakai oleh:** Laravel Filesystem  
> **Memanggil:** `env()`

## Ringkasan

Konfigurasi filesystem: disk default local, disk lokal/public/s3, dan symlink public/storage.

## Peran dalam Sistem

Lapisan konfigurasi penyimpanan file.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| default | `config/filesystems.php:16` | `env('FILESYSTEM_DISK','local')`. |
| local | `:33-39` | Root `storage/app/private`. |
| public | `:41-48` | Root `storage/app/public`, URL berdasarkan APP_URL. |
| links | `:76-78` | `public_path('storage') -> storage_path('app/public')`. |

## Alur Data

```text
Storage facade menggunakan disk yang dikonfigurasi
```

## Relasi

- Route: -
- Model/tabel: -
- View: -
- Service: -
- Test: -

## Catatan Teknis

- Aplikasi tidak terlihat menggunakan disk public secara intensif; sebagian besar data di database.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Dimana file disimpan secara default?  
**Jawab:** Disk `local` di `storage/app/private`.

## File Terkait

- [05-konfigurasi-environment.md](../05-konfigurasi-environment.md)
