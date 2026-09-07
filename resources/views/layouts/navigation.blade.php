<nav x-data="{ sidebarOpen: false }">
    {{-- Mobile top bar --}}
    <div class="fixed top-0 inset-x-0 z-40 flex items-center justify-between h-16 px-4 bg-white border-b border-slate-200 shadow-sm md:hidden">
        <div class="flex items-center gap-2 text-slate-900 font-semibold tracking-tight">
            <svg class="w-7 h-7 text-cyan-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
            </svg>
            <span>ANL Dashboard</span>
        </div>
        <button @click="sidebarOpen = ! sidebarOpen" class="p-2 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{'hidden': sidebarOpen, 'inline-flex': ! sidebarOpen}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                <path :class="{'hidden': ! sidebarOpen, 'inline-flex': sidebarOpen}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    {{-- Backdrop (mobile) --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/50 md:hidden"></div>

    {{-- Sidebar --}}
    <aside :class="{'translate-x-0': sidebarOpen, '-translate-x-full': ! sidebarOpen}"
           class="fixed top-0 left-0 z-40 h-screen w-64 flex flex-col bg-white border-r border-slate-200 text-slate-600 transition-transform duration-200 md:translate-x-0">

        {{-- Brand --}}
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-5 h-16 border-b border-slate-200">
            <div class="p-2 rounded-lg bg-cyan-600/10">
                <svg class="w-7 h-7 text-cyan-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
            </div>
            <div>
                <p class="text-slate-900 font-semibold leading-tight">ANL Dashboard</p>
                <p class="text-[11px] text-slate-500">Operasional Pengiriman</p>
            </div>
        </a>

        {{-- Navigation --}}
        <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
            {{-- OPERASIONAL --}}
            <div>
                <p class="px-2 mb-2 text-[11px] font-bold tracking-widest text-slate-500 uppercase">Operasional</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                              {{ request()->routeIs('dashboard') ? 'bg-cyan-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }} transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                        Dashboard Operasional
                    </a>

                    <a href="{{ route('dashboard') }}#tren-pengiriman"
                       class="flex items-center gap-3 px-3 pl-11 py-2 rounded-lg text-[13px] text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                        Tren Pengiriman
                    </a>

                    <a href="{{ route('dashboard') }}#stagging-pengiriman"
                       class="flex items-center gap-3 px-3 pl-11 py-2 rounded-lg text-[13px] text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                        Stagging Pengiriman
                    </a>

                    <a href="{{ route('dashboard') }}#bottleneck-wide"
                       class="flex items-center gap-3 px-3 pl-11 py-2 rounded-lg text-[13px] text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                        Bottleneck Over SLA
                    </a>

                    <a href="{{ route('dashboard') }}#pengiriman-terbaru"
                       class="flex items-center gap-3 px-3 pl-11 py-2 rounded-lg text-[13px] text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                        Pengiriman Terbaru
                    </a>
                </div>
            </div>

            {{-- PENGIRIMAN --}}
            <div>
                <p class="px-2 mb-2 text-[11px] font-bold tracking-widest text-slate-500 uppercase">Pengiriman</p>
                <div class="space-y-1">
                    <a href="{{ route('shipments') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                              {{ request()->routeIs('shipments') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }} transition">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                        Data Pengiriman
                    </a>
                </div>
            </div>

            @if (auth()->user()?->isAdmin())
                {{-- SISTEM --}}
                <div>
                    <p class="px-2 mb-2 text-[11px] font-bold tracking-widest text-slate-500 uppercase">Sistem</p>
                    <div class="space-y-1">
                        <a href="{{ route('users.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                                  {{ request()->routeIs('users.*') ? 'bg-cyan-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }} transition">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            Manajemen User
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Profile footer --}}
        <div class="px-4 py-4 border-t border-slate-200">
            <div class="flex items-center gap-3 px-2">
                <div class="flex items-center justify-center w-9 h-9 rounded-full bg-cyan-600 text-white font-bold text-sm uppercase">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-slate-500 truncate">Operasional Pengiriman</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </aside>
</nav>