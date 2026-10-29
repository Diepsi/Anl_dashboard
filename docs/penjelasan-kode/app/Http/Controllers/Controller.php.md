# app/Http/Controllers/Controller.php

> **Path:** `app/Http/Controllers/Controller.php`  
> **Jenis:** Controller (base)  
> **Dipakai oleh:** Semua controller yang extends `Controller` (`DashboardController`, `ShipmentsController`, `ShipmentApiController`, `ClusteringController`, `UserController`, `ProfileController`, `Auth/*`)  
> **Memanggil:** Tidak ada (kelas abstrak kosong)

## Ringkasan

Kelas dasar abstrak untuk controller Laravel. Tidak memiliki trait, middleware, atau method tambahan. Hanya berfungsi sebagai penanda hierarki dan titik perluasan jika suatu saat ingin menambahkan helper bersama.

## Peran dalam Sistem

Berada di lapisan HTTP (Controllers). Merupakan base class untuk seluruh controller aplikasi, mengikuti konvensi Laravel standar tanpa modifikasi perilaku.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| `abstract class Controller` | `app/Http/Controllers/Controller.php:5` | Kelas abstrak kosong sebagai base untuk semua controller |

## Alur Data

```text
Tidak ada alur data (hanya definisi kelas)
```

Tidak memproses request/input/output sendiri.

## Relasi

- Route: -
- Model/tabel: -
- View: -
- Service: -
- Test: -

## Catatan Teknis

- Kosong secara sengaja; tidak menambahkan trait (mis. `AuthorizesRequests`, `ValidatesRequests`) karena controller lain tidak bergantung padanya. Seluruh validasi/otorisasi ditangani lewat Form Request (`LoginRequest`, `StoreUserRequest`, `UpdateUserRequest`, `ProfileUpdateRequest`) atau middleware (`EnsureUserIsAdmin`).
- Sangat minimal, tidak menimbulkan efek samping.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apakah kelas `Controller` ini menambahkan trait atau method apa pun?  
**Jawab:** Tidak. Kelas ini abstrak dan kosong; hanya sebagai base class konvensional Laravel.

**Tanya:** Mengapa tidak menambahkan trait bersama?  
**Jawab:** Karena kebutuhan lintas-controller sudah dipenuhi oleh Form Request, middleware, dan service, sehingga tidak diperlukan helper bersama di base controller.

## File Terkait

- [DashboardController](DashboardController.php.md)
- [UserController](UserController.php.md)
- [ShipmentApiController](ShipmentApiController.php.md)
- [ShipmentsController](ShipmentsController.php.md)
- [ClusteringController](ClusteringController.php.md)
- [ProfileController](ProfileController.php.md)
