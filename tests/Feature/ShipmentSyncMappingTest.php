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
        'Tanggal HO ke Vendor',
        'Tgl HO dari SarTrans',
        'Completed date',
        'SLA',
        'Result Delivery for Panthera',
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

        // Panthera's SLA column holds a date serial, never a day count.
        $this->assertNull($this->map(['SLA' => '45940'])['sla']);
        $this->assertNull($this->map(['SLA' => '0'])['sla']);
        $this->assertNull($this->map(['SLA' => ''])['sla']);
        $this->assertNull($this->map(['SLA' => '400'])['sla']);
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
        $this->assertNull($this->normalizeResi('1,01E+15'));
        $this->assertNull($this->normalizeResi('1,0094E+15'));
        $this->assertNull($this->normalizeResi('123'));
        $this->assertNull($this->normalizeResi(''));
    }
}
