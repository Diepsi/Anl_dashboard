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

        $base = Shipment::query()->inRange($range);

        $activeStatuses = ['On Process', 'On Process Delivery', 'Dikirim', 'On Delivery', 'Undelivered', 'Hold', 'Retur'];

        $totalShipment = $base->clone()->count();

        $completed = $base->clone()
            ->where('status_akhir', 'Completed')
            ->count();

        $onDeliveryCount = $base->clone()->where('status_akhir', 'On Delivery')->count();
        $undeliveredCount = $base->clone()->where('status_akhir', 'Undelivered')->count();
        $holdCount = $base->clone()->where('status_akhir', 'Hold')->count();
        $returCount = $base->clone()->where('status_akhir', 'Retur')->count();

        // Verdict SLA dihitung ulang dari completed_date vs sla_due_date, bukan
        // dari teks verdict di sheet. Kolom SLA di sumber berisi dua bentuk (ambang
        // hari untuk BOMA, tanggal batas untuk vendor lain) dan keduanya sudah
        // disatukan menjadi sla_due_date saat sync, jadi satu scope ini berlaku
        // untuk semua vendor. Cakupannya naik dari 10,6% menjadi 99,7%.
        $withinSla = $base->clone()->meetSla()->count();

        $overSla = $base->clone()->outSla()->count();

        $slaCovered = $base->clone()->slaVerifiable()->count();

        $slaDenom = $withinSla + $overSla;
        $slaPct = $slaDenom > 0 ? round(($withinSla / $slaDenom) * 100) : null;
        $slaUnverified = $totalShipment - $slaCovered;

        $outSlaActive = $base->clone()
            ->outSla()
            ->where('status_akhir', '!=', 'Completed')
            ->count();

        $outSlaDone = $overSla - $outSlaActive;

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

        $chartData = $base->clone()
            ->withConsistentDates()
            ->whereNotNull('tanggal_manifest')
            ->selectRaw('DATE(tanggal_manifest) as tanggal')
            ->selectRaw('COUNT(*) as volume_kirim')
            ->selectRaw('SUM(CASE WHEN status_akhir = "Completed" THEN 1 ELSE 0 END) as volume_selesai')
            ->groupByRaw('DATE(tanggal_manifest)')
            ->orderBy('tanggal')
            ->get();

        $outSlaDaily = $base->clone()
            ->withConsistentDates()
            ->whereNotNull('tanggal_manifest')
            ->outSla()
            ->selectRaw('DATE(tanggal_manifest) as tanggal')
            ->selectRaw('COUNT(*) as volume_out')
            ->groupByRaw('DATE(tanggal_manifest)')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $slaComplianceDaily = $base->clone()
            ->withConsistentDates()
            ->whereNotNull('tanggal_manifest')
            ->slaVerifiable()
            ->selectRaw('DATE(tanggal_manifest) as tanggal')
            ->selectRaw('SUM(CASE WHEN completed_date <= sla_due_date THEN 1 ELSE 0 END) as meet')
            ->selectRaw('COUNT(*) as total')
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
            ->outSla()
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

        $vendorStats = $base->clone()
            ->whereNotNull('vendor_lm')
            ->where('vendor_lm', '!=', '')
            ->selectRaw('vendor_lm')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN sla_due_date IS NOT NULL AND completed_date IS NOT NULL THEN 1 ELSE 0 END) as sla_covered')
            ->selectRaw('SUM(CASE WHEN sla_due_date IS NOT NULL AND completed_date IS NOT NULL AND completed_date <= sla_due_date THEN 1 ELSE 0 END) as meet')
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

        // Anchor memakai tanggal selesai terakhir yang konsisten (bukan MAX(tanggal_manifest),
        // yang bisa didominasi baris korup). Tanpa ini semua kiriman aktif jatuh ke bucket
        // usia tertua karena tertaut ke spike tanggal yang tidak logis.
        $rangeLatestRaw = $base->clone()->withConsistentDates()->max('completed_date');
        $agingAnchor = $rangeLatestRaw ? Carbon::parse($rangeLatestRaw)->toDateString() : Carbon::today()->toDateString();

        $agingBuckets = collect([
            '≤ 14 hari' => 0,
            '15–30 hari' => 0,
            '31–60 hari' => 0,
            '> 60 hari' => 0,
        ]);

        $agingRows = $base->clone()
            ->withConsistentDates()
            ->whereIn('status_akhir', $activeStatuses)
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
            ->whereIn('status_akhir', $activeStatuses)
            ->whereNull('tanggal_manifest')
            ->count();

        $attentionShipments = $base->clone()
            ->withConsistentDates()
            ->whereIn('status_akhir', $activeStatuses)
            ->selectRaw('*, DATEDIFF(DATE("'.$agingAnchor.'"), DATE(tanggal_manifest)) as days_open')
            ->where(function ($q) use ($agingAnchor) {
                // Status bermasalah masuk tanpa syarat tanggal: Retur (6 resi) tidak punya
                // tanggal HO ke Vendor sama sekali, jadi REQUIRE tanggal di luar cabang
                // ini akan membuangnya diam-diam. Ambang usia hanya berlaku untuk baris
                // yang memang punya tanggal, dan sisanya tampil dengan usia "—".
                //
                // Baris status-bermasalah didahulukan: kalau hanya diurutkan usia, Retur
                // punya days_open NULL dan akan selalu tersingkir paling akhir oleh
                // On Delivery yang sudah tua — persis baris yang tidak boleh hilang.
                $q->whereIn('status_akhir', ['Hold', 'Undelivered', 'Retur'])
                    ->orWhere(function ($q2) use ($agingAnchor) {
                        $q2->whereNotNull('tanggal_manifest')
                            ->whereRaw('DATEDIFF(DATE("'.$agingAnchor.'"), DATE(tanggal_manifest)) >= 14');
                    });
            })
            ->orderByRaw('CASE WHEN status_akhir IN ("Hold", "Undelivered", "Retur") THEN 0 ELSE 1 END')
            ->orderByRaw('COALESCE(days_open, 0) DESC')
            ->orderBy('id', 'desc')
            ->limit(25)
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
            'chartData',
            'outSlaDaily',
            'slaComplianceDaily',
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

    /**
     * Delivery-duration statistics for completed shipments.
     *
     * Uses median and p90 instead of the mean: a single batch of source rows whose
     * tanggal_manifest is later than completed_date was enough to drag the average
     * to a negative number, while the median stayed at a believable 13 days.
     */
    protected function leadTimeStats($query): array
    {
        return $this->statsFromValues($this->leadTimeValues($query));
    }

    /** Same metric grouped per vendor, so vendors stay comparable. */
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

    /**
     * Duration in days between manifest and completion.
     *
     * Rows with an impossible date order are dropped instead of being clamped, so a
     * corrupt source row can never distort the metric. Returned sorted; when $groupBy
     * is given the result stays keyed by that column.
     */
    protected function leadTimeValues($query, ?string $groupBy = null)
    {
        $rows = $query
            ->withConsistentDates()
            ->whereNotNull('completed_date')
            ->whereNotNull('tanggal_manifest')
            ->where('status_akhir', 'Completed')
            ->selectRaw('DATEDIFF(completed_date, tanggal_manifest) as d'.($groupBy ? ', '.$groupBy : ''))
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
