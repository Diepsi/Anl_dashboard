# Kemungkinan Pertanyaan Sidang

## Arsitektur

**1. Mengapa memakai Laravel?**
Laravel menyediakan routing, autentikasi, validasi, ORM, migrasi, cache, scheduler, dan
testing dalam satu ekosistem. Kebutuhan proyek mencakup semua itu, sehingga tidak perlu
membangun fondasi dari nol.

**2. Apa pola arsitektur aplikasi ini?**
MVC dengan service layer. Controller mengatur request, model menyimpan aturan data,
service menangani proses berat seperti sync CSV dan clustering, view menangani presentasi.

**3. Mengapa logika SLA diletakkan di model?**
Karena aturan SLA dipakai di dashboard, data pengiriman, export, dan API. Scope di model
membuat definisi Meet/Out/Tidak terverifikasi konsisten di semua tempat.

**4. Apa fungsi service layer?**
Memisahkan proses bisnis yang kompleks dari controller. `ShipmentSyncService` menangani
unduh, validasi, normalisasi, dan import; `ClusteringService` mengatur pipeline Python.

## Data dan Sinkronisasi

**5. Dari mana data berasal?**
Dari CSV publik Google Sheets sheet ANL Publikasi vendor BOMA, diunduh melalui
`GOOGLE_SHEETS_CSV_URL`.

**6. Mengapa memakai CSV, bukan API Google Sheets?**
CSV publik cukup untuk kebutuhan baca berkala, tidak memerlukan OAuth, dan lebih sederhana
serta tahan terhadap perubahan kredensial.

**7. Bagaimana mencegah data terhapus saat sheet kosong?**
Sebelum import, service menghitung baris valid. Jika nol atau turun lebih dari 50% dari
jumlah data lama, sync ditolak. `--force` hanya untuk kasus yang disengaja.

**8. Bagaimana menangani resi kosong?**
Baris yang masih punya data shipment disimpan dengan `no_resi = NULL`. Baris template yang
hanya berisi nomor urut dan `FALSE` dibuang.

**9. Mengapa resi boleh duplikat?**
Keputusan pemilik data: semua baris sumber harus dipertahankan. Resi kembar ditandai
`is_duplicate_no_resi` agar tidak menyesatkan, bukan digabung atau dihapus.

**10. Bagaimana menangani nomor resi dalam notasi ilmiah?**
`normalizeResi()` mengubah `1,0094E+15` dan format ribuan menjadi digit bulat, lalu
memvalidasi panjang 13–18 digit.

**11. Bagaimana jika URL sheet diganti?**
Service menghitung fingerprint SHA-256 dari URL. Jika berbeda dari cache, mode replace
dipaksa agar data sheet lama tidak tercampur.

**12. Apa yang terjadi jika sync gagal di tengah import?**
Import berjalan dalam transaksi database. Jika exception, perubahan di-rollback dan file
sementara dihapus.

## SLA

**13. Mengapa verdict SLA tidak diambil langsung dari sheet?**
Kolom SLA di sumber punya dua bentuk: jumlah hari dan tanggal. Verdict sheet juga pernah
salah hitung. Aplikasi menormalkan keduanya ke `sla_due_date`, lalu menghitung ulang
verdict dari `completed_date`.

**14. Apa perbedaan Meet SLA, Out SLA, dan tidak terverifikasi?**
Meet jika selesai sebelum/sama dengan deadline. Out jika melewati deadline. Tidak
terverifikasi jika deadline atau tanggal selesai tidak ada.

**15. Mengapa baris tanpa deadline tidak dihitung Out SLA?**
Karena tidak ada dasar untuk menyimpulkan keterlambatan. Menghitungnya sebagai Out akan
membuat persentase SLA menyesatkan.

**16. Dari mana anchor deadline berbasis hari?**
Dari `tgl_ho_sartrans`, karena pengujian terhadap verdict sheet menunjukkan kecocokan
tertinggi dibanding anchor lain.

