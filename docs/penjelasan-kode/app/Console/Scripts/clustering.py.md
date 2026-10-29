# app/Console/Scripts/clustering.py

> **Path:** `app/Console/Scripts/clustering.py`  
> **Jenis:** Script (Python)  
> **Dipakai oleh:** `ClusteringService` via Symfony Process  
> **Memanggil:** pandas, numpy, scikit-learn (KMeans, StandardScaler)

## Ringkasan

Script Python untuk K-Means clustering k=3 per kota/kabupaten berdasarkan observasi dari PHP. Melakukan agregasi, filter wilayah lengkap, standardisasi, clustering, penentuan label zona berdasarkan pusat cluster, perhitungan risk_index, dan menulis hasil JSON.

## Peran dalam Sistem

Berada di lapisan **Analitik/ML**. Implementasi algoritma; PHP hanya menyiapkan observasi dan mengonsumsi hasil.

## Bagian Penting

| Bagian | Lokasi | Fungsi |
|---|---|---|
| Konstanta label | `clustering.py:22-24` | `HIGH_RISK = "High Risk / Bottleneck Zone"`, `HIGH_VOLUME = "High Volume Zone"`, `STANDARD = "Standard / Low Risk Zone"`. |
| `main(input_csv, output_json)` | `clustering.py:27-136` | Alur lengkap: baca CSV, konversi numerik, agregasi per kota/kabupaten, filter usable, scaling+KMeans, labeling, risk_index, tulis JSON. |
| Entry point | `clustering.py:139-144` | Panggil main dari argv, tangkap exception -> print ke stderr + exit(1). |

## Alur Data (input -> proses -> output)

```text
Input: observations.csv (kota_kabupaten,provinsi,durasi,is_out)
  -> read + coerce numerik
  -> agregasi: avg_aging = mean durasi, out = sum is_out, verifiable = count notna, total_shipment = size
  -> out_sla_rate = out/verifiable*100 jika verifiable>0 else NaN
  -> usable = dropna(avg_aging, out_sla_rate)
  -> filter: if len(usable) < 9 -> RuntimeError
  -> StandardScaler pada [avg_aging,out_sla_rate,total_shipment]
  -> KMeans(n_clusters=3, random_state=42, n_init="auto")
  -> label zona dari pusat cluster (risk_score = z-aging+z-out-sla, volume_score = z-total)
  -> risk_index = (( (z-aging+z-out-sla)/2 ).clip(-2,2)+2)/4*100 round(1)
  -> output JSON dengan regions dan centers
Output: result.json
```

**Detail agregasi:** `provinsi = mode[0]` jika ada, else "". `durasi/is_out` bisa kosong (NA).

**Labeling:** 
1. `risk_cluster = argmax(centers[:,0]+centers[:,1])` (HIGH_RISK)
2. dari sisanya `volume_cluster = argmax(centers[:,2])` (HIGH_VOLUME)  
3. sisanya STANDARD

**Risk index:** distandardisasi, rata-rata (z_aging+z_out_sla)/2, clip -2..2, skala ke 0-100, dibulatkan 1 desimal.

## Relasi

- **Dipanggil oleh:** `ClusteringService` via Process.
- **Input dari:** observasi hasil `writeObservations()`.
- **Output ke:** `ClusteringService::readResult()` lalu `persist()` ke `RegionalCluster`.

## Catatan Teknis, Edge Case, Keputusan Desain

- **Minimal wilayah:** perlu >= 9 wilayah dengan data lengkap untuk k=3 (mencegah clustering tidak stabil).
- **StandardScaler:** menormalkan skala fitur berbeda.
- **Label berdasarkan pusat, bukan urutan:** mencegah penamaan salah jika cluster bergeser.
- **Clip ±2:** membatasi pengaruh outlier pada risk_index (0-100).
- **random_state=42, n_init="auto":** reproduktif.
- **Error ke stderr:** Laravel menangkap dan menampilkan sebagai exception.
- **Centers juga disimpan di JSON:** untuk referensi/diagnostik.

## Kemungkinan Pertanyaan Penguji + Jawaban

**Tanya:** Kapan clustering gagal dan kenapa?  
**Jawab:** Jika jumlah wilayah dengan `avg_aging` dan `out_sla_rate` valid < 9, script raise RuntimeError (perlu minimal 9 untuk K-Means k=3) (L51-54).

**Tanya:** Bagaimana penentuan label zona High Risk/High Volume/Standard?  
**Jawab:** Berdasarkan pusat cluster ter-standardisasi: zona dengan `z_aging+z_out_sla` tertinggi = High Risk; dari sisa, yang `z_total_shipment` tertinggi = High Volume; sisanya Standard (L67-83).

**Tanya:** Bagaimana rumus risk_index?  
**Jawab:** `raw = (z_avg_aging + z_out_sla_rate)/2`, clip ke [-2,2], `risk_index = ((raw+2)/4)*100` dibulatkan 1 desimal (L85-86).

## File Terkait

- [ClusteringService](../../Services/ClusteringService.php.md)
- [RunClusteringAnalysis Command](../Commands/RunClusteringAnalysis.php.md)
- [RegionalCluster Model](../../Models/RegionalCluster.php.md)
- [04-istilah-dan-rumus](../../../04-istilah-dan-rumus.md)
