<?php

namespace App\Http\Controllers;

use App\Models\RegionalCluster;
use App\Services\ClusteringService;
use Illuminate\Support\Facades\Cache;

class ClusteringController extends Controller
{
    private const CLUSTER_COLORS = [
        0 => '#dc2626', // High Risk / Bottleneck Zone
        1 => '#2563eb', // High Volume Zone
        2 => '#16a34a', // Standard / Low Risk Zone
    ];

    public function index()
    {
        $regionalClusters = RegionalCluster::query()->orderBy('cluster_id')->get();

        $clusterSummary = $regionalClusters
            ->groupBy('cluster_id')
            ->map(fn ($group) => [
                'cluster_id' => $group->first()->cluster_id,
                'label' => $group->first()->cluster_label,
                'regions' => $group->count(),
                'shipments' => $group->sum('total_shipment'),
                'out_sla_rate' => round((float) $group->avg('out_sla_rate'), 1),
                'avg_aging' => round((float) $group->avg('avg_aging'), 1),
                'color' => self::CLUSTER_COLORS[$group->first()->cluster_id],
            ])
            ->sortKeys()
            ->values();

        $clusterLabels = $regionalClusters
            ->groupBy('cluster_id')
            ->map(fn ($group) => [
                'cluster_id' => $group->first()->cluster_id,
                'label' => $group->first()->cluster_label,
                'color' => self::CLUSTER_COLORS[$group->first()->cluster_id],
            ])
            ->sortKeys()
            ->values();

        $clusterTopRisk = $regionalClusters
            ->sortByDesc('risk_index')
            ->take(5)
            ->values();

        $clusterChart = $regionalClusters
            ->map(fn ($row) => [
                'label' => $row->kota_kabupaten,
                'cluster' => $row->cluster_id,
                'x' => round((float) $row->out_sla_rate, 1),
                'y' => round((float) $row->avg_aging, 1),
                'r' => max(4, min(22, (int) round(sqrt($row->total_shipment) * 1.6))),
                'volume' => $row->total_shipment,
            ])
            ->values();

        $clusteringMeta = Cache::get(
            ClusteringService::RESULT_CACHE_KEY,
            ['run_at' => null, 'summary' => [], 'n_regions' => 0],
        );

        return view('clustering', compact(
            'clusterSummary',
            'clusterLabels',
            'clusterTopRisk',
            'clusterChart',
            'clusteringMeta',
        ));
    }
}
