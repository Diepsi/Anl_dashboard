<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use SplFileObject;

class ShipmentSyncService
{
    private array $columnMap = [
        'no_resi' => ['No Resi'],
        'nomor_redock' => ['Redock', 'Nomor Redock'],
        'delivery_order' => ['Delivery Order', 'Manifest First Mile'],
        'nama_sekolah' => ['NAMA SEKOLAH'],
        'nama_penerima' => ['Nama Penerima', 'Nama Penerima (Alokasi)'],
        'provinsi' => ['Provinsi'],
        'daerah' => ['Daerah'],
        'kota_kabupaten' => ['Kabupaten/Kota', 'Kota/Kab Tujuan'],
        'kecamatan' => ['Kecamatan'],
        'vendor_mm' => ['Vendor MM', 'Pengirim'],
        'kode_funder' => ['Kode Funder', 'Code Funder'],
        'nama_funder' => ['Nama Funder', 'Founder'],
        'vendor_lm' => ['Vendor LM'],
        'tanggal_manifest' => ['Tanggal HO ke Vendor', 'Tanggal Manifest', 'Tanggal Handover ke Vendor'],
        'tgl_ho_sartrans' => ['Tgl HO dari SarTrans', 'Tanggal HO dari SarTrans'],
        'koli' => ['kOLI', 'Koli', 'KOLI', 'Jumlah Koli'],
        'completed_date' => ['Completed date'],
        'tgl_sampai_kota_tujuan' => ['Actual ETA KAPAL', 'Tgl sampai di kota tujuan'],
        'sla_threshold_days' => ['Aging', 'SLA Threshold'],
        'status_akhir' => ['Status Akhir'],
        'status_instalasi' => ['Status Instalasi'],
        'harga_per_shipment' => ['Harga Per Shipment'],
        'status_invoice' => ['Status Invoice'],
        'stagging' => ['Update Stagging', 'Status update'],
        'sla' => ['SLA', 'SLA for DB'],
        'sla_result' => ['Result Delivery for Panthera', 'Result Delivery', 'Result Delivery for DB'],
        'bast_tgl_balik' => ['TGL BAST BALIK DARI VENDOR'],
        'bast_tgl_ke_finance' => ['TGL HO BAST KE FINANCE', 'TGL HO BAST KE FINENCE'],
        'bast_keterangan' => ['KETERANGAN BAST', 'KETERANGAN BAST BALIK'],
    ];

    public const LAST_SYNC_CACHE_KEY = 'shipments.last_synced_at';

    public const SOURCE_STATS_CACHE_KEY = 'shipments.source_stats';

    public const SOURCE_FINGERPRINT_CACHE_KEY = 'shipments.source_fingerprint';

    /**
     * Ambang Rasio baris valid terhadap baris yang sudah ada. Sheet yang tiba-tiba
     * menyusut drastis hampir selalu berarti sheet terfilter, terpotong, atau salah
     * pilih tab, bukan memang ada data yang dihapus orang.
     */
    public const MIN_ROW_RATIO = 0.5;

    /**
     * Penanda error formula Excel. Sel yang berisi ini bukan data: formula yang gagal
     * hitung bisa berubah begitu sheet dirumuskan ulang, jadi menyimpan teks "#N/A"
     * sebagai nilai hanya memindahkan kesalahan ke dashboard.
     */
    public const FORMULA_ERRORS = ['#N/A', '#VALUE!', '#REF!', '#DIV/0!', '#NAME?', '#NULL!', '#NUM!', 'ERR:'];

    public function lastSyncedAt(): ?Carbon
    {
        $value = Cache::get(self::LAST_SYNC_CACHE_KEY);

        return $value ? Carbon::parse($value) : null;
    }

    public function sourceStats(): ?array
    {
        $stats = Cache::get(self::SOURCE_STATS_CACHE_KEY);

        return is_array($stats) ? $stats : null;
    }

