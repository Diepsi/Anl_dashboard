<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\ShipmentSyncService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ShipmentSyncService $service)
    {
        $range = (int) $request->query('range', 0);

        if (! in_array($range, [0, 30, 90], true)) {
            $range = 0;
        }

        $base = Shipment::query()->inRange($range);

        $activeStatuses = ['On Process', 'On Process Delivery', 'Dikirim', 'On Delivery', 'Undelivered', 'Hold'];

        $totalShipment = $base->clone()->count();

        $totalShipment = $base->clone()->count();

        $completed = $base->clone()
            ->where('status_akhir', 'Completed')
            ->count();

        $onDeliveryCount = $base->clone()->where('status_akhir', 'On Delivery')->count();
        $undeliveredCount = $base->clone()->where('status_akhir', 'Undelivered')->count();
        $holdCount = $base->clone()->where('status_akhir', 'Hold')->count();

        $withinSla = $base->clone()
            ->where('sla_result', 'Meet SLA')
            ->count();

        $overSla = $base->clone()
            ->where('sla_result', 'Out SLA')
            ->count();

        $slaDenom = $withinSla + $overSla;
        $slaPct = $slaDenom > 0 ? round(($withinSla / $slaDenom) * 100) : 0;

        $outSlaActive = $base->clone()
            ->where('sla_result', 'Out SLA')
            ->where('status_akhir', '!=', 'Completed')
            ->count();

        $outSlaDone = $overSla - $outSlaActive;

        $statusBreakdown = $base->clone()
            ->selectRaw('status_akhir, COUNT(*) as total')
            ->groupBy('status_akhir')
            ->orderByDesc('total')
            ->pluck('total', 'status_akhir');

        $bastBalikStats = $base->clone()
            ->selectRaw('COALESCE(NULLIF(TRIM(bast_keterangan), ""), "Belum Terdata") as status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(fn ($row) => [$this->mapBastKeterangan($row->status) => (int) $row->total]);

        $bastFinanceCount = $base->clone()
            ->whereNotNull('bast_tgl_ke_finance')
            ->count();

        $bastFinanceStats = collect([
            'Sudah handover' => $bastFinanceCount,
            'Belum' => $totalShipment - $bastFinanceCount,
        ])->filter(fn ($total) => $total > 0);

        $chartData = $base->clone()
            ->selectRaw('DATE(tanggal_manifest) as tanggal')
            ->selectRaw('COUNT(*) as volume_kirim')
            ->selectRaw('SUM(CASE WHEN status_akhir = "Completed" THEN 1 ELSE 0 END) as volume_selesai')
            ->groupByRaw('DATE(tanggal_manifest)')
            ->orderBy('tanggal')
            ->get();

        $outSlaDaily = $base->clone()
            ->selectRaw('DATE(tanggal_manifest) as tanggal')
            ->selectRaw('COUNT(*) as volume_out')
            ->where('sla_result', 'Out SLA')
            ->groupByRaw('DATE(tanggal_manifest)')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $staggingOrder = [
            'BERANGKAT DARI CROSSDOCK',
            'TIBA DI PORT ORIGIN',
            'BERANGKAT DARI PORT ORIGIN',
            'TIBA DI PORT TRANSIT',
            'BERANGKAT DARI PORT TRANSIT',
            'MENUJU HUB DESTINASI',
            'TIBA DI PORT DESTINASI',
            'PROCESS DOORING',
        ];

        $staggingStats = $base->clone()
            ->selectRaw('stagging, COUNT(*) as total')
            ->whereNotNull('stagging')
            ->groupBy('stagging')
            ->orderByDesc('total')
            ->get()
            ->keyBy('stagging');

        $staggingList = collect($staggingOrder)
            ->map(fn ($name) => [
                'name' => $name,
                'total' => (int) ($staggingStats[$name]->total ?? 0),
            ])
            ->filter(fn ($stage) => $stage['total'] > 0);

        $bottleneckStats = $base->clone()
            ->selectRaw('provinsi, COUNT(*) as total')
            ->where('sla_result', 'Out SLA')
            ->whereNotNull('provinsi')
            ->groupBy('provinsi')
            ->orderByDesc('total')
            ->get();

        $topProvinces = $base->clone()
            ->selectRaw('provinsi, COUNT(*) as total')
            ->whereNotNull('provinsi')
            ->where('provinsi', '!=', '')
            ->groupBy('provinsi')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'provinsi');

        $topVendorsAll = $base->clone()
            ->groupBy('vendor_lm')
            ->selectRaw('COALESCE(NULLIF(vendor_lm, ""), "Belum diisi") as vendor, COUNT(*) as total')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'vendor');

        $topVendors = $topVendorsAll->take(5);
        $otherVendorCount = $topVendorsAll->slice(5)->sum();

        if ($otherVendorCount > 0) {
            $topVendors->put('Lainnya', $otherVendorCount);
        }

        $latestManifest = \Illuminate\Support\Carbon::parse(Shipment::query()->max('tanggal_manifest'));
        $earliestManifest = \Illuminate\Support\Carbon::parse(Shipment::query()->min('tanggal_manifest'));

        $recentShipments = $base->clone()
            ->orderByDesc('tanggal_manifest')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $agingBuckets = collect([
            '≤ 7 hari' => 0,
            '8–14 hari' => 0,
            '15–30 hari' => 0,
            '> 30 hari' => 0,
        ]);

        $agingRows = $base->clone()
            ->whereIn('status_akhir', $activeStatuses)
            ->whereNotNull('tanggal_manifest')
            ->selectRaw('CASE
                WHEN DATEDIFF(CURDATE(), DATE(tanggal_manifest)) <= 7 THEN "≤ 7 hari"
                WHEN DATEDIFF(CURDATE(), DATE(tanggal_manifest)) <= 14 THEN "8–14 hari"
                WHEN DATEDIFF(CURDATE(), DATE(tanggal_manifest)) <= 30 THEN "15–30 hari"
                ELSE "> 30 hari"
              END as bucket')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        foreach ($agingRows as $bucket => $total) {
            if ($agingBuckets->has($bucket)) {
                $agingBuckets[$bucket] = (int) $total;
            }
        }

        $attentionShipments = $base->clone()
            ->whereIn('status_akhir', $activeStatuses)
            ->selectRaw('*, DATEDIFF(CURDATE(), DATE(tanggal_manifest)) as days_open')
            ->where(function ($q) {
                $q->whereIn('status_akhir', ['Hold', 'Undelivered'])
                    ->orWhereRaw('DATEDIFF(CURDATE(), DATE(tanggal_manifest)) >= 14');
            })
            ->orderByRaw('COALESCE(days_open, 0) DESC')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $lastSync = $service->lastSyncedAt();
        $sourceStats = $service->sourceStats();
        $dbTotal = Shipment::count();

        return view('dashboard', compact(
            'range',
            'totalShipment',
            'completed',
            'onDeliveryCount',
            'undeliveredCount',
            'holdCount',
            'withinSla',
            'overSla',
            'slaPct',
            'outSlaActive',
            'outSlaDone',
            'statusBreakdown',
            'bastBalikStats',
            'bastFinanceStats',
            'chartData',
            'outSlaDaily',
            'staggingList',
            'bottleneckStats',
            'topProvinces',
            'topVendors',
            'latestManifest',
            'earliestManifest',
            'recentShipments',
            'agingBuckets',
            'attentionShipments',
            'lastSync',
            'sourceStats',
            'dbTotal',
        ));
    }

    protected function mapBastKeterangan(string $status): string
    {
        return match ($status) {
            'ACTUAL BAST SUDAH DITERIMA' => 'Sudah Diterima',
            'BAST BELUM DITERIMA' => 'Belum Diterima',
            'SEKOLAH TIDAK BEROPERASI LAGI' => 'Sekolah Tidak Beroperasi',
            'RELOKASI' => 'Relokasi',
            default => 'Belum Terdata',
        };
    }
}