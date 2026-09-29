<?php

namespace App\Services;

use App\Models\RegionalCluster;
use App\Models\Shipment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ClusteringService
{
    public const WORK_DIR = 'app/clustering';

    public const RESULT_CACHE_KEY = 'clustering.last_run';

    public function run(): array
    {
        $workspace = storage_path(self::WORK_DIR);
        File::ensureDirectoryExists($workspace);

        $input = $workspace.'/observations.csv';
        $output = $workspace.'/result.json';

        try {
            $this->writeObservations($input);

            $process = new Process([
                config('services.python.bin'),
                config('services.python.script'),
                $input,
                $output,
            ]);
            $process->setTimeout((int) config('services.python.timeout'));
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException('Clustering gagal: '.$this->snippet(
                    $process->getErrorOutput() ?: $process->getOutput()
                ));
            }

            $regions = $this->readResult($output);
            $meta = $this->persist($regions);

            return ['regions' => count($regions), 'meta' => $meta];
        } finally {
            @unlink($input);
            @unlink($output);
        }
    }

    /**
     * CSV observasi per baris pengiriman. Kelayakan ditentukan di PHP supaya data
     * yang masuk ke model sama jujurnya dengan yang dipakai dasbor KPI SLA:
     *  - durasi  : hanya status Completed + tanggal konsisten (manifest <= completed)
     *  - is_out  : hanya baris terverifikasi (completed_date & sla_due_date ada)
     *  - wilayah : tanpa kota/kabupaten dikecualikan (tidak bisa diidentifikasi)
     */
    public function writeObservations(string $path): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, ['kota_kabupaten', 'provinsi', 'durasi', 'is_out']);

        Shipment::query()
            ->whereNotNull('kota_kabupaten')
            ->where('kota_kabupaten', '!=', '')
            ->selectRaw('kota_kabupaten')
            ->selectRaw('provinsi')
            ->selectRaw('CASE WHEN status_akhir = "Completed"
                AND tanggal_manifest IS NOT NULL
                AND completed_date IS NOT NULL
                AND tanggal_manifest <= completed_date
                THEN DATEDIFF(completed_date, tanggal_manifest) ELSE NULL END as durasi')
            ->selectRaw('CASE WHEN sla_due_date IS NOT NULL AND completed_date IS NOT NULL
                THEN CASE WHEN completed_date <= sla_due_date THEN 0 ELSE 1 END
                ELSE NULL END as is_out')
            ->orderBy('kota_kabupaten')
            ->chunk(2000, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->kota_kabupaten,
                        $row->provinsi,
                        $row->durasi === null ? '' : (int) $row->durasi,
                        $row->is_out === null ? '' : (int) $row->is_out,
                    ]);
                }
            });

        fclose($handle);
    }

    protected function readResult(string $path): array
    {
        $json = json_decode(File::get($path), true);

        $regions = is_array($json) ? ($json['regions'] ?? []) : [];

        if ($regions === []) {
            throw new \RuntimeException('Output clustering tidak terbaca atau kosong (result.json).');
        }

        return $regions;
    }

    public function persist(array $regions): array
    {
        return DB::transaction(function () use ($regions) {
            RegionalCluster::upsert(
                $regions,
                ['kota_kabupaten'],
                ['provinsi', 'cluster_id', 'cluster_label', 'avg_aging', 'out_sla_rate',
                    'total_shipment', 'verifiable_count', 'risk_index'],
            );

            $kept = collect($regions)->pluck('kota_kabupaten');
            RegionalCluster::whereNotIn('kota_kabupaten', $kept)->delete();

            $clusters = RegionalCluster::query()->orderBy('cluster_id')->get();

            $summary = $clusters
                ->groupBy('cluster_label')
                ->map(fn ($group) => [
                    'cluster_id' => $group->first()->cluster_id,
                    'label' => $group->first()->cluster_label,
                    'regions' => $group->count(),
                    'shipments' => $group->sum('total_shipment'),
                    'out_sla_rate' => round((float) $group->avg('out_sla_rate'), 1),
                    'avg_aging' => round((float) $group->avg('avg_aging'), 1),
                ])
                ->sortBy('cluster_id')
                ->values()
                ->all();

            $meta = [
                'run_at' => now()->toIso8601String(),
                'algo' => 'k-means-3-standard-scaler',
                'n_regions' => count($regions),
                'summary' => $summary,
            ];

            Cache::put(self::RESULT_CACHE_KEY, $meta);

            return $meta;
        });
    }

    protected function snippet(string $text, int $length = 500): string
    {
        $clean = trim($text);

        return mb_strlen($clean) > $length
            ? mb_substr($clean, 0, $length).'…'
            : ($clean !== '' ? $clean : '(tanpa pesan error)');
    }
}