    public function sync(bool $replace = false, bool $force = false): int
    {
        $url = config('services.google_sheets.csv_url');

        if (empty($url)) {
            throw new \RuntimeException('GOOGLE_SHEETS_CSV_URL belum dikonfigurasi di .env');
        }

        $fingerprint = hash('sha256', $url);
        $lastFingerprint = Cache::get(self::SOURCE_FINGERPRINT_CACHE_KEY);

        if ($lastFingerprint !== $fingerprint) {
            $replace = true;
        }

        $response = Http::timeout(120)->get($url);

        if (! $response->successful() || $response->body() === '') {
            throw new \RuntimeException('Gagal mengunduh CSV (HTTP '.$response->status().').');
        }

        $temp = tempnam(sys_get_temp_dir(), 'anl_');
        file_put_contents($temp, $response->body());

        try {
            $result = DB::transaction(fn () => $this->importFromFile($temp, $replace, $force));
            $count = $result['imported'];
        } finally {
            @unlink($temp);
        }

        $stats = [
            'raw_rows' => $result['stats']['raw_rows'],
            'skipped_empty' => $result['stats']['skipped_empty'],
            'skipped_malformed' => $result['stats']['skipped_malformed'],
            'valid_rows' => $result['stats']['valid_rows'],
            'imported_rows' => $result['stats']['imported_rows'],
            'dup_extra' => $result['stats']['dup_extra'],
            'unique_rows' => $result['stats']['unique_rows'],
            'duplicates' => $result['stats']['duplicates'],
            'malformed_resi' => $result['stats']['malformed_resi'],
        ];

        Cache::put(self::SOURCE_FINGERPRINT_CACHE_KEY, $fingerprint);
        Cache::put(self::SOURCE_STATS_CACHE_KEY, $stats);
        Cache::put(self::LAST_SYNC_CACHE_KEY, now()->toISOString());

        return $count;
    }

    protected function importFromFile(string $file, bool $replace, bool $force = false): array
    {
        $csv = new SplFileObject($file, 'r');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $index = null;

        while (! $csv->eof()) {
            $row = $csv->fgetcsv();

            if ($row === false) {
                continue;
            }

            $row = array_map(fn ($cell) => trim((string) $cell), $row);

            $index = $this->buildHeaderIndex($row);

            if (! isset($index['no resi'])) {
                throw new \RuntimeException('Kolom "No Resi" tidak ditemukan di header CSV.');
            }

            break;
        }

        if ($index === null) {
            throw new \RuntimeException('CSV sumber kosong: tidak ada baris header.');
        }

        // Pre-flight: hitung dulu berapa baris yang benar-benar akan masuk, tanpa
        // menyentuh database. Sheet yang terhapus, terfilter, atau salah pilih tab
        // hanya mengembalikan header, dan tanpa langkah ini tabel akan dikosongkan
        // lalu sync dilaporkan sukses dengan last_synced_at yang ikut diperbarui.
        $incoming = $this->countImportableRows($file, $index);
        $existing = Shipment::count();

        $this->assertImportIsSafe($incoming, $existing, $force);

        $parsed = $this->buildRows($file, $index);

        $rows = $parsed['rows'];
        $duplicates = $parsed['duplicates'];
        $duplicateResi = array_keys($duplicates);

        if ($replace) {
            Shipment::query()->delete();
        } else {
            // Baris lama yang nomornya ikut tayang di CSV ini disegarkan, tapi
            // tidak digabung: nomor kembar tetap disimpan semua.
            foreach (array_chunk($this->uniqueResi($rows), 500) as $slice) {
                Shipment::whereIn('no_resi', $slice)->delete();
            }

            // Baris tanpa resi tidak bisa dicocokkan lewat whereIn (NULL), dan
            // baris ber-NULL selalu berasal dari baris malformed/kosong CSV yang
            // disimpan ulang di tiap putaran. Tanpa penghapusan ini setiap sync
            // non-replace menumpuk salinan baru baris-baris itu.
            Shipment::whereNull('no_resi')->delete();
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->flush($chunk);
        }

        $this->markDuplicateNoResi($duplicateResi);

        $count = count($rows);
        $dupExtra = array_sum($duplicates) - count($duplicates);

        if (count($duplicates) > 50) {
            $duplicates = array_slice($duplicates, 0, 50, true);
        }

        $skippedMalformed = $parsed['skipped_malformed'];

        if (count($skippedMalformed) > 50) {
            $skippedMalformed = array_slice($skippedMalformed, 0, 50, true);
        }

        return [
            'imported' => $count,
            'stats' => [
                'raw_rows' => $parsed['raw_rows'],
                'skipped_empty' => $parsed['skipped_empty'],
                'skipped_malformed' => $parsed['skipped_malformed_count'],
                'valid_rows' => $parsed['valid_rows'],
                'imported_rows' => $count,
                'dup_extra' => $dupExtra,
                'unique_rows' => $parsed['unique_rows'],
                'duplicates' => $duplicates,
                'malformed_resi' => $skippedMalformed,
            ],
        ];
    }

