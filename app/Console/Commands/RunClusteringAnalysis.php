<?php

namespace App\Console\Commands;

use App\Services\ClusteringService;
use Illuminate\Console\Command;

class RunClusteringAnalysis extends Command
{
    protected $signature = 'analytics:run-clustering';

    protected $description = 'K-Means clustering kinerja pengiriman per kota/kabupaten';

    public function handle(ClusteringService $service): int
    {
        $this->info('Menulis observasi dan menjalankan Python (K-Means)...');

        try {
            $result = $service->run();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Selesai: %d wilayah dikelompokkan ke %d zona.',
            $result['regions'],
            count($result['meta']['summary']),
        ));

        foreach ($result['meta']['summary'] as $zone) {
            $this->line(sprintf(
                '  %-28s %3d wilayah · %6d kiriman · out-SLA %5.1f%% · aging %5.1f hari',
                $zone['label'],
                $zone['regions'],
                $zone['shipments'],
                $zone['out_sla_rate'],
                $zone['avg_aging'],
            ));
        }

        return self::SUCCESS;
    }
}
