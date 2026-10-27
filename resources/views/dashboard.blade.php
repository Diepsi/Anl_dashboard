<x-app-layout>

<div x-data="introController()">

    <style>[x-cloak]{display:none!important}</style>

    {{-- Splash intro --}}
    <div x-show="splash"
         x-cloak
         x-transition.opacity.duration.300ms
         class="intro-splash fixed inset-0 z-[60] flex flex-col items-center justify-center gap-5 bg-white">
        <button @click="skip()"
            class="absolute top-5 right-5 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
            aria-label="Lewati animasi">
            Lewati
            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="flex items-center gap-4">
            <div class="intro-logo p-4 rounded-2xl bg-cyan-600/10">
                <svg class="w-14 h-14 text-cyan-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-900">ANL Dashboard</p>
                <p class="text-sm text-slate-500">Operasional Pengiriman</p>
            </div>
        </div>

        <div class="intro-progress intro-track h-1.5 rounded-full bg-slate-100 overflow-hidden">
            <div class="intro-progress-bar h-full rounded-full bg-cyan-600"></div>
        </div>

        <p class="text-xs text-slate-400">Memuat data pengiriman...</p>
    </div>

    <div class="p-4 sm:p-6 lg:p-8 space-y-6" x-data="modalController()">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Dashboard Operasional</h1>
            <p class="mt-1 text-sm text-slate-500">
                @if ($range === 0)
                    semua periode @else {{ $range }} hari terakhir data
                @endif
                @if ($latestManifest)
                    (data: {{ $earliestManifest?->format('d M Y') }} – {{ $latestManifest?->format('d M Y') }})
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1 p-1 rounded-lg bg-white border border-slate-200">
                @foreach ([0 => 'All', 30 => '30 Hari', 90 => '90 Hari'] as $r => $label)
                    <a href="{{ request()->fullUrlWithQuery(['range' => $r]) }}"
                       class="px-3 py-1.5 rounded-md text-sm font-semibold transition
                              {{ $range === $r ? 'bg-cyan-600 text-white shadow' : 'text-slate-600 hover:bg-slate-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
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
            <div class="px-3 py-1.5 rounded-lg text-xs text-slate-500 bg-white border border-slate-200">
                Terakhir sync:
                <span class="font-semibold text-slate-700">{{ $lastSync?->format('d M Y H:i') ?? '—' }}</span>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('sync'))
        <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
            {{ $errors->first('sync') }}
        </div>
    @endif

    @if ($sourceStats)
        @php
            $sourceRows = (int) ($sourceStats['imported_rows'] ?? $sourceStats['raw_rows'] ?? $dbTotal);
            $validRows = (int) $sourceStats['valid_rows'];
            $dupExtra = (int) $sourceStats['dup_extra'];
            $skippedEmpty = (int) $sourceStats['skipped_empty'];
            $skippedMalformed = (int) ($sourceStats['skipped_malformed'] ?? 0);
            $noResiRows = $skippedEmpty + $skippedMalformed;
            $malformedResi = $sourceStats['malformed_resi'] ?? [];
            $duplicates = $sourceStats['duplicates'] ?? [];
            $diff = $sourceRows - $dbTotal;
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <div class="p-2 rounded-lg bg-blue-800/10">
                    <svg class="w-5 h-5 text-blue-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                </div>
                <div class="text-sm text-slate-600">
                    Sumber GSheet: <span class="font-bold text-slate-900">{{ number_format($sourceRows, 0, ',', '.') }}</span> baris
                    <span class="mx-1 text-slate-300">·</span>
                    Database: <span class="font-bold text-slate-900">{{ number_format($dbTotal, 0, ',', '.') }}</span> baris
                </div>
                @if ($diff === 0)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Sesuai
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        Selisih {{ number_format(abs($diff), 0, ',', '.') }}
                    </span>
                @endif
            </div>
            @if ($diff !== 0 || $dupExtra > 0 || $noResiRows > 0)
                <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                    @if ($diff !== 0)
                        Database menyimpan {{ number_format(abs($diff), 0, ',', '.') }} baris yang tidak ada di sumber saat ini —
                        sisa dari sinkronisasi sebelumnya. Gunakan <span class="font-semibold">--replace</span> untuk menyamakannya.
                        @if ($dupExtra > 0 || $noResiRows > 0)
                            ·
                        @endif
                    @endif
                    @if ($dupExtra > 0)
                        {{ number_format($dupExtra, 0, ',', '.') }} baris berbagi <span class="font-semibold text-amber-700">no resi</span> dengan baris lain —
                        semuanya disimpan dan ditandai
                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700">duplikat</span>.
                        @if ($noResiRows > 0)
                            ·
                        @endif
                    @endif
                    @if ($noResiRows > 0)
                        {{ number_format($noResiRows, 0, ',', '.') }} baris tanpa No Resi yang valid ikut disimpan dan ditampilkan sebagai
                        <span class="font-mono text-slate-700">—</span>.
                    @endif
                </p>
            @endif
            @if (! empty($malformedResi))
                <details class="mt-2 group">
                    <summary class="text-xs font-semibold text-red-700 cursor-pointer hover:text-red-800 select-none">
                        Lihat No Resi tidak valid ({{ $skippedMalformed }} baris)
                    </summary>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Nilai ini bukan nomor resi (mis. <span class="font-mono">#N/A</span> atau notasi ilmiah
                        <span class="font-mono">1,0094E+15</span>). Perbaiki di sheet, atau ubah kolom
                        <span class="font-semibold">No Resi</span> menjadi Plain text agar tidak dikonversi angka.
                    </p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($malformedResi as $nilai => $muncul)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-red-50 border border-red-200 text-[11px] font-mono text-red-800">
                                {{ $nilai }}
                                <span class="text-red-400 font-sans">×{{ $muncul }}</span>
                            </span>
                        @endforeach
                    </div>
                </details>
            @endif
            @if (! empty($duplicates))
                <details class="mt-2 group">
                    <summary class="text-xs font-semibold text-cyan-700 cursor-pointer hover:text-cyan-800 select-none">
                        Lihat daftar no_resi duplikat ({{ count($duplicates) }})
                    </summary>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($duplicates as $noResi => $muncul)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200 text-[11px] font-mono text-amber-800">
                                {{ $noResi }}
                                <span class="text-amber-500 font-sans">×{{ $muncul }}</span>
                            </span>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 xl:gap-5 stagger-ready">

        {{-- Total Shipments --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-blue-800 p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Total Shipments</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($totalShipment, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-400">Total keseluruhan resi</p>
                    @if ($koliRows > 0)
                        <p class="mt-2 text-xs text-slate-500">
                            <span class="font-semibold text-slate-700">{{ number_format($totalKoli, 0, ',', '.') }}</span> koli
                            <span class="text-slate-400">· n {{ number_format($koliRows, 0, ',', '.') }} resi</span>
                        </p>
                    @endif
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Completion</span>
                            <span class="font-bold text-emerald-600">{{ $completionRate }}%</span>
                        </div>
                        <div class="mt-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $completionRate }}%"></div>
                        </div>
                    </div>
                </div>
                <div class="p-2.5 rounded-lg bg-blue-800/10 shrink-0">
                    <svg class="w-6 h-6 text-blue-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m13.5 0a2.246 2.246 0 0 1 0 .628m0 0a2.25 2.25 0 0 1-.75.128M6 13.5h12m-9 3h6" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-green-500 p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Completed</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($completed, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-400">Telah sampai di tujuan</p>
                </div>
                <div class="p-2.5 rounded-lg bg-green-500/10 shrink-0">
                    <svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- On Delivery --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-blue-500 p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">On Delivery</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($onDeliveryCount, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-400">Sedang dikirim ke tujuan</p>
                </div>
                <div class="p-2.5 rounded-lg bg-blue-500/10 shrink-0">
                    <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Hold --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-orange-500 p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Hold</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($holdCount, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-400">Ditahan / menunggu follow-up</p>
                </div>
                <div class="p-2.5 rounded-lg bg-orange-500/10 shrink-0">
                    <svg class="w-6 h-6 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008Zm0-6.75h.008v.008H12V9.75Zm1.35-4.5h8.7a.75.75 0 0 1 .75.75v8.7a.75.75 0 0 1-.22.53l-8.7 8.7a.75.75 0 0 1-1.06 0l-8.7-8.7a.75.75 0 0 1-.22-.53v-8.7a.75.75 0 0 1 .75-.75h8.7Z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Undelivered --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-red-500 p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Undelivered</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($undeliveredCount, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-400">Gagal terkirim / kembali</p>
                    <p class="mt-1 text-xs font-bold text-red-600">{{ $undeliveredRate }}% dari total kiriman</p>
                </div>
                <div class="p-2.5 rounded-lg bg-red-500/10 shrink-0">
                    <svg class="w-6 h-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Within SLA --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-emerald-500 p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Within SLA</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($withinSla, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-400">Tepat & sesuai SLA terverifikasi</p>
                </div>
                <div class="p-2.5 rounded-lg bg-emerald-500/10 shrink-0">
                    <svg class="w-6 h-6 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Over SLA --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-red-600 p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Over SLA</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($overSla, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-400">
                        Berjalan <span class="font-semibold text-red-600">{{ number_format($outSlaActive) }}</span>
                        · selesai <span class="font-semibold">{{ number_format($outSlaDone) }}</span>
                    </p>
                    <p class="mt-1 text-[11px] leading-snug text-slate-400">Hanya dihitung pada kiriman berambang SLA valid.</p>
                </div>
                <div class="p-2.5 rounded-lg bg-red-600/10 shrink-0">
                    <svg class="w-6 h-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- SLA Ach. Rate --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-indigo-500 p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">SLA Ach. Rate</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        @if ($slaPct === null)
                            <span class="text-slate-300">&mdash;</span>
                        @else
                            {{ $slaPct }}<span class="text-lg text-slate-400 font-semibold">%</span>
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Meet <span class="font-semibold text-emerald-600">{{ number_format($withinSla) }}</span>
                        · Over <span class="font-semibold text-red-600">{{ number_format($overSla) }}</span>
                    </p>
                    <p class="mt-1 text-[11px] leading-snug text-slate-400">
                        Dari <span class="font-semibold text-slate-500">{{ number_format($slaCovered) }}</span>
                        kiriman berambang SLA valid
                        @if ($slaUnverified > 0)
                            · <span class="font-semibold text-amber-600">{{ number_format($slaUnverified) }}</span> tanpa ambang SLA terverifikasi, tidak dihitung
                        @endif
                    </p>
                </div>
                <div class="p-2.5 rounded-lg bg-indigo-500/10 shrink-0">
                    <svg class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Median Durasi --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-cyan-600 p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">Median Durasi</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        @if ($leadTime['median'] !== null)
                            {{ number_format($leadTime['median'], 0, ',', '.') }}<span class="text-lg text-slate-400 font-semibold"> hari</span>
                        @else
                            <span class="text-2xl text-slate-300">—</span>
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-slate-400">Manifest → selesai (Completed)</p>
                    @if ($leadTime['sample'] > 0)
                        <p class="mt-1 text-xs text-slate-400">
                            p90 <span class="font-semibold text-slate-600">{{ number_format($leadTime['p90'], 0, ',', '.') }} hari</span>
                            <span class="text-slate-300">·</span>
                            n <span class="font-semibold text-slate-600">{{ number_format($leadTime['sample'], 0, ',', '.') }}</span>
                        </p>
                    @endif
                </div>
                <div class="p-2.5 rounded-lg bg-cyan-600/10 shrink-0">
                    <svg class="w-6 h-6 text-cyan-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Combo Chart --}}
    <div id="tren-pengiriman" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 stagger-ready">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Tren Pengiriman Daily</h2>
                <p class="text-sm text-slate-500">Volume kirim, selesai, & kepatuhan SLA per hari</p>
            </div>
            <div class="flex items-center gap-1 p-1 rounded-lg bg-slate-100">
                @foreach ([0 => 'All', 30 => '30 Hari', 90 => '90 Hari'] as $r => $label)
                    <a href="{{ request()->fullUrlWithQuery(['range' => $r]) }}"
                       class="px-3 py-1.5 rounded-md text-xs font-semibold transition
                              {{ $range === $r ? 'bg-white text-slate-900 shadow' : 'text-slate-500 hover:text-slate-800' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="h-[380px]">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    {{-- Analytics doughnuts --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 stagger-ready">

        {{-- Status Pengiriman --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">Status Pengiriman</h2>
                <p class="text-sm text-slate-500">Komposisi status akhir saat ini</p>
            </div>
            <div class="relative h-[260px]">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        {{-- SLA Compliance Rate --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">SLA Compliance Rate</h2>
                <p class="text-sm text-slate-500">
                    Tingkat pemenuhan SLA pada {{ number_format($slaCovered) }} kiriman berambang SLA valid
                </p>
            </div>
            <div class="relative h-[260px]">
                <canvas id="slaChart"></canvas>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="text-center">
                        @if ($slaPct === null)
                            <p class="text-3xl font-bold text-slate-300">&mdash;</p>
                        @else
                            <p class="text-3xl font-bold text-slate-900">{{ $slaPct }}<span class="text-base text-slate-400">%</span></p>
                            <p class="text-xs text-slate-400 font-medium">Meet SLA</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Status BAST Balik --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">Status BAST Balik</h2>
                <p class="text-sm text-slate-500">BAST dari vendor berdasarkan keterangan</p>
            </div>
            <div class="relative h-[260px]">
                <canvas id="bastChart"></canvas>
            </div>
        </div>

        {{-- BAST Handover Finance --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">BAST Handover Finance</h2>
                <p class="text-sm text-slate-500">BAST yang telah diserahkan ke finance</p>
            </div>
            <div class="relative h-[260px]">
                <canvas id="financeChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top 5 charts row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 stagger-ready">

        {{-- Top 5 Provinsi --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">Top 5 Provinsi Tujuan</h2>
                <p class="text-sm text-slate-500">5 provinsi dengan pengiriman terbanyak saat ini</p>
            </div>
            <div class="h-[320px]">
                <canvas id="provinsiChart"></canvas>
            </div>
        </div>

        {{-- Top Vendor --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">Top Vendor</h2>
                <p class="text-sm text-slate-500">Vendor logistik laut — 5 teratas + lainnya</p>
            </div>
            <div class="h-[320px]">
                <canvas id="vendorChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Kinerja Vendor --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden stagger-ready">
        <div class="px-6 py-5 border-b border-slate-200">
            <h2 class="text-lg font-bold text-slate-900">Kinerja Vendor</h2>
            <p class="text-sm text-slate-500">5 vendor teratas — kualitas on-time (SLA) & durasi kirim. Durasi memakai median/p90, hanya baris yang punya tanggal manifest. On-time hanya dihitung bila vendor punya ambang SLA terverifikasi.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="px-6 py-3 font-semibold">Vendor LM</th>
                        <th class="px-6 py-3 font-semibold">Total Resi</th>
                        <th class="px-6 py-3 font-semibold">On-Time (SLA)</th>
                        <th class="px-6 py-3 font-semibold">Median Durasi</th>
                        <th class="px-6 py-3 font-semibold">p90 Durasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($vendorStats as $v)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-3.5 font-semibold text-slate-800">{{ $v['vendor'] }}</td>
                            <td class="px-6 py-3.5 text-slate-600">{{ number_format($v['total'], 0, ',', '.') }}</td>
                            <td class="px-6 py-3.5">
                                @if ($v['onTimePct'] === null)
                                    <span class="text-slate-400" title="Vendor ini tidak punya ambang SLA terverifikasi di sumber data, jadi on-time tidak bisa dihitung">—</span>
                                    <span class="block text-xs text-slate-400">tanpa ambang SLA</span>
                                @else
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        {{ $v['onTimePct'] >= 80 ? 'bg-emerald-100 text-emerald-700' : ($v['onTimePct'] >= 60 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                        {{ $v['onTimePct'] }}%
                                    </span>
                                    <span class="block text-xs text-slate-400">n {{ number_format($v['slaCovered'], 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">{{ $v['medianLead'] !== null ? number_format($v['medianLead'], 0, ',', '.') . ' hari' : '—' }}</td>
                            <td class="px-6 py-3.5 text-slate-600">
                                @if ($v['p90Lead'] !== null)
                                    {{ number_format($v['p90Lead'], 0, ',', '.') }} hari
                                    <span class="block text-xs text-slate-400">n {{ number_format($v['leadSample'], 0, ',', '.') }}</span>
                                @else
                                    <span class="text-slate-400" title="Tanggal manifest belum tersedia di sumber data">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400">Belum ada data vendor.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Middle row: Stagging + Bottleneck --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 stagger-ready">

        {{-- Stagging Pipeline --}}
        <div id="stagging-pengiriman" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">Stagging Pengiriman</h2>
                <p class="text-sm text-slate-500">Posisi pengiriman saat ini — klik tahap untuk detail</p>
            </div>

            <div class="space-y-2">
                @forelse ($staggingList as $stage)
                    <button
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:border-cyan-400 hover:bg-cyan-50/50 transition group text-left"
                        @click="openStaging($el.dataset.stage, $el.dataset.total)"
                        data-stage="{{ $stage['name'] }}" data-total="{{ $stage['total'] }}">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-500 shrink-0"></span>
                        <span class="flex-1 min-w-0">
                            <span class="block text-sm font-semibold text-slate-800 group-hover:text-cyan-800">{{ $stage['name'] }}</span>
                            <span class="block text-xs text-slate-400">Klik untuk melihat detail resi</span>
                        </span>
                        <span class="px-2.5 py-1 rounded-lg bg-cyan-50 text-cyan-700 text-sm font-bold">{{ number_format($stage['total']) }}</span>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-cyan-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12h10.5m0 0-3-3m3 3-3 3" />
                        </svg>
                    </button>
                @empty
                    <p class="text-sm text-slate-400">Belum ada data stagging.</p>
                @endforelse
            </div>
        </div>

        {{-- Bottleneck Over SLA --}}
        <div id="bottleneck-wide" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">Bottleneck Wilayah Over SLA</h2>
                <p class="text-sm text-slate-500">Provinsi dengan pengiriman melebihi SLA — klik untuk detail</p>
            </div>

            @php
                $maxBottleneck = $bottleneckStats->max('total') ?: 1;
            @endphp

            <div class="space-y-3">
                @forelse ($bottleneckStats as $b)
                    <button
                        class="w-full group text-left"
                        @click="openBottleneck($el.dataset.provinsi, $el.dataset.total)"
                        data-provinsi="{{ $b->provinsi }}" data-total="{{ $b->total }}">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold text-slate-700 group-hover:text-red-700">{{ $b->provinsi }}</span>
                            <span class="text-sm font-bold text-red-600">{{ number_format($b->total) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-red-500 transition group-hover:from-amber-500 group-hover:to-red-600"
                                 style="width: {{ ($b->total / $maxBottleneck) * 100 }}%"></div>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">Klik untuk melihat detail resi</p>
                    </button>
                @empty
                    <p class="text-sm text-slate-400">Tidak ada data Over SLA pada filter ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Perlu Perhatian --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 stagger-ready">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-900">Perlu Perhatian</h2>
            <p class="text-sm text-slate-500">Pengiriman belum selesai yang perlu follow-up — Hold/Undelivered/Retur, atau usia ≥ 14 hari dari tanggal manifest terakhir</p>
        </div>

        @php
            $agingTotal = $agingBuckets->sum();
            $maxBucket = $agingBuckets->max() ?: 1;
        @endphp

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ($agingBuckets as $label => $total)
                <div class="rounded-xl border {{ $label === '> 60 hari' ? 'border-red-200 bg-red-50/60' : 'border-slate-200 bg-slate-50/60' }} p-4">
                    <p class="text-xs font-semibold tracking-wider {{ $label === '> 60 hari' ? 'text-red-600' : 'text-slate-500' }} uppercase">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold {{ $label === '> 60 hari' ? 'text-red-700' : 'text-slate-900' }}">{{ number_format($total) }}</p>
                    <div class="mt-2 h-1.5 rounded-full bg-slate-200/70 overflow-hidden">
                        <div class="h-full rounded-full {{ $label === '> 60 hari' ? 'bg-red-400' : 'bg-slate-400' }}"
                             style="width: {{ ($total / max($agingTotal, 1)) * 100 }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($agingExcluded > 0)
            <p class="mt-3 text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                <span class="font-semibold text-slate-700">{{ number_format($agingExcluded) }} resi aktif</span>
                (termasuk {{ number_format($returCount) }} Retur) tidak masuk tabel usia di atas karena sumber
                tidak memuat tanggal HO ke Vendor. Usia tidak dapat dihitung — sengaja ditampilkan kosong, bukan 0.
            </p>
        @endif

        @if ($attentionShipments->isNotEmpty())
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <th class="px-4 py-3 font-semibold">No Resi</th>
                            <th class="px-4 py-3 font-semibold">Sekolah</th>
                            <th class="px-4 py-3 font-semibold">Provinsi</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Stagging</th>
                            <th class="px-4 py-3 font-semibold">Usia</th>
                            <th class="px-4 py-3 font-semibold">SLA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($attentionShipments as $ship)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3 font-mono text-slate-700 whitespace-nowrap">
                                    {{ $ship->no_resi ?? '—' }}
                                    @if ($ship->is_duplicate_no_resi)
                                        <span class="ml-1 inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700">duplikat</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-800 font-medium max-w-[200px] truncate">{{ $ship->nama_sekolah }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $ship->provinsi ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        {{ match ($ship->status_akhir) {
                                            'Completed' => 'bg-green-100 text-green-700',
                                            'On Delivery' => 'bg-blue-100 text-blue-700',
                                            'Undelivered' => 'bg-red-100 text-red-700',
                                            'Hold' => 'bg-orange-100 text-orange-700',
                                            'Retur' => 'bg-fuchsia-100 text-fuchsia-700',
                                            default => 'bg-slate-100 text-slate-600',
                                        } }}">
                                        {{ $ship->status_akhir }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $ship->stagging ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold
                                        {{ ($ship->days_open ?? 0) >= 30 ? 'bg-red-100 text-red-700' : (($ship->days_open ?? 0) >= 14 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                                        {{ $ship->days_open !== null ? $ship->days_open . ' hari' : '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $verdict = $ship->sla_verdict;
                                    @endphp
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        @if ($verdict === 'Out SLA') bg-red-50 text-red-600 border border-red-200
                                        @elseif ($verdict === 'Meet SLA') bg-emerald-50 text-emerald-600 border border-emerald-200
                                        @else bg-slate-100 text-slate-500 border border-slate-200 @endif">
                                        {{ $verdict ?? 'Tidak terverifikasi' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-400">Tidak ada pengiriman yang perlu perhatian pada filter ini.</p>
        @endif
    </div>

    {{-- Recent table --}}
    <div id="pengiriman-terbaru" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden stagger-ready">
        <div class="px-6 py-5 border-b border-slate-200">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Pengiriman Terbaru</h2>
                    <p class="text-sm text-slate-500">8 resi terakhir</p>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>Completed
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>On Delivery
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>Undelivered
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span>Hold
                    </span>
                    <span class="italic text-slate-400" title="Hold = pengiriman ditahan / sedang menunggu tindak lanjut (follow-up), mis. menunggu info alamat atau persetujuan">
                        Hold = ditahan / menunggu follow-up
                    </span>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <th class="px-6 py-3 font-semibold">No Resi</th>
                        <th class="px-6 py-3 font-semibold">Sekolah</th>
                        <th class="px-6 py-3 font-semibold">Provinsi</th>
                        <th class="px-6 py-3 font-semibold">Stagging</th>
                        <th class="px-6 py-3 font-semibold">Status Akhir</th>
                        <th class="px-6 py-3 font-semibold">SLA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentShipments as $ship)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-3.5 font-mono text-slate-700 whitespace-nowrap">
                                {{ $ship->no_resi ?? '—' }}
                                @if ($ship->is_duplicate_no_resi)
                                    <span class="ml-1.5 inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700">duplikat</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-slate-800 font-medium max-w-[200px] truncate">{{ $ship->nama_sekolah }}</td>
                            <td class="px-6 py-3.5 text-slate-600">{{ $ship->provinsi ?? '—' }}</td>
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
                                @php $verdict = $ship->sla_verdict; @endphp
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    {{ match ($verdict) {
                                        'Out SLA' => 'bg-red-50 text-red-600 border border-red-200',
                                        'Meet SLA' => 'bg-emerald-50 text-emerald-600 border border-emerald-200',
                                        default => 'bg-slate-100 text-slate-500 border border-slate-200',
                                    } }}">
                                    {{ $verdict ?? 'Tidak terverifikasi' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">Belum ada data pengiriman.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

{{-- Modal Detail --}}
    <style>[x-cloak]{display:none!important}</style>

    <div x-show="modalOpen"
         x-cloak
         x-transition.opacity
         @keydown.escape.window="modalOpen = false"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/60" @click="modalOpen = false"></div>
        <div class="relative w-full max-w-4xl max-h-[88vh] flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="flex items-start justify-between gap-4 px-6 py-4 border-b border-slate-200 bg-slate-50 shrink-0">
                <div class="min-w-0">
                    <h3 class="text-lg font-bold text-slate-900 truncate" x-text="modalTitle"></h3>
                    <p class="text-sm text-slate-500" x-text="modalSubtitle"></p>
                </div>
                <button @click="modalOpen = false" class="p-2 rounded-lg text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition shrink-0" aria-label="Tutup">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 min-h-0 overflow-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50">
                        <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <th class="px-6 py-3 font-semibold">No Resi</th>
                            <th class="px-6 py-3 font-semibold">Sekolah</th>
                            <th class="px-6 py-3 font-semibold">Provinsi</th>
                            <th class="px-6 py-3 font-semibold">Kota/Kab</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold">SLA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="row in modalRows" :key="row.no_resi">
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3 font-mono text-slate-700 whitespace-nowrap">
                                    <span x-text="row.no_resi || '—'"></span>
                                    <span x-show="row.is_duplicate_no_resi" class="ml-1.5 inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700">duplikat</span>
                                </td>
                                <td class="px-6 py-3 text-slate-800 font-medium max-w-[260px] truncate" x-text="row.nama_sekolah"></td>
                                <td class="px-6 py-3 text-slate-600" x-text="row.provinsi ?? '—'"></td>
                                <td class="px-6 py-3 text-slate-600" x-text="row.kota_kabupaten ?? '—'"></td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                          :class="statusClass(row.status_akhir)"
                                          x-text="row.status_akhir"></span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                          :class="slaBadgeClass(row.sla_due_date, row.completed_date)"
                                          x-text="slaBadgeLabel(row.sla_due_date, row.completed_date)"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="modalLoading">
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">Memuat data...</td>
                        </tr>
                        <tr x-show="!modalLoading && modalRows.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">Tidak ada data pada filter ini.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between gap-4 px-6 py-3 border-t border-slate-200 bg-slate-50 shrink-0">
                <p class="text-sm text-slate-500">
                    Menampilkan <span class="font-semibold text-slate-700" x-text="modalRows.length"></span> resi
                    <span x-show="modalRowsFound > modalRows.length" class="text-slate-400" x-text="'dari ' + modalRowsFound"></span>
                </p>
                <button @click="modalOpen = false"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

@push('scripts')
<style>[x-cloak]{display:none!important}</style>
<style>
.intro-splash { z-index: 60; }
.intro-logo { animation: introPulse 1.4s ease-in-out infinite; }
.intro-track { width: 14rem; }
@keyframes introPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
.intro-progress-bar { width: 0%; animation: introFill 1.15s ease-out forwards; }
@keyframes introFill { to { width: 100%; } }

.intro-active .stagger-ready > * {
    opacity: 0;
    transform: translateY(14px);
    animation: introRise .55s cubic-bezier(.22, 1, .36, 1) forwards;
}
.intro-active .stagger-ready > *:nth-child(1) { animation-delay: .05s; }
.intro-active .stagger-ready > *:nth-child(2) { animation-delay: .12s; }
.intro-active .stagger-ready > *:nth-child(3) { animation-delay: .19s; }
.intro-active .stagger-ready > *:nth-child(4) { animation-delay: .26s; }
.intro-active .stagger-ready > *:nth-child(5) { animation-delay: .33s; }
.intro-active .stagger-ready > *:nth-child(6) { animation-delay: .40s; }
.intro-active .stagger-ready > *:nth-child(7) { animation-delay: .47s; }
.intro-active .stagger-ready > *:nth-child(8) { animation-delay: .54s; }
.intro-active .stagger-ready > *:nth-child(n+9) { animation-delay: .60s; }
@keyframes introRise { to { opacity: 1; transform: none; } }
@media (prefers-reduced-motion: reduce) {
    .intro-active .stagger-ready > * { animation: none; opacity: 1; transform: none; }
}
</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('introController', () => ({
        splash: false,
        init() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || sessionStorage.getItem('anl_intro_shown')) {
                return;
            }
            sessionStorage.setItem('anl_intro_shown', '1');
            this.splash = true;
            setTimeout(() => {
                this.splash = false;
                document.documentElement.classList.add('intro-active');
            }, 3000);
            setTimeout(() => document.documentElement.classList.remove('intro-active'), 3000);
        },
        skip() {
            this.splash = false;
            document.documentElement.classList.remove('intro-active');
        },
    }));
});
</script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('modalController', () => ({
        modalOpen: false,
        modalTitle: '',
        modalSubtitle: '',
        modalRows: [],
        modalRowsFound: 0,
        modalLoading: false,
        statusClass(status) {
            return {
                'Completed': 'bg-green-100 text-green-700',
                'On Delivery': 'bg-blue-100 text-blue-700',
                'Undelivered': 'bg-red-100 text-red-700',
                'Hold': 'bg-orange-100 text-orange-700',
                'Retur': 'bg-fuchsia-100 text-fuchsia-700',
            }[status] || 'bg-slate-100 text-slate-600';
        },
        // Verdict dihitung ulang di browser dengan aturan yang sama persis seperti
        // scopeOutSla()/scopeMeetSla() di server: selesai pada atau sebelum batas
        // SLA berarti Meet. Tanpa kedua tanggal, baris tidak bisa dinilai dan harus
        // tampil abu-abu, bukan hijau karena sumbernya menulis "Meet SLA".
        slaVerifiable(due, completed) {
            return due !== null && due !== undefined && due !== ''
                && completed !== null && completed !== undefined && completed !== '';
        },
        slaVerdict(due, completed) {
            if (!this.slaVerifiable(due, completed)) {
                return null;
            }

            return String(completed).slice(0, 10) <= String(due).slice(0, 10) ? 'Meet SLA' : 'Out SLA';
        },
        slaBadgeClass(due, completed) {
            const verdict = this.slaVerdict(due, completed);

            if (verdict === 'Out SLA') {
                return 'bg-red-50 text-red-600 border border-red-200';
            }

            if (verdict === 'Meet SLA') {
                return 'bg-emerald-50 text-emerald-600 border border-emerald-200';
            }

            return 'bg-slate-100 text-slate-500 border border-slate-200';
        },
        slaBadgeLabel(due, completed) {
            return this.slaVerdict(due, completed) ?? 'Tidak terverifikasi';
        },
        openStaging(stage, total) {
            this.loadModal(
                'Stagging: ' + stage,
                'Resi yang sedang berada di tahap ini (maks. 50 tampil)',
                '/api/shipments/staging/' + encodeURIComponent(stage) + '?range=' + @json($range),
                Number(total)
            );
        },
        openBottleneck(provinsi, total) {
            this.loadModal(
                'Over SLA — ' + provinsi,
                'Resi yang melewati SLA terverifikasi di provinsi ini (maks. 50 tampil)',
                '/api/shipments/bottleneck?provinsi=' + encodeURIComponent(provinsi) + '&range=' + @json($range),
                Number(total)
            );
        },
        loadModal(title, subtitle, url, foundTotal) {
            this.modalTitle = title;
            this.modalSubtitle = subtitle;
            this.modalRows = [];
            this.modalRowsFound = foundTotal || 0;
            this.modalLoading = true;
            this.modalOpen = true;

            fetch(url)
                .then(r => r.json())
                .then(rows => { this.modalRows = rows; this.modalLoading = false; })
                .catch(() => { this.modalRows = []; this.modalLoading = false; });
        },
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    const themeColors = ['#075985', '#0891b2', '#06b6d4', '#22d3ee', '#67e8f9', '#94a3b8'];

    const renderChart = (id, config) => {
        const el = document.getElementById(id);
        if (!el || typeof Chart === 'undefined') return;
        new Chart(el.getContext('2d'), config);
    };

    const trendLabels = @json($chartData->map(fn ($d) => \Illuminate\Support\Carbon::parse($d->tanggal)->format('d M'))->values());
    const trendKirim = @json($chartData->pluck('volume_kirim')->map(fn ($v) => (int) $v)->values());
    const trendSelesai = @json($chartData->pluck('volume_selesai')->map(fn ($v) => (int) $v)->values());
    const trendOutSla = @json($chartData->map(fn ($d) => (int) ($outSlaDaily[$d->tanggal]->volume_out ?? 0))->values());
    const trendCompliance = @json($chartData->map(function ($d) use ($slaComplianceDaily) {
        $row = $slaComplianceDaily[$d->tanggal] ?? null;
        if (! $row || (int) $row->total <= 0) return null;
        return (int) round(((int) $row->meet / (int) $row->total) * 100);
    })->values());

    if (trendLabels.length) {
        renderChart('trendChart', {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [
                    { type: 'bar', label: 'Volume Kirim', data: trendKirim, backgroundColor: 'rgba(8,145,178,0.75)', borderRadius: 3 },
                    { type: 'line', label: 'Volume Selesai', data: trendSelesai, borderColor: '#1d4ed8', backgroundColor: '#1d4ed8', borderWidth: 2, tension: 0.3, pointRadius: 2 },
                    { type: 'line', label: 'Out SLA', data: trendOutSla, borderColor: '#dc2626', backgroundColor: '#dc2626', borderWidth: 2, borderDash: [6, 4], tension: 0.3, pointRadius: 2 },
                    { type: 'line', yAxisID: 'y1', label: 'SLA Compliance %', data: trendCompliance, borderColor: '#16a34a', backgroundColor: '#16a34a', borderWidth: 2, tension: 0.3, pointRadius: 2 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, y1: { position: 'right', min: 0, max: 100, grid: { drawOnChartArea: false }, ticks: { callback: v => v + '%', precision: 0 } } } },
        });
    }

    const provinsiLabels = @json($topProvinces->keys()->values());
    const provinsiData = @json($topProvinces->values());

    renderChart('provinsiChart', {
        type: 'bar',
        data: {
            labels: provinsiLabels,
            datasets: [{ label: 'Jumlah Resi', data: provinsiData, backgroundColor: themeColors.slice(0, provinsiLabels.length), borderRadius: 4 }],
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
    });

    const vendorLabels = @json($topVendors->keys()->values());
    const vendorData = @json($topVendors->values());

    renderChart('vendorChart', {
        type: 'doughnut',
        data: {
            labels: vendorLabels,
            datasets: [{ data: vendorData, backgroundColor: themeColors.slice(0, vendorLabels.length), borderWidth: 2, borderColor: '#ffffff' }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom' } } },
    });

    const statusColors = {
        'Completed': '#22c55e',
        'On Delivery': '#3b82f6',
        'Hold': '#f59e0b',
        'Undelivered': '#ef4444',
    };
    const statusLabels = @json($statusBreakdown->keys()->values());
    const statusData = @json($statusBreakdown->values());

    renderChart('statusChart', {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: statusLabels.map(l => statusColors[l] || '#64748b'),
                borderWidth: 2,
                borderColor: '#ffffff',
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } },
    });

    const slaData = @json([$withinSla, $overSla]);

    renderChart('slaChart', {
        type: 'doughnut',
        data: {
            labels: ['Meet SLA', 'Out SLA'],
            datasets: [{
                data: slaData,
                backgroundColor: ['#10b981', '#ef4444'],
                borderWidth: 2,
                borderColor: '#ffffff',
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { position: 'bottom' } } },
    });

    const bastLabels = @json($bastBalikStats->keys()->values());
    const bastData = @json($bastBalikStats->values());
    const bastColors = ['#10b981', '#f59e0b', '#94a3b8', '#3b82f6', '#a855f7', '#64748b'];
    const bastColorAt = (i) => bastColors[i] || '#cbd5e1';

    renderChart('bastChart', {
        type: 'doughnut',
        data: {
            labels: bastLabels,
            datasets: [{
                data: bastData,
                backgroundColor: bastLabels.map((_, i) => bastColorAt(i)),
                borderWidth: 2,
                borderColor: '#ffffff',
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } },
    });

    const financeLabels = @json($bastFinanceStats->keys()->values());
    const financeData = @json($bastFinanceStats->values());

    renderChart('financeChart', {
        type: 'doughnut',
        data: {
            labels: financeLabels,
            datasets: [{
                data: financeData,
                backgroundColor: ['#0891b2', '#cbd5e1'],
                borderWidth: 2,
                borderColor: '#ffffff',
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } },
    });
});
</script>
@endpush

</div>

</x-app-layout>