    /**
     * Semua baris berisi data diubah jadi baris database. Tidak ada lagi baris yang
     * dibuang karena resi kosong/rubah: nomor yang tak bisa dipakai disimpan sebagai
     * NULL dan ditandai lewat "—" di tampilan, sesuai keputusan pemilik data bahwa
     * "data di masukkan semua".
     */
    protected function buildRows(string $file, array $index): array
    {
        $csv = new SplFileObject($file, 'r');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $csv->fgetcsv();

        $rows = [];
        $seen = [];
        $duplicates = [];
        $skippedEmpty = 0;
        $skippedMalformedCount = 0;
        $skippedMalformed = [];
        $withResi = 0;
        $rawRows = 0;

        while (! $csv->eof()) {
            $row = $csv->fgetcsv();

            if ($row === false) {
                continue;
            }

            $row = array_map(fn ($cell) => trim((string) $cell), $row);

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rawRows++;

            $data = $this->mapRow($row, $index);

            $rawResi = $data['no_resi'];

            if ($rawResi === null) {
                $skippedEmpty++;
            } else {
                $resi = $this->normalizeResi($rawResi);

                if ($resi === null) {
                    $skippedMalformedCount++;
                    $skippedMalformed[$rawResi] = ($skippedMalformed[$rawResi] ?? 0) + 1;
                    $data['no_resi'] = null;
                } else {
                    $data['no_resi'] = $resi;
                    $withResi++;
                    $seen[$resi] = ($seen[$resi] ?? 0) + 1;

                    if ($seen[$resi] >= 2) {
                        $duplicates[$resi] = $seen[$resi];
                    }
                }
            }

            $rows[] = $data;
        }

        return [
            'rows' => $rows,
            'raw_rows' => $rawRows,
            'skipped_empty' => $skippedEmpty,
            'skipped_malformed_count' => $skippedMalformedCount,
            'skipped_malformed' => $skippedMalformed,
            'valid_rows' => $withResi,
            'duplicates' => $duplicates,
            'unique_rows' => count($seen),
        ];
    }

