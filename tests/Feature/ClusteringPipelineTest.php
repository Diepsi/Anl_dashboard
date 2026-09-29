<?php

namespace Tests\Feature;

use App\Models\RegionalCluster;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ClusteringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class ClusteringPipelineTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ClusteringService
    {
        return new ClusteringService;
    }

    private function shipment(array $overrides = []): Shipment
    {
        return Shipment::create(array_merge([
            'no_resi' => str_pad((string) random_int(1, 999999), 15, '0', STR_PAD_LEFT),
            'nama_sekolah' => 'SD 1',
            'nama_funder' => 'ANL',
            'kota_kabupaten' => 'Kota X',
            'provinsi' => 'Jawa Barat',
            'status_akhir' => 'Completed',
            'tanggal_manifest' => '2025-12-01',
            'completed_date' => '2025-12-10',
            'sla_due_date' => '2025-12-12',
        ], $overrides));
    }

    public function test_ingest_upserts_regional_results_and_prunes_stale(): void
    {
        RegionalCluster::create([
            'kota_kabupaten' => 'Kota X',
            'provinsi' => 'Jawa Barat',
            'cluster_id' => 2,
            'cluster_label' => 'Standard / Low Risk Zone',
            'total_shipment' => 1,
            'verifiable_count' => 1,
        ]);

        RegionalCluster::create([
            'kota_kabupaten' => 'Kota Lama',
            'provinsi' => 'Lampung',
            'cluster_id' => 2,
            'cluster_label' => 'Standard / Low Risk Zone',
            'total_shipment' => 1,
            'verifiable_count' => 1,
        ]);

        $regions = [
            [
                'kota_kabupaten' => 'Kota X',
                'provinsi' => 'Jawa Barat',
                'cluster_id' => 0,
                'cluster_label' => 'High Risk / Bottleneck Zone',
                'avg_aging' => 22.5,
                'out_sla_rate' => 61.0,
                'total_shipment' => 12,
                'verifiable_count' => 11,
                'risk_index' => 88.0,
            ],
            [
                'kota_kabupaten' => 'Kota Baru',
                'provinsi' => 'Jawa Tengah',
                'cluster_id' => 2,
                'cluster_label' => 'Standard / Low Risk Zone',
                'avg_aging' => 9.0,
                'out_sla_rate' => 12.0,
                'total_shipment' => 30,
                'verifiable_count' => 28,
                'risk_index' => 10.0,
            ],
        ];

        $meta = $this->service()->persist($regions);

        $this->assertSame(2, RegionalCluster::count(), 'Wilayah lama yang lepas harus dibuang.');
        $this->assertNull(
            RegionalCluster::query()->where('kota_kabupaten', 'Kota Lama')->first()
        );

        $kotaX = RegionalCluster::query()->where('kota_kabupaten', 'Kota X')->first();
        $this->assertSame('High Risk / Bottleneck Zone', $kotaX->cluster_label);
        $this->assertSame(0, $kotaX->cluster_id);

        $cache = cache(ClusteringService::RESULT_CACHE_KEY);
        $this->assertNotNull($cache, 'Meta run harus tersimpan ke cache.');
        $this->assertSame(2, $cache['n_regions']);
        $this->assertCount(2, $meta['summary']);
        $this->assertSame('High Risk / Bottleneck Zone', $meta['summary'][0]['label']);
        $this->assertSame('Standard / Low Risk Zone', $meta['summary'][1]['label']);
    }

    public function test_observations_csv_respects_eligibility_rules(): void
    {
        $this->shipment(['no_resi' => '1', 'completed_date' => '2025-12-10', 'sla_due_date' => '2025-12-12']);                 // durasi 9, is_out 0
        $this->shipment(['no_resi' => '2', 'status_akhir' => 'On Process', 'completed_date' => null, 'sla_due_date' => '2025-12-12']); // tanpa durasi, tanpa is_out
        $this->shipment(['no_resi' => '3', 'completed_date' => '2025-12-20', 'sla_due_date' => '2025-12-12']);                // durasi 19, is_out 1
        $this->shipment(['no_resi' => '4', 'kota_kabupaten' => null]);                                                       // dikecualikan

        $path = tempnam(sys_get_temp_dir(), 'obs_');
        $this->service()->writeObservations($path);

        $rows = array_map('str_getcsv', file($path));
        $this->assertSame(['kota_kabupaten', 'provinsi', 'durasi', 'is_out'], $rows[0]);

        $data = collect(array_slice($rows, 1));
        $this->assertCount(3, $data, 'Baris tanpa kota/kabupaten tidak ikut.');

        $this->assertSame(
            ['', '9', '19'],
            $data->map(fn ($r) => $r[2])->sort()->values()->all(),
            'durasi hanya terisi untuk status Completed dengan tanggal konsisten.'
        );

        $this->assertSame(
            ['', '0', '1'],
            $data->map(fn ($r) => $r[3])->sort()->values()->all(),
            'is_out hanya terisi untuk baris SLA terverifikasi.'
        );

        unlink($path);
    }

    public function test_command_fails_gracefully_when_python_is_missing(): void
    {
        config(['services.python.bin' => 'python-tidak-ada.exe']);

        $this->artisan('analytics:run-clustering')
            ->expectsOutputToContain('gagal')
            ->assertExitCode(1);
    }

    public function test_dashboard_renders_clustering_section(): void
    {
        RegionalCluster::create([
            'kota_kabupaten' => 'Kota X',
            'provinsi' => 'Jawa Barat',
            'cluster_id' => 0,
            'cluster_label' => 'High Risk / Bottleneck Zone',
            'avg_aging' => 22.5,
            'out_sla_rate' => 61.0,
            'total_shipment' => 12,
            'verifiable_count' => 11,
            'risk_index' => 88.0,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Analitik Clustering Performance Wilayah')
            ->assertSee('clusterChart');
    }

    public function test_clustering_is_scheduled_to_run_after_successful_sync(): void
    {
        $syncEvents = collect(Schedule::events())
            ->filter(fn ($e) => str_contains((string) $e->command, 'shipments:sync'));

        $this->assertTrue($syncEvents->isNotEmpty(), 'shipments:sync harus terjadwal.');

        $clustered = $syncEvents->contains(function ($event) {
            $callbackProp = (new \ReflectionClass($event))->getProperty('afterCallbacks');

            return count($callbackProp->getValue($event)) > 0;
        });

        $this->assertTrue($clustered, 'Clustering harus menempel sebagai success-callback pada sync.');
    }
}
