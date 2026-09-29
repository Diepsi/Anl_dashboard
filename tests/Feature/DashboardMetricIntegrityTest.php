<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Locks in the fix for the two dashboard defects that silently produce absurd numbers:
 *  - duration shown as a negative average because manifest was dated after completion
 *  - the "last N days" filter anchored on a corrupt spike date
 */
class DashboardMetricIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(array $overrides = []): Shipment
    {
        return Shipment::create(array_merge([
            'nama_sekolah' => 'SD Negeri 1',
            'nama_funder' => 'ANL',
            'vendor_lm' => 'BOMA',
            'provinsi' => 'Jawa Barat',
        ], $overrides));
    }

    private function seedScenario(): void
    {
        // Three healthy shipments, each a 10-day delivery.
        foreach (range(1, 3) as $i) {
            $this->shipment([
                'no_resi' => "VALID{$i}",
                'tanggal_manifest' => '2025-12-01',
                'completed_date' => '2025-12-11',
                'status_akhir' => 'Completed',
                'sla_result' => 'Meet SLA',
            ]);
        }

        // Corrupt row: manifest dated long after completion. The old average used this
        // row and reported roughly -127 days.
        $this->shipment([
            'no_resi' => 'CORRUPT1',
            'tanggal_manifest' => '2026-08-13',
            'completed_date' => '2025-12-01',
            'status_akhir' => 'Completed',
            'sla_result' => 'Out SLA',
        ]);

        // Still in progress, so it has no completion date yet.
        $this->shipment([
            'no_resi' => 'ACTIVE1',
            'tanggal_manifest' => '2025-12-20',
            'completed_date' => null,
            'status_akhir' => 'Hold',
        ]);
    }

    public function test_impossible_date_order_rows_are_excluded_from_date_aggregates(): void
    {
        $this->seedScenario();

        $this->assertSame(4, Shipment::withConsistentDates()->count());
        $this->assertSame(5, Shipment::count());
    }

    public function test_duration_statistic_ignores_impossible_date_order_rows(): void
    {
        $this->seedScenario();

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $leadTime = $response->viewData('leadTime');

        $this->assertSame(3, $leadTime['sample'], 'Only the three valid rows should be measured.');
        $this->assertSame(10.0, (float) $leadTime['median']);
        $this->assertSame(10.0, (float) $leadTime['p90']);
        $this->assertGreaterThan(0, $leadTime['mean'], 'Mean must never be negative.');
    }

    public function test_range_anchor_uses_latest_completion_not_the_corrupt_manifest_spike(): void
    {
        $this->seedScenario();

        // Completes 41 days before the anchor: outside 30 days, inside 90 days.
        $this->shipment([
            'no_resi' => 'MID1',
            'tanggal_manifest' => '2025-09-25',
            'completed_date' => '2025-10-01',
            'status_akhir' => 'Completed',
        ]);

        // The spike row carries a manifest dated 2026-08-13, but its completion is
        // 2025-12-01. The window must anchor on the completion, not the manifest.
        $this->assertSame('2025-12-11', Carbon::parse(Shipment::max('completed_date'))->toDateString());

        $in30 = Shipment::inRange(30)->pluck('no_resi')->all();

        // Window is anchored on 2025-12-11: it reaches the anchor date, and is bounded.
        $this->assertContains('VALID1', $in30);
        $this->assertNotContains('MID1', $in30);

        // If the anchor had jumped to the 2026-08-13 spike, the 90-day window would
        // start at 2026-05-16 and drop every healthy row.
        $this->assertContains('MID1', Shipment::inRange(90)->pluck('no_resi')->all());
    }

    public function test_unfinished_shipments_stay_inside_every_range(): void
    {
        $this->seedScenario();

        // The dashboard exists to surface work that is still running, so a time window
        // must never hide a shipment that has no completion date yet.
        foreach ([30, 90] as $days) {
            $this->assertContains(
                'ACTIVE1',
                Shipment::inRange($days)->pluck('no_resi')->all(),
                "Unfinished shipments must stay visible in the {$days}-day range."
            );
        }
    }

    public function test_vendor_table_reports_median_and_sample_instead_of_mean_aging(): void
    {
        $this->seedScenario();

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $vendor = collect($response->viewData('vendorStats'))->firstWhere('vendor', 'BOMA');

        $this->assertNotNull($vendor);
        $this->assertSame(10.0, (float) $vendor['medianLead']);
        $this->assertSame(3, $vendor['leadSample']);
        $this->assertArrayNotHasKey('avgAging', $vendor, 'Aging average is not a delivery duration.');
        $this->assertArrayNotHasKey('avgLead', $vendor);
    }

    public function test_on_time_counts_both_sla_column_shapes_and_only_those(): void
    {
        // Panthera style: kolom SLA berisi tanggal batas, jadi sla_due_date terisi dan
        // verdict-nya bisa dihitung. Dulu kode hanya menerima angka 1..365, sehingga
        // 28.320 baris seperti ini hilang dari semua perhitungan SLA.
        $this->shipment([
            'no_resi' => 'PANTHERA1',
            'vendor_lm' => 'Panthera',
            'tanggal_manifest' => '2025-12-01',
            'completed_date' => '2025-12-05',
            'status_akhir' => 'Completed',
            'sla' => null,
            'sla_due_date' => '2025-12-10',
            'sla_result' => 'Meet SLA',
        ]);

        // Tanpa ambang sama sekali: teks verdict di sumber tidak bisa diaudit.
        $this->shipment([
            'no_resi' => 'TANPAAMBANG1',
            'vendor_lm' => 'TanpaAmbang',
            'tanggal_manifest' => '2025-12-01',
            'completed_date' => '2025-12-05',
            'status_akhir' => 'Completed',
            'sla' => null,
            'sla_due_date' => null,
            'sla_result' => 'Meet SLA',
        ]);

        // BOMA: ambang 3 hari dari Tgl HO SarTrans 01/12, selesai 05/12 = Out SLA meski
        // sheet menulis "Meet SLA".
        $this->shipment([
            'no_resi' => 'BOMA1',
            'vendor_lm' => 'BOMA',
            'tanggal_manifest' => '2025-12-01',
            'tgl_ho_sartrans' => '2025-12-01',
            'completed_date' => '2025-12-05',
            'status_akhir' => 'Completed',
            'sla' => 3,
            'sla_due_date' => '2025-12-04',
            'sla_result' => 'Meet SLA',
        ]);

        $user = User::factory()->create();
        $view = $this->actingAs($user)->get(route('dashboard'))->viewData('vendorStats');

        $boma = collect($view)->firstWhere('vendor', 'BOMA');
        $panthera = collect($view)->firstWhere('vendor', 'Panthera');
        $none = collect($view)->firstWhere('vendor', 'TanpaAmbang');

        $this->assertNotNull($boma);
        $this->assertNotNull($panthera);
        $this->assertNotNull($none);

        $this->assertSame(0.0, (float) $boma['onTimePct'], 'Verdict harus dihitung ulang, bukan diambil dari sheet.');
        $this->assertSame(1, $boma['slaCovered']);

        $this->assertSame(100.0, (float) $panthera['onTimePct'], 'Tanggal batas adalah ambang yang sah.');
        $this->assertSame(1, $panthera['slaCovered']);

        $this->assertNull(
            $none['onTimePct'],
            'A vendor without any threshold must not report an on-time percentage.'
        );
        $this->assertSame(0, $none['slaCovered']);
    }

    public function test_dashboard_reports_how_much_of_the_data_the_sla_rate_covers(): void
    {
        $this->shipment([
            'no_resi' => 'NO_THRESHOLD1',
            'tanggal_manifest' => '2025-12-01',
            'completed_date' => '2025-12-05',
            'status_akhir' => 'Completed',
            'sla' => null,
            'sla_due_date' => null,
            'sla_result' => 'Meet SLA',
        ]);

        $user = User::factory()->create();
        $data = $this->actingAs($user)->get(route('dashboard'))->viewData('slaCovered');

        $this->assertSame(0, $data, 'Nothing here has a usable threshold, so the KPI must say so.');
    }
}
