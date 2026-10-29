# bootstrap/app.php

> **Path:** `bootstrap/app.php`  
> **Jenis:** Bootstrap  
> **Dipakai oleh:** Aplikasi Laravel (bootstrap)  
> **Memanggil:** `Illuminate\Foundation\Application`, `Illuminate\Foundation\Configuration\Exceptions`, `Illuminate\Foundation\Configuration\Middleware`, `Illuminate\Http\Request`

## Ringkasan

Konfigurasi bootstrap aplikasi Laravel: routing (web, commands, health), alias middleware, dan penanganan exception untuk request JSON.

## Peran dalam Sistem

Lapisan bootstrap. Mengatur konfigurasi awal framework.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Application configure | `bootstrap/app.php:8-22` | Set base path, routing web/commands/health `/up`, middleware alias, exception handling. |
| Middleware alias | `:15-18` | Alias `admin` -> `App\Http\Middleware\EnsureUserIsAdmin::class`. |
| Exceptions JSON | `:19-22` | Render JSON untuk request `api/*` atau `expectsJson()`. |

## Alur Data

```text
public/index.php -> bootstrap/app.php -> buat Application instance dengan konfigurasi
```

## Relasi

- Route: routes/web.php, routes/console.php
- Model/tabel: -
- View: -
- Service: -
- Test: -

## Catatan Teknis

- Health route `/up` tersedia.
- Exception akan di-render sebagai JSON untuk endpoint API/ajax.
- Middleware alias `admin` digunakan di routes/web.php.

## Kemungkinan Pertanyaan Penguji

**Tanya:** Apa fungsi alias `admin`?  
**Jawab:** Mempermudah penggunaan middleware `App\Http\Middleware\EnsureUserIsAdmin` di route definition.

**Tanya:** Kapan exception di-render JSON?  
**Jawab:** Jika request diawali `api/` atau mengharapkan JSON (`expectsJson()`).

## File Terkait

- [public/index.php.md](../public/index.php.md)
- [routes/web.php.md](../routes/web.php.md)
- [03-peta-route-dan-halaman.md](../03-peta-route-dan-halaman.md)
