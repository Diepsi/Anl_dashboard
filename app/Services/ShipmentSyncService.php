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
        'aging' => ['Aging'],
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

    public function sync(bool $replace = false): int
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
            $result = DB::transaction(fn () => $this->importFromFile($temp, $replace));
            $count = $result['imported'];
        } finally {
            @unlink($temp);
        }

        $stats = [
            'raw_rows' => $result['stats']['raw_rows'],
            'skipped_empty' => $result['stats']['skipped_empty'],
            'skipped_malformed' => $result['stats']['skipped_malformed'],
            'valid_rows' => $result['stats']['valid_rows'],
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

    protected function importFromFile(string $file, bool $replace): array
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

        if ($replace) {
            Shipment::query()->delete();
        }

        $chunks = [];
        $count = 0;
        $rawRows = 0;
        $skippedEmpty = 0;
        $skippedMalformedCount = 0;
        $skippedMalformed = [];
        $seen = [];
        $duplicates = [];

        while (! $csv->eof()) {
            $row = $csv->fgetcsv();

            if ($row === false) {
                continue;
            }

            $row = array_map(fn ($cell) => trim((string) $cell), $row);

            $rawRows++;

            $data = $this->mapRow($row, $index);

            $rawResi = $this->nullableString($data['no_resi']);

            if ($rawResi === null) {
                $skippedEmpty++;

                continue;
            }

            $resi = $this->normalizeResi($rawResi);

            if ($resi === null) {
                $skippedMalformedCount++;
                $skippedMalformed[$rawResi] = ($skippedMalformed[$rawResi] ?? 0) + 1;

                continue;
            }

            $data['no_resi'] = $resi;

            if (isset($seen[$resi])) {
                $seen[$resi]++;

                if ($seen[$resi] === 2) {
                    $duplicates[$resi] = 2;
                } else {
                    $duplicates[$resi] = $seen[$resi];
                }
            } else {
                $seen[$resi] = 1;
            }

            $chunks[] = $data;
            $count++;

            if (count($chunks) >= 500) {
                $this->flush($chunks);
                $chunks = [];
            }
        }

        if (! empty($chunks)) {
            $this->flush($chunks);
        }

        $dupExtra = array_sum($duplicates) - count($duplicates);

        if (count($duplicates) > 50) {
            $duplicates = array_slice($duplicates, 0, 50, true);
        }

        $malformedTotal = array_sum($skippedMalformed);

        if (count($skippedMalformed) > 50) {
            $skippedMalformed = array_slice($skippedMalformed, 0, 50, true);
        }

        return [
            'imported' => $count,
            'stats' => [
                'raw_rows' => $rawRows,
                'skipped_empty' => $skippedEmpty,
                'skipped_malformed' => $skippedMalformedCount,
                'valid_rows' => $count,
                'dup_extra' => $dupExtra,
                'unique_rows' => $count - $dupExtra,
                'duplicates' => $duplicates,
                'malformed_resi' => $skippedMalformed,
            ],
        ];
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

        $namaFunder = $this->nullableString($this->pick($row, $index, $this->columnMap['nama_funder']));
        $slaRaw = $this->pick($row, $index, $this->columnMap['sla']);

        return [
            'no_resi' => (string) $this->pick($row, $index, $this->columnMap['no_resi']) ?? '',
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
            'nama_funder' => (string) ($namaFunder ?? 'Panthera'),
            'vendor_lm' => $this->nullableString($this->pick($row, $index, $this->columnMap['vendor_lm'])),
            'tanggal_manifest' => $this->toDate($this->pick($row, $index, $this->columnMap['tanggal_manifest'])),
            'tgl_ho_sartrans' => $this->toDate($this->pick($row, $index, $this->columnMap['tgl_ho_sartrans'])),
            'koli' => $this->toIntOrNull($this->pick($row, $index, $this->columnMap['koli'])),
            'completed_date' => $this->toDate($this->pick($row, $index, $this->columnMap['completed_date'])),
            'tgl_sampai_kota_tujuan' => $this->toDate($this->pick($row, $index, $this->columnMap['tgl_sampai_kota_tujuan'])),
            'aging' => $this->toInt($this->pick($row, $index, $this->columnMap['aging'])),
            'status_akhir' => $this->nullableString($this->pick($row, $index, $this->columnMap['status_akhir'])) ?? 'On Process',
            'status_instalasi' => $this->nullableString($this->pick($row, $index, $this->columnMap['status_instalasi'])) ?? 'BELUM',
            'harga_per_shipment' => $this->toDecimal($this->pick($row, $index, $this->columnMap['harga_per_shipment'])),
            'status_invoice' => $statusInvoice,
            'stagging' => $this->nullableString($this->pick($row, $index, $this->columnMap['stagging'])),
            'sla' => $this->toSlaDays($slaRaw),
            'sla_result' => $this->nullableString($this->pick($row, $index, $this->columnMap['sla_result'])),
            'bast_tgl_balik' => $this->toDate($this->pick($row, $index, $this->columnMap['bast_tgl_balik'])),
            'bast_tgl_ke_finance' => $this->toDate($this->pick($row, $index, $this->columnMap['bast_tgl_ke_finance'])),
            'bast_keterangan' => $this->nullableString($this->pick($row, $index, $this->columnMap['bast_keterangan'])),
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
        Shipment::upsert($chunks, ['no_resi'], array_keys($chunks[0]));
    }

    protected function nullableString($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (string) $value;
    }

    protected function toDate($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2,4})/', $value, $m)) {
            $year = $m[3];
            if (strlen($year) === 2) {
                $year = '20'.$year;
            }

            return sprintf('%04d-%02d-%02d', (int) $year, (int) $m[2], (int) $m[1]);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $m)) {
            return $m[0];
        }

        return null;
    }

    /**
     * No Resi hanya dianggap sah bila 13-18 digit angka. Nilai lain
     * (#N/A, notasi ilmiah 1,0094E+15, formula, teks kosong) ditolak agar
     * tidak ikut terhitung sebagai pengiriman.
     */
    protected function normalizeResi(string $raw): ?string
    {
        $value = str_replace([',', ' ', "\xc2\xa0"], '', $raw);

        if ($value === '' || ! ctype_digit($value)) {
            return null;
        }

        if (strlen($value) < 13 || strlen($value) > 18) {
            return null;
        }

        return $value;
    }

    protected function toInt($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return 0;
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
     * Ambang SLA hanya bermakna sebagai jumlah hari dalam rentang 1..365.
     * Nilai lain (mis. serial date, teks, atau 0) tidak pernah dihitung
     * sebagai SLA dan disimpan NULL supaya tidak ikut ter-Smith.
     */
    protected function toSlaDays($value): ?int
    {
        $days = $this->toIntOrNull($value);

        if ($days === null || $days < 1 || $days > 365) {
            return null;
        }

        return $days;
    }

    protected function toDecimal($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return 0;
    }
}