    /**
     * Jumlah baris yang akan benar-benar di-import, dihitung dari file tanpa
     * menyentuh database. Setiap baris berisi data masuk — resi bukan lagi syarat —
     * supaya angka pre-flight persis dengan hasilnya.
     */
    protected function countImportableRows(string $file, array $index): int
    {
        $csv = new SplFileObject($file, 'r');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $csv->fgetcsv();

        $count = 0;

        while (! $csv->eof()) {
            $row = $csv->fgetcsv();

            if ($row === false) {
                continue;
            }

            $row = array_map(fn ($cell) => trim((string) $cell), $row);

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function uniqueResi(array $rows): array
    {
        $resi = [];

        foreach ($rows as $data) {
            if ($data['no_resi'] !== null) {
                $resi[$data['no_resi']] = true;
            }
        }

        return array_keys($resi);
    }

    /**
     * Nomor resi yang muncul lebih dari satu kali di tabel ditandai, supaya pemilik
     * data tahu bahwa baris itu berbagi nomor dengan baris lain. Mustahil membedakan
     * mana yang "asli" — sheet sendiri menampilkan batch 1,01E+15 sebagai nilai yang
     * sama — jadi cukup ditandai, bukan dihapus atau diberi nomor karangan.
     */
    protected function markDuplicateNoResi(array $duplicateResi): void
    {
        Shipment::query()->update(['is_duplicate_no_resi' => false]);

        foreach (array_chunk($duplicateResi, 500) as $slice) {
            Shipment::whereIn('no_resi', $slice)->update(['is_duplicate_no_resi' => true]);
        }
    }

    /**
     * Tolak sync yang jelas-jelas salah sebelum tabel dikosongkan. Tanpa ini,
     * `replace` menghapus seluruh data lalu melaporkan sukses, dan
     * `last_synced_at` diperbarui ke waktu sekarang sehingga operator mengira
     * data baru saja masuk.
     *
     * @throws \RuntimeException
     */
    protected function assertImportIsSafe(int $incoming, int $existing, bool $force): void
    {
        if ($force) {
            return;
        }

        if ($incoming === 0) {
            throw new \RuntimeException(sprintf(
                'CSV sumber berisi 0 baris data valid. Tabel shipments (%s baris) tidak diubah. '
                .'Periksa apakah sheet masih berisi data, atau pakai --force bila memang sengaja dikosongkan.',
                number_format($existing, 0, ',', '.')
            ));
        }

        $floor = (int) floor($existing * self::MIN_ROW_RATIO);

        if ($existing > 0 && $incoming < $floor) {
            throw new \RuntimeException(sprintf(
                'Baris valid turun drastis: %s di CSV vs %s di tabel (minimal %s). '
                .'Kemungkinan sheet terfilter atau terpotong. Tabel tidak diubah; pakai --force bila disengaja.',
                number_format($incoming, 0, ',', '.'),
                number_format($existing, 0, ',', '.'),
                number_format($floor, 0, ',', '.')
            ));
        }
    }

    protected function buildHeaderIndex(array $row): array
    {
        $map = [];

        foreach ($row as $col => $header) {
            if ($header !== '') {
                $map[strtolower($header)] = $col;
            }
        }

        return $map;
    }

    protected function mapRow(array $row, array $index): array
    {
        $statusInvoice = $this->pick($row, $index, $this->columnMap['status_invoice']);

        if (is_string($statusInvoice)) {
            $statusInvoice = strtoupper(trim($statusInvoice));

            if ($statusInvoice === 'TRUE' || $statusInvoice === 'PAID') {
                $statusInvoice = 'PAID';
            } else {
                $statusInvoice = null;
            }
        } else {
            $statusInvoice = null;
        }

        $namaFunder = $this->cleanText($this->pick($row, $index, $this->columnMap['nama_funder']));

        // Kolom SLA di sumber tidak pernah satu arti. BOMA mengisinya dengan ambang
        // hari ("17"), sedangkan 31 vendor lain mengisinya dengan tanggal batas
        // ("25/08/2025"). Keduanya sah dan keduanya dipakai sheet untuk menghitung
        // verdict, jadi keduanya diselesaikan menjadi satu deadline yang bisa
        // dibandingkan langsung dengan completed_date.
        //
        // Batas hari dihitung dari "Tgl HO dari SarTrans" karena diuji terhadap
        // verdict sheet pada 99,1% baris; jangkar lain meleset jauh lebih jauh
        // (completed_date 92,3%, Actual ETA KAPAL 80,6%).
        $slaRaw = $this->pick($row, $index, $this->columnMap['sla']);
        $slaDays = $this->toSlaDays($slaRaw);
        $slaDueDate = $this->toDate($slaRaw);

        if ($slaDueDate === null && $slaDays !== null) {
            $anchor = $this->toDate($this->pick($row, $index, $this->columnMap['tgl_ho_sartrans']));
            $completed = $this->toDate($this->pick($row, $index, $this->columnMap['completed_date']));

            // Jangkar yang lebih baru dari tanggal selesai membuat deadline selalu
            // jatuh setelah selesai, sehingga baris itu akan terbaca "Meet SLA"
            // tanpa pernah bisa meleset. Baris seperti ini tidak bisa dinilai, jadi
            // dibiarkan tanpa deadline alih-alih diberi nilai yang selalu lulus.
            if ($anchor !== null && ($completed === null || $anchor <= $completed)) {
                $slaDueDate = Carbon::parse($anchor)->addDays($slaDays)->toDateString();
            }
        }

        return [
            'no_resi' => $this->nullableString($this->pick($row, $index, $this->columnMap['no_resi'])),
            'nomor_redock' => $this->nullableString($this->pick($row, $index, $this->columnMap['nomor_redock'])),
            'delivery_order' => $this->nullableString($this->pick($row, $index, $this->columnMap['delivery_order'])),
            'nama_sekolah' => (string) ($this->pick($row, $index, $this->columnMap['nama_sekolah']) ?? ''),
            'nama_penerima' => $this->nullableString($this->pick($row, $index, $this->columnMap['nama_penerima'])),
            'provinsi' => $this->nullableString($this->pick($row, $index, $this->columnMap['provinsi'])),
            'daerah' => $this->nullableString($this->pick($row, $index, $this->columnMap['daerah'])),
            'kota_kabupaten' => $this->nullableString($this->pick($row, $index, $this->columnMap['kota_kabupaten'])),
            'kecamatan' => $this->nullableString($this->pick($row, $index, $this->columnMap['kecamatan'])),
            'vendor_mm' => $this->nullableString($this->pick($row, $index, $this->columnMap['vendor_mm'])),
            'kode_funder' => $this->nullableString($this->pick($row, $index, $this->columnMap['kode_funder'])),
            'nama_funder' => $namaFunder,
            'vendor_lm' => $this->cleanText($this->pick($row, $index, $this->columnMap['vendor_lm'])),
            'tanggal_manifest' => $this->toDate($this->pick($row, $index, $this->columnMap['tanggal_manifest'])),
            'tgl_ho_sartrans' => $this->toDate($this->pick($row, $index, $this->columnMap['tgl_ho_sartrans'])),
            'koli' => $this->toIntOrNull($this->pick($row, $index, $this->columnMap['koli'])),
            'completed_date' => $this->toDate($this->pick($row, $index, $this->columnMap['completed_date'])),
            'tgl_sampai_kota_tujuan' => $this->toDate($this->pick($row, $index, $this->columnMap['tgl_sampai_kota_tujuan'])),
            'sla_threshold_days' => $this->toThresholdDays($this->pick($row, $index, $this->columnMap['sla_threshold_days'])),
            'status_akhir' => $this->cleanText($this->pick($row, $index, $this->columnMap['status_akhir'])),
            'status_instalasi' => $this->cleanText($this->pick($row, $index, $this->columnMap['status_instalasi'])),
            'harga_per_shipment' => $this->toDecimalOrNull($this->pick($row, $index, $this->columnMap['harga_per_shipment'])),
            'status_invoice' => $statusInvoice,
            'stagging' => $this->cleanText($this->pick($row, $index, $this->columnMap['stagging'])),
            'sla' => $slaDays,
            'sla_due_date' => $slaDueDate,
            'sla_result' => $this->cleanText($this->pick($row, $index, $this->columnMap['sla_result'])),
            'bast_tgl_balik' => $this->toDate($this->pick($row, $index, $this->columnMap['bast_tgl_balik'])),
            'bast_tgl_ke_finance' => $this->toDate($this->pick($row, $index, $this->columnMap['bast_tgl_ke_finance'])),
            'bast_keterangan' => $this->cleanText($this->pick($row, $index, $this->columnMap['bast_keterangan'])),
        ];
    }

    protected function pick(array $row, array $index, array $headers)
    {
        $fallback = null;

        foreach ($headers as $header) {
            $col = $index[strtolower($header)] ?? null;

            if ($col === null || ! array_key_exists($col, $row)) {
                continue;
            }

            $value = $row[$col];

            if ($fallback === null) {
                $fallback = $value;
            }

            if (trim((string) $value) !== '') {
                return $value;
            }
        }

        return $fallback;
    }

    protected function flush(array $chunks): void
    {
        $now = now();

        foreach ($chunks as $i => $data) {
            $chunks[$i]['created_at'] ??= $now;
            $chunks[$i]['updated_at'] ??= $now;
        }

        Shipment::insert($chunks);
    }

    protected function nullableString($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * Teks dari sumber, dengan sel kosong dan error formula dibuang. Kolom
     * `Status Instalasi` Misalnya berisi "#N/A" pada 20.866 baris karena rumus di
     * sheet gagal; menyimpan teks itu apa adanya memindahkan kesalahan formula ke
     * file export dan ke dashboard.
     */
    protected function cleanText($value): ?string
    {
        $text = $this->nullableString($value);

        if ($text === null) {
            return null;
        }

        return $this->isFormulaError($text) ? null : $text;
    }

    protected function isFormulaError(string $text): bool
    {
        $upper = strtoupper($text);

        foreach (self::FORMULA_ERRORS as $marker) {
            if (str_contains($upper, $marker)) {
                return true;
            }
        }

        // Google Sheets sesekali mengeluarkan angka dalam notasi ilmiah
        // ("1,0094E+15"). Nilai seperti ini lolos semua cast numerik dan terlihat
        // sah, padahal sudah kehilangan presisi.
        return (bool) preg_match('/^\d+(?:,\d+)?E\+\d+$/i', $text);
    }

    /**
     * Tanggal hanya diterima bila benar-benar ada di kalender. Tanpa pemeriksaan ini
     * "31/02/2026" tersimpan sebagai tanggal yang tidak ada, yang di MySQL strict
     * mode menggagalkan seluruh sync dan di mode biasa menjadi tanggal kosong
     * tanpa pesan error sama sekali.
     */
    protected function toDate($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})/', $value, $m)) {
            $year = strlen($m[3]) === 2 ? '20'.$m[3] : $m[3];

            return checkdate((int) $m[2], (int) $m[1], (int) $year)
                ? sprintf('%04d-%02d-%02d', (int) $year, (int) $m[2], (int) $m[1])
                : null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1])
                ? sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3])
                : null;
        }

        return null;
    }

    /**
     * No Resi hanya dianggap sah bila 13-18 digit angka. Nilai lain (#N/A,
     * formula, teks kosong) ditolak agar tidak ikut terhitung sebagai pengiriman.
     *
     * Google Sheets menampilkan no resi sebagai angka, jadi yang 15-16 digit
     * keluar dalam notasi ilmiah ("1,01E+15", "1.009.400.821.166.140,00").
     * Bentuk itu memakai "E+" atau pemisah ribuan, dan dipulihkan menjadi digit
     * bulatnya di sini — angka itu memang resinya, cuma ditulis pendek oleh Sheets.
     */
    protected function normalizeResi(string $raw): ?string
    {
        $value = trim($raw);

        if ($value === '' || str_starts_with($value, '#')) {
            return null;
        }

        if (preg_match('/^([0-9][0-9.,]*)[eE]\s*\+(\d{1,4})$/', $value, $m)) {
            $mantissa = str_replace(',', '.', $m[1]);
            $exp = (int) $m[2];

            if (strpbrk($mantissa, '.') !== false) {
                [$whole, $fraction] = explode('.', $mantissa, 2);
                $decimals = strlen($fraction);
                $digits = $whole.$fraction;

                if ($exp - $decimals >= 0) {
                    $digits .= str_repeat('0', $exp - $decimals);
                }
            } else {
                $digits = $mantissa.str_repeat('0', $exp);
            }

            // Mantissa bisa dimulai nol ("0.5E+15"); angka bulatnya baru muncul
            // setelah leading zero itu dihilangkan.
            $digits = ltrim($digits, '0') ?: '0';
        } elseif (preg_match('/^(\d{1,3}(?:[.,]\d{3})+)(?:[.,]\d{1,2})?$/', $value, $m)) {
            // Angka bentukan Excel dengan pemisah ribuan dan opsi desimal di ekor.
            // "1.009.400.821.166.140,00" berarti desimal ",00" yang harus dibuang,
            // bukan ikut digabung jadi "…14000".
            $digits = str_replace([',', '.', ' ', "\xc2\xa0"], '', $m[1]);
        } else {
            // Pemisah ribuan maupun spasi tak bisa membawa angka bulat, jadi buang
            // pemisah lalu periksa digit yang tersisa. No resi dengan nol di depan
            // dipertahankan apa adanya.
            $digits = str_replace([',', '.', ' ', "\xc2\xa0"], '', $value);
        }

        if ($digits === '' || ! ctype_digit($digits)) {
            return null;
        }

        if (strlen($digits) < 13 || strlen($digits) > 18) {
            return null;
        }

        return $digits;
    }

    protected function toIntOrNull($value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (! is_numeric(trim((string) $value))) {
            return null;
        }

        return (int) trim((string) $value);
    }

    /**
     * Ambang SLA hanya bermakna sebagai jumlah hari. Nilai di luar rentang itu
     * ditolak supaya serial date dan teks tidak pernah terbaca sebagai ambang.
     */
    protected function toSlaDays($value): ?int
    {
        $days = $this->toIntOrNull($value);

        if ($days === null || $days < 1 || $days > 365) {
            return null;
        }

        return $days;
    }

    /**
     * Ambang hari dengan rentang lebih longgar untuk kolom `Aging`. Isinya
     * 300-400 hari, jadi batas 365 ala toSlaDays akan membuangnya semua.
     */
    protected function toThresholdDays($value): ?int
    {
        $days = $this->toIntOrNull($value);

        if ($days === null || $days < 1 || $days > 3650) {
            return null;
        }

        return $days;
    }

    /**
     * Harga yang kosong di sumber disimpan NULL, bukan 0. Kolom ini terisi hanya
     * pada 2.392 dari 31.059 baris, jadi memaksa sisanya jadi nol membuat
     * rata-rata terhitung terhadap 31.059 pembagi dan meleset belasan kali.
     */
    protected function toDecimalOrNull($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '' || $this->isFormulaError($text) || ! is_numeric($text)) {
            return null;
        }

        return (float) $text;
    }
}
