<?php

namespace Tests\Feature;

use App\Services\ShipmentSyncService;
use Tests\TestCase;

/**
 * Locks in the import rules that keep the dashboard from reporting invented numbers.
 *
 * Three defects motivated these assertions:
 *  - tanggal_manifest silently fell back to "Tgl HO dari SarTrans", stamping 2,392
 *    Panthera rows with one wrong date and making duration statistics unusable
 *  - the SLA column holds a date serial for Panthera, which parsed to 0 days and
 *    dragged every on-time percentage
 *  - No Resi is stored as a number in the sheet, so long values arrive as
 *    scientific notation ("1,01E+15") and a comma splits the CSV row
 */
class ShipmentSyncMappingTest extends TestCase
{
    private const HEADERS = [
        'No Resi',
        'Status Akhir',
        'Status Instalasi',
        'Tanggal HO ke Vendor',
        'Tgl HO dari SarTrans',
        'Completed date',
        'SLA',
        'Aging',
        'Result Delivery for Panthera',
        'Harga Per Shipment',
        'kOLI',
    ];

    private function service(): ShipmentSyncService
    {
        return new ShipmentSyncService;
    }

    private function index(): array
    {
        $method = new \ReflectionMethod(ShipmentSyncService::class, 'buildHeaderIndex');

        return $method->invoke($this->service(), self::HEADERS);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    private function map(array $overrides = []): array
    {
        $row = array_fill(0, count(self::HEADERS), '');
        $row[0] = '19201203013555';

        foreach (self::HEADERS as $col => $header) {
            if (array_key_exists($header, $overrides)) {
                $row[$col] = $overrides[$header];
            }
        }

        $method = new \ReflectionMethod(ShipmentSyncService::class, 'mapRow');

        return $method->invoke($this->service(), $row, $this->index());
    }

    private function normalizeResi(string $raw): ?string
    {
        $method = new \ReflectionMethod(ShipmentSyncService::class, 'normalizeResi');

        return $method->invoke($this->service(), $raw);
    }

    public function test_manifest_does_not_borrow_the_sartrans_handover_date(): void
    {
        $data = $this->map([
            'Tanggal HO ke Vendor' => '',
            'Tgl HO dari SarTrans' => '13/08/2026',
        ]);

        $this->assertNull(
            $data['tanggal_manifest'],
            'tanggal_manifest must stay empty instead of inheriting the SarTrans handover date'
        );
        $this->assertSame('2026-08-13', $data['tgl_ho_sartrans']);
    }

    public function test_manifest_uses_the_vendor_handover_when_present(): void
    {
        $data = $this->map([
            'Tanggal HO ke Vendor' => '01/12/2025',
            'Tgl HO dari SarTrans' => '05/12/2025',
        ]);

        $this->assertSame('2025-12-01', $data['tanggal_manifest']);
        $this->assertSame('2025-12-05', $data['tgl_ho_sartrans']);
    }

    public function test_sla_only_survives_as_a_plausible_day_count(): void
    {
        $this->assertSame(17, $this->map(['SLA' => '17'])['sla']);

        $this->assertNull($this->map(['SLA' => '45940'])['sla']);
        $this->assertNull($this->map(['SLA' => '0'])['sla']);
        $this->assertNull($this->map(['SLA' => ''])['sla']);
        $this->assertNull($this->map(['SLA' => '400'])['sla']);
    }

    /**
     * Kolom SLA di sumber berisi dua bentuk yang sama-sama sah. BOMA mengisi ambang
     * hari, 31 vendor lain mengisi tanggal batas. Keduanya dipakai sheet untuk
     * menghitung verdict, jadi keduanya harus sampai ke database.
     */
    public function test_a_date_shaped_sla_becomes_the_deadline(): void
    {
        $data = $this->map(['SLA' => '25/08/2025', 'Completed date' => '23/08/2025']);

        $this->assertNull($data['sla'], 'Tanggal batas bukan jumlah hari.');
        $this->assertSame('2025-08-25', $data['sla_due_date']);
    }

    public function test_a_day_shaped_sla_is_anchored_to_the_sartrans_handover(): void
    {
        $data = $this->map([
            'SLA' => '17',
            'Tgl HO dari SarTrans' => '08/10/2025',
            'Completed date' => '27/10/2025',
        ]);

        $this->assertSame(17, $data['sla']);
        $this->assertSame('2025-10-25', $data['sla_due_date']);
    }

    public function test_a_day_threshold_without_its_anchor_gets_no_deadline(): void
    {
        // Tanpa jangkar, ambang hari tidak bisa jadi tanggal. Memberinya deadline
        // karangan akan membuat baris ini selalu terbaca "Meet SLA".
        $this->assertNull($this->map(['SLA' => '17', 'Tgl HO dari SarTrans' => ''])['sla_due_date']);
    }

    public function test_an_anchor_later_than_completion_gets_no_deadline(): void
    {
        $data = $this->map([
            'SLA' => '17',
            'Tgl HO dari SarTrans' => '30/12/2025',
            'Completed date' => '05/12/2025',
        ]);

        $this->assertNull(
            $data['sla_due_date'],
            'Jangkar yang lebih baru dari tanggal selesai membuat deadline selalu lolos.'
        );
    }

    public function test_the_threshold_column_keeps_values_above_365(): void
    {
        // Kolom Aging berisi 300-400 hari. Batas 365 ala kolom SLA akan membuangnya.
        $data = $this->map(['Aging' => '400', 'SLA' => '17', 'Tgl HO dari SarTrans' => '01/12/2025']);

        $this->assertSame(400, $data['sla_threshold_days']);
        $this->assertNull($this->map(['Aging' => '#N/A'])['sla_threshold_days']);
        $this->assertNull($this->map(['Aging' => ''])['sla_threshold_days']);
    }

    public function test_empty_cells_are_stored_as_null_instead_of_invented_values(): void
    {
        $data = $this->map([
            'Status Akhir' => '',
            'SLA' => '',
        ]);

        $this->assertNull($data['status_akhir'], 'Kosong bukan "On Process".');
        $this->assertNull($data['nama_funder'], 'Kosong bukan "Panthera".');
        $this->assertNull($data['status_instalasi'], 'Kosong bukan "BELUM".');
        $this->assertNull($data['harga_per_shipment'], 'Kosong bukan 0.');
    }

    public function test_formula_errors_are_discarded_rather_than_stored_as_status(): void
    {
        // Status Instalasi berisi "#N/A" pada 20.866 baris dan sla_result pada 2 baris.
        foreach (['#N/A', '#VALUE!', '#REF!', '#DIV/0!', '#NAME?', 'Err:502'] as $error) {
            $data = $this->map(['Status Instalasi' => $error, 'Result Delivery for Panthera' => $error]);

            $this->assertNull($data['status_instalasi'], "{$error} is a broken formula, not a status.");
            $this->assertNull($data['sla_result']);
        }

        $real = $this->map(['Status Instalasi' => 'DONE', 'Result Delivery for Panthera' => 'Meet SLA']);

        $this->assertSame('DONE', $real['status_instalasi']);
        $this->assertSame('Meet SLA', $real['sla_result']);
    }

    public function test_prices_are_not_zero_filled(): void
    {
        $this->assertSame(250000.0, $this->map(['Harga Per Shipment' => '250000'])['harga_per_shipment']);
        $this->assertNull($this->map(['Harga Per Shipment' => ''])['harga_per_shipment']);
        $this->assertNull($this->map(['Harga Per Shipment' => '1,0094E+15'])['harga_per_shipment']);
    }

    public function test_impossible_calendar_dates_are_rejected(): void
    {
        $this->assertNull($this->map(['Completed date' => '31/02/2026'])['completed_date']);
        $this->assertNull($this->map(['Completed date' => '2026-13-45'])['completed_date']);
        $this->assertSame('2026-02-28', $this->map(['Completed date' => '28/02/2026'])['completed_date']);
    }

    public function test_koli_is_imported_as_a_number(): void
    {
        $this->assertSame(3, $this->map(['kOLI' => '3'])['koli']);
        $this->assertNull($this->map(['kOLI' => ''])['koli']);
    }

    public function test_malformed_resi_values_are_rejected(): void
    {
        $this->assertSame('19201203013555', $this->normalizeResi('19201203013555'));
        $this->assertSame('1009400821166123', $this->normalizeResi('1009400821166123'));

        // Thousands separators are harmless, the values underneath are real.
        $this->assertSame('19201203013555', $this->normalizeResi('19,201,203,013,555'));

        $this->assertNull($this->normalizeResi('#N/A'));
        $this->assertNull($this->normalizeResi('123'));
        $this->assertNull($this->normalizeResi(''));
    }

    /**
     * Google Sheets menampilkan no resi 15-16 digit sebagai angka, jadi CSV-nya keluar
     * dalam notasi ilmiah ("1,01E+15") atau dengan pemisah ribuan ("1.009.400.821.166.140,00").
     * Bentuk pendek itu memakai E+ atau "." sebagai pemisah, dan harus dipulihkan ke
     * digit bulatnya karena memang berisi no resi asli.
     */
    public function test_scientific_notation_is_recovered_as_resi(): void
    {
        $this->assertSame('1010000000000000', $this->normalizeResi('1,01E+15'));
        $this->assertSame('1010000000000000', $this->normalizeResi('1.01E+15'));
        $this->assertSame('1009400000000000', $this->normalizeResi('1,0094E+15'));
        $this->assertSame('1009400821166140', $this->normalizeResi('1.009.400.821.166.140,00'));

        // Eksponen yang terlalu kecil tidak bisa sampai 13 digit, jadi tetap ditolak.
        $this->assertNull($this->normalizeResi('1,01E+5'));
    }
}
