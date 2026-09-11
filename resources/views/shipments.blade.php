<x-app-layout>

<div class="p-4 sm:p-6 lg:p-8 space-y-6" x-data="detailController()">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Data Pengiriman</h1>
            <p class="mt-1 text-sm text-slate-500">Seluruh resi pengiriman — cari, filter, dan pantau status tiap shipment.</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Export Excel --}}
            <form method="POST" action="{{ route('shipments.export') }}">
                @csrf
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="provinsi" value="{{ $provinsi }}">
                <input type="hidden" name="stagging" value="{{ $stagging }}">
                <input type="hidden" name="sla" value="{{ $sla }}">
                <input type="hidden" name="search" value="{{ $search }}">
                <button type="submit"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-semibold text-white bg-cyan-600 border border-cyan-700 hover:bg-cyan-700 transition">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export Excel
                </button>
            </form>

            @if (auth()->user()?->isAdmin())
            <form method="POST" action="{{ route('sync') }}">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-semibold text-cyan-700 bg-cyan-50 border border-cyan-200 hover:bg-cyan-100 transition">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Sync Sekarang
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <form method="GET" action="{{ route('shipments') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3">
            <div class="xl:col-span-2">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Cari Resi / Sekolah / Penerima</label>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Ketik no resi atau nama sekolah..."
                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/40">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Status Akhir</label>
                <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                    <option value="">Semua</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Provinsi</label>
                <select name="provinsi" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                    <option value="">Semua</option>
                    @foreach ($provinces as $p)
                        <option value="{{ $p }}" @selected($provinsi === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Stagging</label>
                <select name="stagging" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                    <option value="">Semua</option>
                    @foreach ($staggingList as $sg)
                        <option value="{{ $sg }}" @selected($stagging === $sg)>{{ $sg }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">SLA Result</label>
                <select name="sla" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                    <option value="">Semua</option>
                    <option value="meet" @selected($sla === 'meet')>Meet SLA</option>
                    <option value="out" @selected($sla === 'out')>Out SLA</option>
                </select>
            </div>
            <div class="xl:col-span-6 flex items-center gap-2 pt-0">
                <button type="submit"
                    class="px-5 py-2 rounded-lg bg-cyan-600 text-white text-sm font-semibold hover:bg-cyan-700 transition">Terapkan Filter</button>
                <a href="{{ route('shipments') }}"
                    class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">Reset</a>
            </div>
        </form>
    </div>

    @if (session('status'))
        <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900">Daftar Shipment</h2>
            <p class="text-sm text-slate-500">{{ number_format($shipments->total(), 0, ',', '.') }} resi</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="px-6 py-3 font-semibold">No Resi</th>
                        <th class="px-6 py-3 font-semibold">Sekolah</th>
                        <th class="px-6 py-3 font-semibold">Provinsi</th>
                        <th class="px-6 py-3 font-semibold">Vendor LM</th>
                        <th class="px-6 py-3 font-semibold">Tanggal HO</th>
                        <th class="px-6 py-3 font-semibold">Stagging</th>
                        <th class="px-6 py-3 font-semibold">Status Akhir</th>
                        <th class="px-6 py-3 font-semibold">SLA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($shipments as $ship)
                        <tr class="cursor-pointer hover:bg-slate-50 transition"
                            data-resi="{{ $ship->no_resi }}"
                            @click="openDetail($el.dataset.resi)">
                            <td class="px-6 py-3.5 font-mono text-slate-700">{{ $ship->no_resi }}</td>
                            <td class="px-6 py-3.5 text-slate-800 font-medium max-w-[220px] truncate">{{ $ship->nama_sekolah }}</td>
                            <td class="px-6 py-3.5 text-slate-600">{{ $ship->provinsi ?? '—' }}</td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-medium">{{ $ship->vendor_lm ?? '—' }}</span>
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">{{ $ship->tanggal_manifest?->format('d M Y') ?? '—' }}</td>
                            <td class="px-6 py-3.5 text-slate-600">{{ $ship->stagging ?? '—' }}</td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    {{ match ($ship->status_akhir) {
                                        'Completed' => 'bg-green-100 text-green-700',
                                        'On Delivery' => 'bg-blue-100 text-blue-700',
                                        'Undelivered' => 'bg-red-100 text-red-700',
                                        'Hold' => 'bg-orange-100 text-orange-700',
                                        default => 'bg-slate-100 text-slate-600',
                                    } }}">
                                    <span class="w-1.5 h-1.5 rounded-full
                                        {{ match ($ship->status_akhir) {
                                            'Completed' => 'bg-green-500',
                                            'On Delivery' => 'bg-blue-500',
                                            'Undelivered' => 'bg-red-500',
                                            'Hold' => 'bg-orange-500',
                                            default => 'bg-slate-400',
                                        } }}"></span>
                                    {{ $ship->status_akhir }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    {{ $ship->sla_result === 'Out SLA' ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-200' }}">
                                    {{ $ship->sla_result ?? '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-slate-400">Belum ada data pengiriman.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $shipments->links() }}
        </div>
    </div>
</div>

{{-- Modal Detail Resi --}}
<style>[x-cloak]{display:none!important}</style>

<div x-show="open"
     x-cloak
     x-transition.opacity
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6">
    <div class="absolute inset-0 bg-black/60" @click="open = false"></div>
    <div class="relative w-full max-w-3xl max-h-[88vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-start justify-between gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 shrink-0">
            <div class="min-w-0">
                <h3 class="text-lg font-bold text-slate-900">Detail Resi</h3>
                <p class="text-sm text-slate-500 font-mono" x-text="data.no_resi || 'Memuat...'"></p>
            </div>
            <button @click="open = false" class="p-2 rounded-lg text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition shrink-0" aria-label="Tutup">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex-1 min-h-0 overflow-auto p-6">
            <div x-show="loading" class="py-10 text-center text-slate-400">Memuat data...</div>
            <div x-show="notFound && !loading" class="py-10 text-center text-slate-400">Resi tidak ditemukan.</div>
            <div x-show="!loading && !notFound" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                <template x-for="f in fieldList()" :key="f.label">
                    <div>
                        <p class="text-[11px] font-semibold tracking-wider text-slate-400 uppercase" x-text="f.label"></p>
                        <p class="mt-0.5 text-sm text-slate-800 font-medium break-words" x-text="f.value"></p>
                    </div>
                </template>
            </div>
        </div>
        <div class="flex items-center justify-end gap-4 px-6 py-3 border-t border-slate-200 bg-slate-50 shrink-0">
            <button @click="open = false"
                class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 transition">
                Tutup
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('detailController', () => ({
        open: false,
        loading: false,
        notFound: false,
        data: {},
        openDetail(noResi) {
            this.open = true;
            this.loading = true;
            this.notFound = false;
            this.data = {};

            fetch('/api/shipments/detail?no_resi=' + encodeURIComponent(noResi))
                .then(r => {
                    if (!r.ok) { this.notFound = true; return null; }
                    return r.json();
                })
                .then(row => { if (row) this.data = row; })
                .catch(() => { this.notFound = true; })
                .finally(() => { this.loading = false; });
        },
        fieldList() {
            const d = this.data || {};
            if (!d.no_resi) return [];

            const fmtDate = (v) => {
                if (!v) return null;
                const s = String(v);
                return /^\d{4}-\d{2}-\d{2}/.test(s) ? s.slice(0, 10) : s;
            };

            const items = [
                ['Nama Sekolah', d.nama_sekolah],
                ['Nama Penerima', d.nama_penerima],
                ['Provinsi', d.provinsi],
                ['Daerah', d.daerah],
                ['Kota / Kabupaten', d.kota_kabupaten],
                ['Kecamatan', d.kecamatan],
                ['Vendor MM', d.vendor_mm],
                ['Vendor LM', d.vendor_lm],
                ['Nomor Redock', d.nomor_redock],
                ['Delivery Order', d.delivery_order],
                ['Kode Funder', d.kode_funder],
                ['Nama Funder', d.nama_funder],
                ['Tanggal Manifest', fmtDate(d.tanggal_manifest)],
                ['Completed Date', fmtDate(d.completed_date)],
                ['Tgl Sampai Kota Tujuan', fmtDate(d.tgl_sampai_kota_tujuan)],
                ['Aging', d.aging !== null && d.aging !== undefined ? d.aging + ' hari' : null],
                ['Status Akhir', d.status_akhir],
                ['Status Instalasi', d.status_instalasi],
                ['Stagging', d.stagging],
                ['SLA', d.sla !== null && d.sla !== undefined ? d.sla + ' hari' : null],
                ['SLA Result', d.sla_result],
                ['Harga Per Shipment', d.harga_per_shipment ? 'Rp ' + Number(d.harga_per_shipment).toLocaleString('id-ID') : null],
                ['Status Invoice', d.status_invoice],
            ];

            return items
                .filter(([, v]) => v !== null && v !== undefined && String(v) !== '')
                .map(([label, value]) => ({ label, value: String(value) }));
        },
    }));
});
</script>
@endpush

</x-app-layout>