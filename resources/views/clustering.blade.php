<x-app-layout>

<div class="p-4 sm:p-6 lg:p-8 space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Analitik Clustering Performance Wilayah</h1>
        <p class="mt-1 text-sm text-slate-500">
            K-Means (k=3) per Kabupaten/Kota · rata-rata durasi, rasio keterlambatan, dan volume
            @if ($clusteringMeta['run_at'])
                · dihitung {{ \Illuminate\Support\Carbon::parse($clusteringMeta['run_at'])->format('d M Y H:i') }}
                · {{ $clusteringMeta['n_regions'] }} wilayah
            @endif
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden stagger-ready">
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-5 border-b border-slate-200">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Segmentasi Wilayah</h2>
                <p class="text-sm text-slate-500">
                    Pengelompokan kabupaten/kota berdasarkan performa pengiriman
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                sumber: seluruh data hasil sync terakhir
            </span>
        </div>

        @if ($clusterSummary->isEmpty())
            <div class="px-6 py-10 text-center text-slate-400">
                Clustering belum dijalankan. Jalankan
                <code class="font-mono text-slate-600">php artisan analytics:run-clustering</code>
                dari terminal untuk mengisi analitik ini.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-6">
                @foreach ($clusterSummary as $zone)
                    <div class="rounded-xl border border-slate-200 p-5" style="border-top: 4px solid {{ $zone['color'] }}">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-700">{{ $zone['label'] }}</p>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold" style="background: {{ $zone['color'] }}1a; color: {{ $zone['color'] }}">
                                {{ $zone['regions'] }} kab/kota
                            </span>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                            <div>
                                <p class="text-lg font-bold text-slate-900">{{ number_format($zone['shipments'], 0, ',', '.') }}</p>
                                <p class="text-[11px] text-slate-500">Kiriman</p>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-slate-900">{{ $zone['out_sla_rate'] }}%</p>
                                <p class="text-[11px] text-slate-500">Out-SLA</p>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-slate-900">{{ $zone['avg_aging'] }}</p>
                                <p class="text-[11px] text-slate-500">Aging (hari)</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 px-6 pb-6">
                <div class="lg:col-span-3">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <h3 class="text-sm font-semibold text-slate-700 mb-2">Sebaran Wilayah (Out-SLA vs Durasi)</h3>
                        <div class="h-72"><canvas id="clusterChart"></canvas></div>
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <h3 class="text-sm font-semibold text-slate-700 mb-2">5 Wilayah Risiko Keterlambatan Tertinggi</h3>
                        <ol class="divide-y divide-slate-100">
                            @forelse ($clusterTopRisk as $i => $region)
                                <li class="py-2.5 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-800 truncate">
                                            {{ $i + 1 }}. {{ $region->kota_kabupaten }}
                                        </p>
                                        <p class="text-[11px] text-slate-500">
                                            {{ $region->provinsi ?? '—' }} · {{ number_format($region->total_shipment, 0, ',', '.') }} kiriman
                                        </p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold"
                                              style="background: #dc26261a; color: #dc2626">
                                            {{ $region->out_sla_rate }}% out-SLA
                                        </span>
                                    </div>
                                </li>
                            @empty
                                <li class="py-6 text-center text-sm text-slate-400">Belum ada data.</li>
                            @endforelse
                        </ol>
                    </div>
                </div>
            </div>
        @endif
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const renderChart = (id, config) => {
        const el = document.getElementById(id);
        if (!el || typeof Chart === 'undefined') return;
        new Chart(el.getContext('2d'), config);
    };

    const clusterPoints = @json($clusterChart);
    const clusterLegend = @json($clusterLabels);
    if (clusterPoints.length && clusterLegend.length) {
        const clusterDatasets = clusterLegend.map(zone => ({
            label: zone.label,
            data: clusterPoints.filter(p => p.cluster === zone.cluster_id),
            backgroundColor: zone.color + 'cc',
            borderColor: zone.color,
            borderWidth: 1,
        }));

        renderChart('clusterChart', {
            type: 'scatter',
            data: { datasets: clusterDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.raw.label}: ${ctx.raw.x}% out-SLA, ${ctx.raw.y} hari, ${ctx.raw.volume} kiriman`,
                        },
                    },
                },
                scales: {
                    x: { title: { display: true, text: 'Out SLA Rate (%)' }, beginAtZero: true },
                    y: { title: { display: true, text: 'Rata-rata Durasi (hari)' }, beginAtZero: true },
                },
            },
        });
    }
});
</script>
@endpush

</div>

</x-app-layout>
