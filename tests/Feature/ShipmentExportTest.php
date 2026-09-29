<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

/**
 * Berkas yang keluar dari sistem sering dipakai sebagai pengganti sheet sumber,
 * jadi file itu harus bisa dibaca tanpa tebakan: ia harus memuat nilai yang sama
 * dengan yang tersimpan, dan punya catatan yang menyebutkan seberapa banyak baris
 * yang bisa diverifikasi.
 */
class ShipmentExportTest extends TestCase
{
    use RefreshDatabase;

    private function export(array $filters = []): Worksheet
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('shipments.export'), $filters);

        $response->assertOk();

        // The export streams a file on disk, so read it from where the response
        // points rather than from the body, which is empty for downloads.
        $path = $response->baseResponse->getFile()->getPathname();

        $spreadsheet = (new XlsxReader)->load($path);
        unlink($path);

        return $spreadsheet->getActiveSheet();
    }

    private function rows(Worksheet $sheet): array
    {
        $rows = [];

        foreach ($sheet->toArray() as $index => $row) {
            if ($index === 0) {
                continue;
            }

            if (($row[0] ?? null) === null || $row[0] === '') {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function shipment(array $overrides = []): Shipment
    {
        return Shipment::create(array_merge([
            'no_resi' => 'RESI1',
            'nama_sekolah' => 'SD Negeri 1',
            'provinsi' => 'Jawa Barat',
            'vendor_lm' => 'BOMA',
        ], $overrides));
    }

    public function test_export_writes_the_recomputed_verdict_next_to_the_sheet_text(): void
    {
        // Sheet menulis "Meet SLA" untuk baris yang sebenarnya lewat tenggat.
        $this->shipment([
            'no_resi' => 'BOMA1',
            'sla' => 3,
            'sla_due_date' => '2025-12-04',
            'completed_date' => '2025-12-05',
            'sla_result' => 'Meet SLA',
            'tanggal_manifest' => '2025-12-01',
        ]);

        $sheet = $this->export();
        $header = array_flip($sheet->toArray()[0]);
        $row = $this->rows($sheet)[0];

        $this->assertSame('Out SLA', $row[$header['Status SLA']]);
        $this->assertSame('Meet SLA', $row[$header['Verdict Sheet']], 'Teks sumber harus tetap ikut terbawa.');
        $this->assertStringStartsWith('2025-12-04', (string) $row[$header['Batas SLA']]);
    }

    public function test_export_labels_unverifiable_rows_instead_of_leaving_them_blank(): void
    {
        $this->shipment([
            'no_resi' => 'TANPAAMBANG1',
            'sla_due_date' => null,
            'completed_date' => '2025-12-05',
            'sla_result' => 'Meet SLA',
            'tanggal_manifest' => '2025-12-01',
        ]);

        $sheet = $this->export();
        $header = array_flip($sheet->toArray()[0]);
        $row = $this->rows($sheet)[0];

        $this->assertSame('Tidak terverifikasi', $row[$header['Status SLA']]);

        $meta = collect($sheet->toArray())->map(fn ($r) => $r[0])->filter()->last();

        $this->assertStringContainsString('dihitung ulang', (string) $meta);
        $this->assertStringContainsString(
            '0.0% dari baris di file ini (0 dari 1 baris terverifikasi)',
            (string) $meta
        );
    }

    public function test_coverage_note_counts_only_the_rows_in_the_file(): void
    {
        // Dua baris terverifikasi, lalu filter yang menyisakan hanya satu di antaranya.
        $this->shipment([
            'no_resi' => 'JABAR1',
            'provinsi' => 'Jawa Barat',
            'sla_due_date' => '2025-12-10',
            'completed_date' => '2025-12-05',
            'tanggal_manifest' => '2025-12-01',
        ]);

        $this->shipment([
            'no_resi' => 'JABAR2',
            'provinsi' => 'Jawa Barat',
            'sla_due_date' => '2025-12-10',
            'completed_date' => '2025-12-05',
            'tanggal_manifest' => '2025-12-01',
        ]);

        $this->shipment([
            'no_resi' => 'BALI1',
            'provinsi' => 'Bali',
            'sla_due_date' => '2025-12-10',
            'completed_date' => '2025-12-05',
            'tanggal_manifest' => '2025-12-01',
        ]);

        $all = collect($this->export()->toArray())->map(fn ($r) => $r[0])->filter()->last();

        $this->assertStringContainsString(
            '100.0% dari baris di file ini (3 dari 3 baris terverifikasi)',
            (string) $all
        );

        $filtered = collect($this->export(['provinsi' => 'Bali'])->toArray())
            ->map(fn ($r) => $r[0])
            ->filter()
            ->last();

        $this->assertStringContainsString(
            '100.0% dari baris di file ini (1 dari 1 baris terverifikasi)',
            (string) $filtered,
            'Catatan cakupan harus mengikuti isi file, bukan seluruh tabel.'
        );
    }

    public function test_export_carries_the_new_source_columns(): void
    {
        $this->shipment([
            'no_resi' => 'RESI1',
            'koli' => 4,
            'tgl_ho_sartrans' => '2025-12-01',
            'sla' => 3,
            'sla_threshold_days' => 400,
            'tanggal_manifest' => '2025-12-01',
        ]);

        $sheet = $this->export();
        $header = array_flip($sheet->toArray()[0]);
        $row = $this->rows($sheet)[0];

        $this->assertEquals(4, $row[$header['KOLI']]);
        $this->assertStringStartsWith('2025-12-01', (string) $row[$header['Tgl HO dari SarTrans']]);
        $this->assertEquals(400, $row[$header['Ambang SLA (hari)']]);
    }

    public function test_missing_prices_stay_empty_rather_than_becoming_zero(): void
    {
        // 28.667 baris di sumber tidak punya harga. Menuliskannya sebagai 0 membuat
        // rata-rata harga terlihat jauh lebih murah daripada kenyataannya.
        $this->shipment(['no_resi' => 'TANPAHARGA', 'tanggal_manifest' => '2025-12-01']);

        $sheet = $this->export();
        $header = array_flip($sheet->toArray()[0]);
        $row = $this->rows($sheet)[0];

        $this->assertNull($row[$header['Harga / Shipment']]);
    }

    public function test_export_marks_duplicate_receipt_rows(): void
    {
        $this->shipment(['no_resi' => 'DUP', 'is_duplicate_no_resi' => true, 'tanggal_manifest' => '2025-12-01']);
        $this->shipment(['no_resi' => 'UNIK', 'is_duplicate_no_resi' => false, 'tanggal_manifest' => '2025-12-01']);

        $sheet = $this->export();
        $header = array_flip($sheet->toArray()[0]);
        $rows = collect($this->rows($sheet))->keyBy(fn ($r) => $r[0]);

        $this->assertSame('Ya', $rows['DUP'][$header['Duplikat Resi']]);
        $this->assertSame('Tidak', $rows['UNIK'][$header['Duplikat Resi']]);
    }

    public function test_the_export_route_is_authenticated(): void
    {
        $this->post(route('shipments.export'))->assertRedirect(route('login'));
    }
}