**17. Bagaimana menangani tanggal mustahil?**
`checkdate()` menolak tanggal seperti 31 Februari. Scope `withConsistentDates()` juga
membuang baris dengan manifest setelah completed dari agregasi.

## Dashboard dan UI

**18. Mengapa modal staging sempat kosong?**
Key `x-for` memakai `no_resi`, yang bisa NULL atau duplikat. API sekarang mengirim `id`
dan frontend memakai `row.id`.

**19. Mengapa filter 30/90 hari memakai completed_date terakhir, bukan hari ini?**
Agar dashboard tetap menampilkan data relevan ketika sheet belum diperbarui. Jika memakai
hari ini, data lama bisa tampak kosong.

**20. Mengapa lead time memakai median dan P90?**
Mean mudah rusak oleh satu batch tanggal korup. Median dan P90 lebih tahan outlier.

**21. Mengapa dashboard hanya menampilkan stage dengan total > 0?**
Agar panel tidak dipenuhi tahap kosong. Urutan 9 tahap tetap dipertahankan.

**22. Apa fungsi tombol Sync Sekarang?**
Memanggil service yang sama dengan scheduler, tetapi hanya untuk admin dan langsung
memberi umpan balik di halaman.

## Clustering

**23. Mengapa memakai K-Means?**
K-Means cocok untuk mengelompokkan wilayah berdasarkan tiga fitur numerik dan menghasilkan
zona yang mudah dijelaskan: risiko, volume, dan standar.

**24. Mengapa K=3?**
Tujuannya tiga kategori operasional yang dapat ditindaklanjuti, bukan mencari jumlah
cluster optimal secara statistik.

**25. Mengapa perlu StandardScaler?**
Aging, persentase Out SLA, dan volume memiliki skala berbeda. Tanpa standardisasi, volume
akan mendominasi jarak K-Means.

**26. Bagaimana label zona ditentukan?**
Dari pusat cluster: skor risiko tertinggi menjadi High Risk, volume tertinggi dari sisanya
menjadi High Volume, sisanya Standard.

**27. Apa arti risk index?**
Skor 0–100 dari rata-rata komponen aging dan Out SLA yang sudah distandardisasi, dipotong
pada ±2 standar deviasi.

**28. Mengapa clustering dijalankan setelah sync?**
Agar hasil analitik selalu mengikuti data pengiriman terbaru. Jika clustering gagal, data
shipment tetap tersimpan.

## Keamanan dan Operasional

**29. Bagaimana otorisasi admin bekerja?**
User memiliki kolom `role`. Middleware `EnsureUserIsAdmin` menolak non-admin dengan 403.
Route manajemen user dan sync manual memakai middleware ini.

**30. Bagaimana mencegah admin terakhir dihapus?**
`UserController` menghitung jumlah admin sebelum update/delete. Operasi ditolak jika
tersisa satu admin, dan user tidak bisa menghapus akun sendiri.

**31. Bagaimana akun nonaktif ditangani?**
Setelah kredensial valid, `AuthenticatedSessionController` memeriksa `is_active`. Jika
false, sesi langsung logout dan login ditolak.

**32. Mengapa login dibatasi?**
`LoginRequest` memakai RateLimiter 5 percobaan per kombinasi email dan IP untuk mengurangi
brute force.

**33. Apa risiko menyimpan backup SQL di storage?**
Backup berisi data operasional dan tidak boleh di-commit atau diakses publik. Dokumentasi
ini tidak menyertakan isi backup.

## Pengujian

**34. Apa yang diuji?**
Konsistensi SLA, filter range, integritas metrik dashboard, guard sync, mapping CSV,
duplikat resi, export, clustering, autentikasi, dan profil.

**35. Mengapa ada test yang menguji nilai NULL?**
Karena banyak bug berasal dari nilai kosong yang dipaksa menjadi angka atau string. Test
memastikan NULL tetap NULL dan tidak ikut dihitung sebagai Meet/Out.

**36. Apa yang belum tercakup test?**
Tampilan visual dan interaksi browser nyata belum diuji end-to-end; pengujian fokus pada
HTTP response, query, dan isi HTML yang dihasilkan.
