<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidResiAndVendorSlaTest extends TestCase
{
    use RefreshDatabase;

    private function shipment(array $overrides = []): Shipment
    {
        return Shipment::create(array_merge([
            'no_resi' => 'RESI'.uniqid(),
            'nama_sekolah' => 'SD Negeri 1',
            'vendor_lm' => 'BOMA',
            'provinsi' => 'Jawa Barat',
            'tanggal_manifest' => '2025-12-01',
            'status_akhir' => 'Completed',
        ], $overrides));
    }

    public function test_dashboard_hold_kpi_ignores_rows_without_a_valid_resi(): void
    {
        foreach (range(1, 3) as $i) {
            $this->shipment(['no_resi' => 'HOLDVALID'.$i, 'status_akhir' => 'Hold']);
        }

        foreach (range(1, 2) as $i) {
            $this->shipment(['no_resi' => null, 'status_akhir' => 'Hold']);
        }

        $this->shipment(['no_resi' => 'DONE1', 'status_akhir' => 'Completed', 'completed_date' => '2025-12-05']);
        $this->shipment(['no_resi' => 'DONE2', 'status_akhir' => 'Completed', 'completed_date' => '2025-12-06']);

        $user = User::factory()->create();
        $view = $this->actingAs($user)->get(route('dashboard'))->viewData();

        $this->assertSame(3, $view['holdCount']);
        $this->assertSame(5, $view['totalShipment']);
        $this->assertSame(5, $view['validDbTotal']);
        $this->assertSame(2, $view['invalidTotal']);
        $this->assertSame(3, $view['statusBreakdown']['Hold']);
    }

    public function test_shipments_page_defaults_to_valid_resi_and_can_show_invalid_rows(): void
    {
        $this->shipment(['no_resi' => 'VALID1']);
        $this->shipment(['no_resi' => null]);

        $user = User::factory()->create();

        $default = $this->actingAs($user)->get(route('shipments'))->viewData('shipments');
        $this->assertSame(1, $default->total());

        $invalid = $this->actingAs($user)->get(route('shipments', ['resi' => 'invalid']))->viewData('shipments');
        $this->assertSame(1, $invalid->total());
        $this->assertNull($invalid->first()->no_resi);

        $all = $this->actingAs($user)->get(route('shipments', ['resi' => 'all']))->viewData('shipments');
        $this->assertSame(2, $all->total());
    }

    public function test_staging_and_bottleneck_default_to_valid_resi(): void
    {
        $stage = 'PROCESS DOORING';

        $this->shipment([
            'no_resi' => 'STAGEVALID1',
            'stagging' => $stage,
            'sla_due_date' => '2025-12-10',
            'completed_date' => '2025-12-11',
        ]);
        $this->shipment([
            'no_resi' => null,
            'stagging' => $stage,
            'sla_due_date' => '2025-12-10',
            'completed_date' => '2025-12-11',
        ]);

        $user = User::factory()->create();

        $staging = $this->actingAs($user)
            ->getJson(route('api.staging', ['stagging' => $stage]))
            ->assertOk()
            ->json();
        $this->assertCount(1, $staging);
        $this->assertSame('STAGEVALID1', $staging[0]['no_resi']);

        $stagingAudit = $this->actingAs($user)
            ->getJson(route('api.staging', ['stagging' => $stage, 'include_invalid' => 1]))
            ->assertOk()
            ->json();
        $this->assertCount(2, $stagingAudit);

        $bottleneck = $this->actingAs($user)
            ->getJson(route('api.bottleneck', ['provinsi' => 'Jawa Barat', 'sla_mode' => 'customer']))
            ->assertOk()
            ->json();
        $this->assertCount(1, $bottleneck);
        $this->assertSame('STAGEVALID1', $bottleneck[0]['no_resi']);

        $bottleneckAudit = $this->actingAs($user)
            ->getJson(route('api.bottleneck', [
                'provinsi' => 'Jawa Barat',
                'sla_mode' => 'customer',
                'include_invalid' => 1,
            ]))
            ->assertOk()
            ->json();
        $this->assertCount(2, $bottleneckAudit);
    }

    public function test_dashboard_vendor_sla_mode_recomputes_from_sartrans_handover_to_delivered_date(): void
    {
        $this->shipment([
            'no_resi' => 'VENDOROVER1',
            'sla_due_date' => '2025-12-10',
            'tgl_ho_sartrans' => '2025-11-01',
            'tgl_delivered_panthera' => '2025-11-10',
            'sla_vendor_days' => 16,
            'sla_vendor_result' => 'Over SLA',
        ]);

        $this->shipment([
            'no_resi' => 'VENDORMEET1',
            'sla_due_date' => '2025-12-10',
            'tgl_ho_sartrans' => '2025-11-01',
            'tgl_delivered_panthera' => '2025-12-05',
            'sla_vendor_days' => 16,
            'sla_vendor_result' => 'Meet SLA',
        ]);

        $user = User::factory()->create();

        $vendor = $this->actingAs($user)
            ->get(route('dashboard', ['sla_mode' => 'vendor']))
            ->viewData();
        $this->assertSame('vendor', $vendor['slaMode']);
        $this->assertSame(1, $vendor['overSla']);
        $this->assertSame(1, $vendor['withinSla']);
        $this->assertEquals(50, $vendor['slaPct']);

        $customer = $this->actingAs($user)
            ->get(route('dashboard', ['sla_mode' => 'customer']))
            ->viewData();
        $this->assertSame('customer', $customer['slaMode']);
        $this->assertSame(0, $customer['overSla']);
        $this->assertSame(2, $customer['withinSla']);
        $this->assertEquals(100, $customer['slaPct']);
    }

    public function test_shipment_filter_can_select_vendor_sla_rows(): void
    {
        $this->shipment([
            'no_resi' => 'VENDOROVER2',
            'tgl_ho_sartrans' => '2025-12-01',
            'tgl_delivered_panthera' => '2025-12-20',
            'sla_vendor_days' => 16,
            'sla_vendor_result' => 'Over SLA',
        ]);
        $this->shipment([
            'no_resi' => 'VENDORMEET2',
            'tgl_ho_sartrans' => '2025-12-01',
            'tgl_delivered_panthera' => '2025-12-10',
            'sla_vendor_days' => 16,
            'sla_vendor_result' => 'Meet SLA',
        ]);

        $user = User::factory()->create();

        $over = $this->actingAs($user)
            ->get(route('shipments', ['sla' => 'vendor_out']))
            ->viewData('shipments');
        $this->assertSame(1, $over->total());
        $this->assertSame('VENDOROVER2', $over->first()->no_resi);

        $meet = $this->actingAs($user)
            ->get(route('shipments', ['sla' => 'vendor_meet']))
            ->viewData('shipments');
        $this->assertSame(1, $meet->total());
        $this->assertSame('VENDORMEET2', $meet->first()->no_resi);
    }
}
