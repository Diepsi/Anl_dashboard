<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTrendTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(array $overrides = []): Shipment
    {
        return Shipment::create(array_merge([
            'nama_sekolah' => 'SD Negeri 1',
            'vendor_lm' => 'BOMA',
            'provinsi' => 'Jawa Barat',
        ], $overrides));
    }

    private function trend(array $query = []): array
    {
        $user = User::factory()->create();

        return $this->actingAs($user)
            ->get(route('dashboard', $query))
            ->assertOk()
            ->viewData('trend');
    }

    private function indexOf(array $labels, string $label): int
    {
        $index = array_search($label, $labels, true);

        $this->assertNotFalse($index, "Label {$label} was not present in the trend axis.");

        return $index;
    }

    public function test_completed_volume_uses_completion_date_not_manifest_date(): void
    {
        $this->shipment([
            'no_resi' => 'DONE1',
            'tanggal_manifest' => '2025-10-01',
            'completed_date' => '2025-10-10',
            'status_akhir' => 'Completed',
            'sla_due_date' => '2025-10-11',
        ]);

        $this->shipment([
            'no_resi' => 'ACTIVE1',
            'tanggal_manifest' => '2025-10-01',
            'completed_date' => null,
            'status_akhir' => 'On Delivery',
        ]);

        $trend = $this->trend();

        $manifestIndex = $this->indexOf($trend['labels'], '01 Oct 25');
        $completedIndex = $this->indexOf($trend['labels'], '10 Oct 25');

        $this->assertSame(2, $trend['kirim'][$manifestIndex]);
        $this->assertSame(0, $trend['selesai'][$manifestIndex]);
        $this->assertSame(0, $trend['kirim'][$completedIndex]);
        $this->assertSame(1, $trend['selesai'][$completedIndex]);
    }

    public function test_daily_axis_fills_days_without_activity(): void
    {
        $this->shipment([
            'no_resi' => 'A1',
            'tanggal_manifest' => '2025-10-01',
            'completed_date' => '2025-10-05',
            'status_akhir' => 'Completed',
        ]);

        $this->shipment([
            'no_resi' => 'B1',
            'tanggal_manifest' => '2025-10-04',
            'completed_date' => '2025-10-05',
            'status_akhir' => 'Completed',
        ]);

        $trend = $this->trend();

        $this->assertSame(
            ['01 Oct 25', '02 Oct 25', '03 Oct 25', '04 Oct 25', '05 Oct 25'],
            $trend['labels']
        );

        $this->assertSame(0, $trend['kirim'][$this->indexOf($trend['labels'], '02 Oct 25')]);
        $this->assertSame(0, $trend['kirim'][$this->indexOf($trend['labels'], '03 Oct 25')]);
    }

    public function test_out_sla_and_compliance_are_grouped_by_completion_date(): void
    {
        $this->shipment([
            'no_resi' => 'OUT1',
            'tanggal_manifest' => '2025-10-01',
            'completed_date' => '2025-10-10',
            'status_akhir' => 'Completed',
            'sla_due_date' => '2025-10-05',
        ]);

        $this->shipment([
            'no_resi' => 'MEET1',
            'tanggal_manifest' => '2025-10-01',
            'completed_date' => '2025-10-10',
            'status_akhir' => 'Completed',
            'sla_due_date' => '2025-10-11',
        ]);

        $trend = $this->trend();

        $manifestIndex = $this->indexOf($trend['labels'], '01 Oct 25');
        $completedIndex = $this->indexOf($trend['labels'], '10 Oct 25');

        $this->assertSame(0, $trend['outSla'][$manifestIndex]);
        $this->assertNull($trend['compliance'][$manifestIndex]);
        $this->assertSame(1, $trend['outSla'][$completedIndex]);
        $this->assertSame(50, $trend['compliance'][$completedIndex]);
    }

    public function test_compliance_stays_null_when_no_row_can_be_verified(): void
    {
        $this->shipment([
            'no_resi' => 'NODUE1',
            'tanggal_manifest' => '2025-10-01',
            'completed_date' => '2025-10-05',
            'status_akhir' => 'Completed',
            'sla_due_date' => null,
        ]);

        $trend = $this->trend();

        $completedIndex = $this->indexOf($trend['labels'], '05 Oct 25');

        $this->assertSame(1, $trend['selesai'][$completedIndex]);
        $this->assertNull($trend['compliance'][$completedIndex]);
    }

    public function test_range_window_drops_older_completion_dates(): void
    {
        $this->shipment([
            'no_resi' => 'OLD1',
            'tanggal_manifest' => '2025-01-01',
            'completed_date' => '2025-01-05',
            'status_akhir' => 'Completed',
        ]);

        $this->shipment([
            'no_resi' => 'NEW1',
            'tanggal_manifest' => '2025-12-01',
            'completed_date' => '2025-12-11',
            'status_akhir' => 'Completed',
        ]);

        $trend = $this->trend(['range' => 30]);

        $this->assertContains('11 Dec', $trend['labels']);
        $this->assertNotContains('05 Jan', $trend['labels']);
    }
}
