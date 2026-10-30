<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\ShipmentSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request, ShipmentSyncService $service)
    {
        $range = (int) $request->query('range', 0);

        if (! in_array($range, [0, 30, 90], true)) {
            $range = 0;
        }

        $base = Shipment::query()->hasValidResi()->inRange($range);

        $requestedSlaMode = $request->query('sla_mode');
        // Default diset ke 'customer' (SLA Panthera berbasis tanggal)
        $slaMode = in_array($requestedSlaMode, ['vendor', 'customer'], true) ? $requestedSlaMode : 'customer';
        $slaModeFallback = false;

        $vendorSlaCovered = $base->clone()->slaVendorVerifiable()->count();
        $customerSlaCovered = $base->clone()->slaVerifiable()->count();

        if ($slaMode === 'vendor' && $vendorSlaCovered === 0 && $customerSlaCovered > 0) {
            $slaMode = 'customer';
            $slaModeFallback = true;
        }

        $totalShipment = $base->clone()->count();

        $completed = $base->clone()->statusCompleted()->count();

        $onDeliveryCount = $base->clone()->statusOnDelivery()->count();
        $undeliveredCount = $base->clone()->statusUndelivered()->count();
        $holdCount = $base->clone()->statusHold()->count();
        $returCount = $base->clone()->statusRetur()->count();

        if ($slaMode === 'vendor') {
            $withinSla = $base->clone()->meetSlaVendor()->count();
            $overSla = $base->clone()->outSlaVendor()->count();
            $slaCovered = $vendorSlaCovered;
            $outSlaActive = $base->clone()
                ->outSlaVendor()
                ->statusNotCompleted()
                ->count();
        } else {
            // Mode customer menghitung ulang dari tgl_delivered_panthera vs sla_due_date
            $withinSla = $base->clone()->meetSla()->count();
            $overSla = $base->clone()->outSla()->count();
            $slaCovered = $customerSlaCovered;
            $outSlaActive = $base->clone()
                ->outSla()
                ->statusNotCompleted()
                ->count();
        }

        $slaDenom = $withinSla + $overSla;
        $slaPct = $slaDenom > 0 ? round(($withinSla / $slaDenom) * 100) : null;
        $slaUnverified = $totalShipment - $slaCovered;
        $outSlaDone = $overSla - $outSlaActive;
        $slaModeLabel = $slaMode === 'vendor' ? 'Vendor/SARTrans' : 'End-to-End';

        $totalKoli = (int) $base->clone()->sum('koli');
        $koliRows = (int) $base->clone()->whereNotNull('koli')->count();

        $completionRate = $totalShipment > 0 ? round(($completed / $totalShipment) * 100) : 0;
        $undeliveredRate = $totalShipment > 0 ? round(($undeliveredCount / $totalShipment) * 100) : 0;

        $leadTime = $this->leadTimeStats($base->clone());

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

        $trend = $this->trendSeries($base, $range, $slaMode);

        $staggingOrder = [
            'BERANGKAT DARI CROSSDOCK',
            'TIBA DI PORT ORIGIN',
            'BERANGKAT DARI PORT ORIGIN',
            'TIBA DI PORT TRANSIT',
            'BERANGKAT DARI PORT TRANSIT',
            'MENUJU HUB DESTINASI',
            'TIBA DI HUB DESTINASI',
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
            ->when(
                $slaMode === 'vendor',
                fn ($q) => $q->outSlaVendor(),
                fn ($q) => $q->outSla()
            )
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

        $vendorLeadTimes = $this->leadTimeStatsByVendor($base->clone());

        $vendorDiff = Shipment::dateDiffExpression('tgl_delivered_panthera', 'tgl_ho_sartrans');
        $vendorRecognisedLabel = "UPPER(TRIM(COALESCE(sla_vendor_result, ''))) IN ('MEET SLA', 'OVER SLA', 'OUT SLA')";
        $vendorDaysUsable = 'sla_vendor_days IS NOT NULL AND tgl_ho_sartrans <= tgl_delivered_panthera';
        $vendorVerifiable = 'tgl_delivered_panthera IS NOT NULL AND tgl_ho_sartrans IS NOT NULL '
            ."AND ({$vendorDaysUsable} OR {$vendorRecognisedLabel})";
        $vendorMeet = "({$vendorDaysUsable} AND {$vendorDiff} <= sla_vendor_days) "
            .'OR ((sla_vendor_days IS NULL OR tgl_ho_sartrans > tgl_delivered_panthera) '
            ."AND UPPER(TRIM(COALESCE(sla_vendor_result, ''))) = 'MEET SLA')";

        $vendorCoveredSql = $slaMode === 'vendor'
            ? "SUM(CASE WHEN {$vendorVerifiable} THEN 1 ELSE 0 END) as sla_covered"
            : 'SUM(CASE WHEN tgl_delivered_panthera IS NOT NULL AND sla_due_date IS NOT NULL THEN 1 ELSE 0 END) as sla_covered';
        $vendorMeetSql = $slaMode === 'vendor'
            ? "SUM(CASE WHEN ({$vendorVerifiable}) AND ({$vendorMeet}) THEN 1 ELSE 0 END) as meet"
            : 'SUM(CASE WHEN tgl_delivered_panthera IS NOT NULL AND sla_due_date IS NOT NULL AND tgl_delivered_panthera <= sla_due_date THEN 1 ELSE 0 END) as meet';

        $vendorStats = $base->clone()
            ->whereNotNull('vendor_lm')
            ->where('vendor_lm', '!=', '')
            ->selectRaw('vendor_lm')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw($vendorCoveredSql)
            ->selectRaw($vendorMeetSql)
            ->groupBy('vendor_lm')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($r) use ($vendorLeadTimes) {
                $slaCovered = (int) $r->sla_covered;
                $meet = (int) $r->meet;

                return [
                    'vendor' => $r->vendor_lm,
                    'total' => (int) $r->total,
                    'slaCovered' => $slaCovered,
                    'onTimePct' => $slaCovered > 0 ? round(($meet / $slaCovered) * 100) : null,
                    'medianLead' => $vendorLeadTimes[$r->vendor_lm]['median'] ?? null,
                    'p90Lead' => $vendorLeadTimes[$r->vendor_lm]['p90'] ?? null,
                    'leadSample' => $vendorLeadTimes[$r->vendor_lm]['sample'] ?? 0,
                ];
            });

        $latestRawDate = Shipment::query()->withConsistentDates()->max('tanggal_manifest');
        $earliestRawDate = Shipment::query()->withConsistentDates()->min('tanggal_manifest');

        $latestManifest = $latestRawDate ? Carbon::parse($latestRawDate) : null;
        $earliestManifest = $earliestRawDate ? Carbon::parse($earliestRawDate) : null;

        $recentShipments = $base->clone()
            ->withConsistentDates()
            ->orderByDesc('tanggal_manifest')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $rangeLatestRaw = $base->clone()->withConsistentDates()->max('tgl_delivered_panthera');
        $agingAnchor = $rangeLatestRaw ? Carbon::parse($rangeLatestRaw)->toDateString() : Carbon::today()->toDateString();

        $agingBuckets = collect([
            '≤ 14 hari' => 0,
            '15–30 hari' => 0,
            '31–60 hari' => 0,
            '> 60 hari' => 0,
        ]);

        $agingRows = $base->clone()
            ->withConsistentDates()
            ->statusActive()
            ->whereNotNull('tanggal_manifest')
            ->selectRaw('CASE
                WHEN DATEDIFF(DATE("'.$agingAnchor.'"), DATE(tanggal_manifest)) <= 14 THEN "≤ 14 hari"
                WHEN DATEDIFF(DATE("'.$agingAnchor.'"), DATE(tanggal_manifest)) <= 30 THEN "15–30 hari"
                WHEN DATEDIFF(DATE("'.$agingAnchor.'"), DATE(tanggal_manifest)) <= 60 THEN "31–60 hari"
                ELSE "> 60 hari"
              END as bucket')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        foreach ($agingRows as $bucket => $total) {
            if ($agingBuckets->has($bucket)) {
                $agingBuckets[$bucket] = (int) $total;
            }
        }

        $agingExcluded = $base->clone()
            ->withConsistentDates()
            ->statusActive()
            ->whereNull('tanggal_manifest')
            ->count();

        $attentionShipments = $base->clone()
            ->withConsistentDates()
            ->statusActive()
            ->selectRaw('*, DATEDIFF(DATE("'.$agingAnchor.'"), DATE(tanggal_manifest)) as days_open')
            ->where(function ($q) use ($agingAnchor) {
                $q->where(function ($q2) {
                    $q2->statusHold()->orWhere(fn ($q3) => $q3->statusUndelivered());
                })->orWhere(function ($q2) use ($agingAnchor) {
                    $q2->whereNotNull('tanggal_manifest')
                        ->whereRaw('DATEDIFF(DATE("'.$agingAnchor.'"), DATE(tanggal_manifest)) >= 14');
                });
            })
            ->orderByRaw('CASE WHEN LOWER(TRIM(status_akhir)) IN ("hold", "pending", "ditahan", "undelivered", "gagal kirim", "gagal", "retur") THEN 0 ELSE 1 END')
            ->orderByRaw('COALESCE(days_open, 0) DESC')
            ->orderBy('id', 'desc')
            ->limit(25)
            ->get();

        $lastSync = $service->lastSyncedAt();
        $sourceStats = $service->sourceStats();
        $dbTotal = Shipment::count();
        $validDbTotal = Shipment::query()->hasValidResi()->count();
        $invalidTotal = $dbTotal - $validDbTotal;

        return view('dashboard', compact(
            'range',
            'slaMode',
            'slaModeLabel',
            'slaModeFallback',
            'invalidTotal',
            'validDbTotal',
            'totalShipment',
            'completed',
            'onDeliveryCount',
            'undeliveredCount',
            'holdCount',
            'returCount',
            'withinSla',
            'overSla',
            'slaPct',
            'slaCovered',
            'slaUnverified',
            'outSlaActive',
            'outSlaDone',
            'totalKoli',
            'koliRows',
            'completionRate',
            'undeliveredRate',
            'leadTime',
            'statusBreakdown',
            'bastBalikStats',
            'bastFinanceStats',
            'trend',
            'staggingList',
            'bottleneckStats',
            'topProvinces',
            'topVendors',
            'vendorStats',
            'latestManifest',
            'earliestManifest',
            'recentShipments',
            'agingBuckets',
            'agingExcluded',
            'attentionShipments',
            'lastSync',
            'sourceStats',
            'dbTotal',
        ));
    }

    protected function trendSeries($base, int $range, string $slaMode): array
    {
        $windowStart = null;

        if ($range > 0) {
            $anchor = $base->clone()->withConsistentDates()->max('tgl_delivered_panthera');

            if ($anchor) {
                $windowStart = Carbon::parse($anchor)->subDays($range - 1)->startOfDay();
            }
        }

        $volumeKirimDaily = $base->clone()
            ->withConsistentDates()
            ->whereNotNull('tanggal_manifest')
            ->when($windowStart, fn ($q) => $q->where('tanggal_manifest', '>=', $windowStart))
            ->selectRaw('DATE(tanggal_manifest) as tanggal')
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw('DATE(tanggal_manifest)')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $volumeSelesaiDaily = $base->clone()
            ->withConsistentDates()
            ->whereNotNull('tgl_delivered_panthera')
            ->statusCompleted()
            ->when($windowStart, fn ($q) => $q->where('tgl_delivered_panthera', '>=', $windowStart))
            ->selectRaw('DATE(tgl_delivered_panthera) as tanggal')
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw('DATE(tgl_delivered_panthera)')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $outSlaDaily = $base->clone()
            ->withConsistentDates()
            ->whereNotNull('tgl_delivered_panthera')
            ->when(
                $slaMode === 'vendor',
                fn ($q) => $q->outSlaVendor(),
                fn ($q) => $q->outSla()
            )
            ->when($windowStart, fn ($q) => $q->where('tgl_delivered_panthera', '>=', $windowStart))
            ->selectRaw('DATE(tgl_delivered_panthera) as tanggal')
            ->selectRaw('COUNT(*) as volume_out')
            ->groupByRaw('DATE(tgl_delivered_panthera)')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $vendorDiff = Shipment::dateDiffExpression('tgl_delivered_panthera', 'tgl_ho_sartrans');
        $vendorDaysUsable = 'sla_vendor_days IS NOT NULL AND tgl_ho_sartrans <= tgl_delivered_panthera';
        $vendorMeet = "({$vendorDaysUsable} AND {$vendorDiff} <= sla_vendor_days) "
            .'OR ((sla_vendor_days IS NULL OR tgl_ho_sartrans > tgl_delivered_panthera) '
            ."AND UPPER(TRIM(COALESCE(sla_vendor_result, ''))) = 'MEET SLA')";

        $slaComplianceDaily = $base->clone()
            ->withConsistentDates()
            ->whereNotNull('tgl_delivered_panthera')
            ->when(
                $slaMode === 'vendor',
                fn ($q) => $q->slaVendorVerifiable(),
                fn ($q) => $q->slaVerifiable()
            )
            ->when($windowStart, fn ($q) => $q->where('tgl_delivered_panthera', '>=', $windowStart))
            ->selectRaw('DATE(tgl_delivered_panthera) as tanggal')
            ->selectRaw($slaMode === 'vendor'
                ? "SUM(CASE WHEN {$vendorMeet} THEN 1 ELSE 0 END) as meet"
                : 'SUM(CASE WHEN tgl_delivered_panthera <= sla_due_date THEN 1 ELSE 0 END) as meet')
            ->selectRaw('COUNT(*) as total')
            ->groupByRaw('DATE(tgl_delivered_panthera)')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $trendDates = collect()
            ->merge($volumeKirimDaily->keys())
            ->merge($volumeSelesaiDaily->keys())
            ->merge($outSlaDaily->keys())
            ->merge($slaComplianceDaily->keys())
            ->unique()
            ->sort()
            ->values();

        $axis = collect();

        if ($trendDates->isNotEmpty()) {
            $first = Carbon::parse($trendDates->first())->startOfDay();
            $last = Carbon::parse($trendDates->last())->startOfDay();

            if ($first->diffInDays($last) <= 366) {
                for ($cursor = $first->copy(); $cursor->lte($last); $cursor->addDay()) {
                    $axis->push($cursor->toDateString());
                }
            } else {
                $axis = $trendDates;
            }
        }

        return [
            'labels' => $axis->map(fn ($date) => Carbon::parse($date)->format($range === 0 ? 'd M y' : 'd M'))->values()->all(),
            'kirim' => $axis->map(fn ($date) => (int) ($volumeKirimDaily[$date]->total ?? 0))->values()->all(),
            'selesai' => $axis->map(fn ($date) => (int) ($volumeSelesaiDaily[$date]->total ?? 0))->values()->all(),
            'outSla' => $axis->map(fn ($date) => (int) ($outSlaDaily[$date]->volume_out ?? 0))->values()->all(),
            'compliance' => $axis->map(function ($date) use ($slaComplianceDaily) {
                $row = $slaComplianceDaily[$date] ?? null;

                if (! $row || (int) $row->total <= 0) {
                    return null;
                }

                return (int) round(((int) $row->meet / (int) $row->total) * 100);
            })->values()->all(),
        ];
    }

    protected function leadTimeStats($query): array
    {
        return $this->statsFromValues($this->leadTimeValues($query));
    }

    protected function leadTimeStatsByVendor($query): array
    {
        $values = $this->leadTimeValues($query, 'vendor_lm');

        $out = [];

        foreach ($values as $vendor => $vendorValues) {
            $out[$vendor] = $this->statsFromValues($vendorValues);
        }

        return $out;
    }

    protected function statsFromValues($sorted): array
    {
        if ($sorted->isEmpty()) {
            return ['median' => null, 'p90' => null, 'mean' => null, 'sample' => 0];
        }

        return [
            'median' => $this->percentile($sorted, 50),
            'p90' => $this->percentile($sorted, 90),
            'mean' => round((float) $sorted->avg(), 1),
            'sample' => $sorted->count(),
        ];
    }

    protected function leadTimeValues($query, ?string $groupBy = null)
    {
        $start = Shipment::endToEndStartExpression();
        $diff = Shipment::dateDiffExpression('tgl_delivered_panthera', $start);

        $rows = $query
            ->withConsistentDates()
            ->whereNotNull('tgl_delivered_panthera')
            ->whereRaw($start.' IS NOT NULL')
            ->statusCompleted()
            ->selectRaw($diff.' as d'.($groupBy ? ', '.$groupBy : ''))
            ->get();

        $clean = fn ($row) => (int) $row->d;

        if ($groupBy === null) {
            return $rows->map($clean)->filter(fn ($v) => $v >= 0)->sort()->values();
        }

        return $rows
            ->groupBy($groupBy)
            ->map(fn ($group) => $group->map($clean)->filter(fn ($v) => $v >= 0)->sort()->values());
    }

    protected function percentile($sorted, int $percentile): float
    {
        $count = $sorted->count();

        if ($count === 0) {
            return 0.0;
        }

        return (float) $sorted[(int) floor($percentile / 100 * ($count - 1))];
    }

    protected function mapBastKeterangan(string $status): string
    {
        $normalized = mb_strtoupper(trim($status));

        return match (true) {
            str_contains($normalized, 'SUDAH DITERIMA'), str_contains($normalized, 'BAST DITERIMA') => 'Sudah Diterima',
            str_contains($normalized, 'BELUM DITERIMA') => 'Belum Diterima',
            str_contains($normalized, 'BELUM TERDATA') => 'Belum Terdata',
            str_contains($normalized, 'TIDAK BEROPERASI') => 'Sekolah Tidak Beroperasi',
            str_contains($normalized, 'RELOKASI') => 'Relokasi',
            default => 'Lainnya',
        };
    }
}
