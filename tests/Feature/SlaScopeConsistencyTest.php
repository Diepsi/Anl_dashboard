<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Kolom SLA di sumber tidak pernah satu arti: BOMA mengisinya dengan ambang hari
 * ("17"), sedangkan 31 vendor lain mengisinya dengan tanggal batas ("25/08/2025").
 * Keduanya dipakai sheet untuk menghitung verdict, tapi kode lama hanya menerima
 * angka 1..365, sehingga 28.320 baris non-BOMA kehilangan ambangnya dan hilang dari
 * seluruh perhitungan SLA.
 *
 * Sync kini menyatukan kedua bentuk itu menjadi `sla_due_date`, dan verdict
 * dihitung ulang dari `completed_date` terhadap deadline tersebut. Test di sini
 * mengunci hasil itu plus tiga tempat di mana panel, modal, dan filter dulu
 * berbeda jawabannya.
 */
class SlaScopeConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function shipment(array $overrides = []): Shipment
    {
        return Shipment::create(array_merge([
            'no_resi' => sprintf('%015d', ++self::$seq),
            'nama_sekolah' => 'SD Negeri 1',
            'vendor_lm' => 'BOMA',
            'provinsi' => 'Jawa Barat',
        ], $overrides));
    }

    /** BOMA style: ambang hari yang dijangkarkan ke Tgl HO dari SarTrans. */
    private function bomaWithDays(int $days, string $anchor, string $completed): Shipment
    {
        return $this->shipment([
            'sla' => $days,
            'tgl_ho_sartrans' => $anchor,
            'completed_date' => $completed,
            'sla_due_date' => Carbon::parse($anchor)
                ->addDays($days)
                ->toDateString(),
        ]);
    }

    /** Vendor lain style: kolom SLA berisi tanggal batas secara langsung. */
    private function vendorWithDueDate(string $due, string $completed): Shipment
    {
        return $this->shipment([
            'vendor_lm' => 'ACEH',
            'sla' => null,
            'sla_due_date' => $due,
            'completed_date' => $completed,
        ]);
    }

    public function test_a_date_based_deadline_is_verified(): void
    {
        // Bentuk kolom SLA yang dipakai 28.320 dari 31.709 baris. Dulu baris
        // seperti ini kehilangan ambangnya dan tidak pernah muncul di mana pun.
        $meet = $this->vendorWithDueDate('2025-08-25', '2025-08-23');
        $out = $this->vendorWithDueDate('2025-08-25', '2025-08-27');

        $this->assertSame('Meet SLA', $meet->sla_verdict);
        $this->assertSame('Out SLA', $out->sla_verdict);

        $user = User::factory()->create();
        $view = $this->actingAs($user)->get(route('dashboard'))->viewData();

        $this->assertSame(1, $view['withinSla'], 'Tanggal batas harus dihitung sebagai ambang yang sah.');
        $this->assertSame(1, $view['overSla']);
    }

    public function test_a_day_threshold_is_verified_against_its_anchor(): void
    {
        // 17 hari dari 08/10 berarti jatuh tempo 25/10, selesai 27/10 = Out SLA.
        $row = $this->bomaWithDays(17, '2025-10-08', '2025-10-27');

        $this->assertSame('2025-10-25', $row->sla_due_date->toDateString());
        $this->assertSame('Out SLA', $row->sla_verdict);
    }

    public function test_verdict_is_recomputed_rather_than_copied_from_the_sheet(): void
    {
        // 31 baris BOMA di sumber salah hitung. Karena dashboard menghitung ulang
        // dari ambangnya, baris ini harus keluar sebagai Out SLA meski sheet menulis
        // "Meet SLA". Kolom sla_result tetap disimpan untuk perbandingan.
        $row = $this->bomaWithDays(17, '2025-10-08', '2025-10-27');
        $row->forceFill(['sla_result' => 'Meet SLA'])->save();

        $this->assertSame('Out SLA', $row->fresh()->sla_verdict);
        $this->assertSame('Meet SLA', $row->fresh()->sla_result);
    }

    public function test_a_day_threshold_without_its_anchor_is_not_verified(): void
    {
        // Tanpa jangkar, ambang hari tidak bisa jadi tanggal, jadi baris ini tidak
        // boleh dihitung lebih dulu lalu dihitung benar.
        $this->shipment([
            'no_resi' => 'NOANCHOR1',
            'sla' => 17,
            'sla_due_date' => null,
            'completed_date' => '2025-10-27',
        ]);

        $user = User::factory()->create();
        $view = $this->actingAs($user)->get(route('dashboard'))->viewData();

        $this->assertSame(0, $view['slaCovered']);
        $this->assertSame(1, $view['slaUnverified']);
    }

    public function test_a_row_without_a_completed_date_stays_unverified(): void
    {
        $this->vendorWithDueDate('2025-08-25', '2025-08-23');
        $this->shipment([
            'no_resi' => 'BELUMSELESAI1',
            'vendor_lm' => 'ACEH',
            'sla_due_date' => '2025-08-25',
            'completed_date' => null,
        ]);

        $user = User::factory()->create();
        $view = $this->actingAs($user)->get(route('dashboard'))->viewData();

        $this->assertSame(1, $view['slaCovered'], 'Baris yang belum selesai tidak bisa dinilai.');
        $this->assertSame(1, $view['slaUnverified']);
        $this->assertSame(0, $view['overSla'], 'Baris tanpa tanggal selesai bukan Out SLA.');
    }

    public function test_bottleneck_modal_lists_both_deadline_shapes(): void
    {
        $this->bomaWithDays(3, '2025-12-01', '2025-12-11');
        $this->vendorWithDueDate('2025-12-10', '2025-12-11');
        $this->vendorWithDueDate('2025-12-10', '2025-12-05');

        $user = User::factory()->create();
        $rows = $this->actingAs($user)
            ->getJson(route('api.bottleneck', ['provinsi' => 'Jawa Barat']))
            ->assertOk()
            ->json();

        $this->assertCount(2, $rows, 'Kedua bentuk ambang harus sama-sama bisa masuk daftar.');
    }

    public function test_bottleneck_modal_row_count_matches_the_panel_that_opened_it(): void
    {
        foreach (range(1, 4) as $i) {
            $this->vendorWithDueDate('2025-12-10', '2025-12-11');
            $this->shipment(['no_resi' => "PANEL{$i}", 'sla_due_date' => '2025-12-10', 'completed_date' => '2025-12-11']);
        }

        $user = User::factory()->create();
        $panel = collect($this->actingAs($user)->get(route('dashboard'))->viewData('bottleneckStats'))
            ->firstWhere('provinsi', 'Jawa Barat');

        $rows = $this->actingAs($user)
            ->getJson(route('api.bottleneck', ['provinsi' => 'Jawa Barat']))
            ->json();

        $this->assertNotNull($panel);
        $this->assertSame(
            (int) $panel->total,
            count($rows),
            'The modal must not report a different population than the panel headline.'
        );
    }

    public function test_bottleneck_modal_follows_the_range_switcher(): void
    {
        $this->shipment([
            'no_resi' => 'RECENT_OUT1',
            'sla_due_date' => '2025-12-10',
            'completed_date' => '2025-12-11',
            'tanggal_manifest' => '2025-12-01',
        ]);

        $this->shipment([
            'no_resi' => 'OLD_OUT1',
            'sla_due_date' => '2025-10-01',
            'completed_date' => '2025-10-05',
            'tanggal_manifest' => '2025-09-25',
        ]);

        $user = User::factory()->create();

        $in30 = array_column(
            $this->actingAs($user)->getJson(route('api.bottleneck', ['provinsi' => 'Jawa Barat', 'range' => 30]))->json(),
            'no_resi'
        );

        $this->assertContains('RECENT_OUT1', $in30);
        $this->assertNotContains('OLD_OUT1', $in30, 'A 30-day window must exclude an older row.');

        $in90 = array_column(
            $this->actingAs($user)->getJson(route('api.bottleneck', ['provinsi' => 'Jawa Barat', 'range' => 90]))->json(),
            'no_resi'
        );

        $this->assertContains('OLD_OUT1', $in90, 'A 90-day window must reach further back.');
    }

    public function test_staging_modal_follows_the_range_switcher(): void
    {
        $stage = 'PROCESS DOORING';

        $this->shipment([
            'no_resi' => 'RECENT_STAGE1',
            'stagging' => $stage,
            'tanggal_manifest' => '2025-12-01',
            'completed_date' => '2025-12-11',
        ]);

        $this->shipment([
            'no_resi' => 'OLD_STAGE1',
            'stagging' => $stage,
            'tanggal_manifest' => '2025-01-01',
            'completed_date' => '2025-01-05',
        ]);

        $user = User::factory()->create();

        $in30 = array_column(
            $this->actingAs($user)->getJson(route('api.staging', ['stagging' => $stage, 'range' => 30]))->json(),
            'no_resi'
        );

        $this->assertContains('RECENT_STAGE1', $in30);
        $this->assertNotContains('OLD_STAGE1', $in30);
    }

    public function test_an_unrecognised_range_falls_back_to_all_time(): void
    {
        $this->shipment([
            'no_resi' => 'OLD_STAGE1',
            'stagging' => 'PROCESS DOORING',
            'tanggal_manifest' => '2025-09-25',
            'completed_date' => '2025-10-01',
        ]);

        $user = User::factory()->create();

        foreach ([999, -5, 'abc'] as $bogus) {
            $rows = array_column(
                $this->actingAs($user)
                    ->getJson(route('api.staging', ['stagging' => 'PROCESS DOORING', 'range' => $bogus]))
                    ->json(),
                'no_resi'
            );

            $this->assertContains(
                'OLD_STAGE1',
                $rows,
                "range={$bogus} is not a dashboard option and must not silently narrow the result."
            );
        }
    }

    public function test_shipments_page_sla_filter_matches_the_dashboard_verdicts(): void
    {
        $out = $this->vendorWithDueDate('2025-08-25', '2025-08-27');
        $meet = $this->vendorWithDueDate('2025-08-25', '2025-08-23');

        $this->shipment([
            'no_resi' => 'TANPAAMBANG1',
            'sla_due_date' => null,
            'completed_date' => '2025-08-27',
        ]);

        $user = User::factory()->create();

        $expectations = [
            'out' => $out->no_resi,
            'meet' => $meet->no_resi,
        ];

        foreach ($expectations as $filter => $expected) {
            $response = $this->actingAs($user)->get(route('shipments', ['sla' => $filter]));

            $response->assertOk();

            $this->assertSame(
                [$expected],
                $response->viewData('shipments')->pluck('no_resi')->all(),
                "The '{$filter}' filter must agree with the verdict the dashboard counts."
            );
        }
    }

    public function test_returned_shipments_reach_the_attention_panel_with_an_empty_age(): void
    {
        // All six real Retur rows carry no manifest date at all, so the age is unknown
        // rather than zero. They must still be visible, because a returned parcel is
        // exactly the kind of thing this panel exists to surface.
        $this->shipment([
            'no_resi' => 'RETUR1',
            'tanggal_manifest' => null,
            'completed_date' => '2025-10-27',
            'status_akhir' => 'Retur',
            'sla' => null,
            'sla_due_date' => null,
        ]);

        $user = User::factory()->create();
        $view = $this->actingAs($user)->get(route('dashboard'))->assertOk()->viewData();

        $retur = collect($view['attentionShipments'])->firstWhere('no_resi', 'RETUR1');

        $this->assertNotNull($retur, 'Retur must not be filtered out for lacking a manifest date.');
        $this->assertNull($retur->days_open, 'Age is unknown, so it must not be reported as 0.');
        $this->assertSame(1, $view['returCount']);
        $this->assertSame(1, $view['agingExcluded']);
        $this->assertSame(
            0,
            $view['agingBuckets']->sum(),
            'An unknown age must not be padded into the oldest age bucket.'
        );
    }

    public function test_returned_shipments_are_not_crowded_out_by_older_aged_rows(): void
    {
        // Retur has no manifest date, so its age is NULL. Ordering the panel by age alone
        // pushed every Retur row below the limit, which is how six returned parcels could
        // stay invisible while the panel looked full.
        foreach (range(1, 30) as $i) {
            $this->shipment([
                'no_resi' => "AGED{$i}",
                'tanggal_manifest' => '2025-01-01',
                'completed_date' => null,
                'status_akhir' => 'On Delivery',
            ]);
        }

        $this->shipment([
            'no_resi' => 'RETUR1',
            'tanggal_manifest' => null,
            'completed_date' => '2025-10-27',
            'status_akhir' => 'Retur',
        ]);

        $user = User::factory()->create();
        $attention = $this->actingAs($user)->get(route('dashboard'))->viewData('attentionShipments');

        $this->assertNotNull(
            $attention->firstWhere('no_resi', 'RETUR1'),
            'A returned parcel must not be displaced by aged rows that merely look worse.'
        );
    }

    public function test_a_row_without_a_deadline_is_labelled_unverified(): void
    {
        // Sumber menulis "Meet SLA" tanpa ambang, jadi baris ini tidak boleh
        // dilaporkan sebagai patuh meski teksnya terdengar tegas.
        $this->shipment([
            'no_resi' => 'TANPAAMBANG1',
            'sla_due_date' => null,
            'completed_date' => '2025-12-11',
            'sla_result' => 'Meet SLA',
            'tanggal_manifest' => '2025-12-01',
            'status_akhir' => 'Completed',
        ]);

        $user = User::factory()->create();

        $html = $this->actingAs($user)
            ->get(route('shipments', ['search' => 'TANPAAMBANG1']))
            ->assertOk()
            ->assertSee('TANPAAMBANG1')
            ->assertSee('Tidak terverifikasi')
            ->getContent();

        $this->assertStringNotContainsString(
            'text-emerald-600 border border-emerald-200',
            $this->sliceRow($html, 'TANPAAMBANG1'),
            'An unverifiable row must not be rendered with the compliant badge.'
        );

        $row = $this->actingAs($user)
            ->getJson(route('api.shipment-detail', ['no_resi' => 'TANPAAMBANG1']))
            ->json();

        $this->assertArrayHasKey('sla_due_date', $row);
        $this->assertNull($row['sla_due_date']);
        $this->assertNull($row['sla_verdict']);
    }

    /** Isolate the single <tr> that renders a given receipt number. */
    private function sliceRow(string $html, string $noResi): string
    {
        $start = strpos($html, $noResi);

        $this->assertNotFalse($start, "Receipt {$noResi} was not rendered at all.");

        $end = strpos($html, '</tr>', $start);

        return substr($html, $start, $end - $start);
    }
}
