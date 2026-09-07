<x-app-layout>

<div class="p-4 sm:p-6 lg:p-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ isset($user) ? 'Edit User' : 'Tambah User' }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                @isset($user)
                    Ubah data akun untuk <span class="font-semibold text-slate-700">{{ $user->email }}</span>.
                @else
                    Buat akun baru untuk akses dashboard.
                @endisset
            </p>
        </div>
        <a href="{{ route('users.index') }}"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">
            Kembali ke Daftar
        </a>
    </div>

    {{-- Form --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form method="POST" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}" class="space-y-6">
            @csrf
            @isset($user)
                @method('PUT')
            @endisset

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user?->name ?? '') }}"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/40">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user?->email ?? '') }}"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/40">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Role</label>
                    <select name="role" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                        <option value="user" @selected(old('role', $user?->role ?? 'user') === 'user')>User</option>
                        <option value="admin" @selected(old('role', $user?->role ?? 'user') === 'admin')>Admin</option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_active" value="1"
                            @checked(old('is_active', $user?->is_active ?? true))
                            class="w-4 h-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500/40">
                        <span class="text-sm text-slate-700 font-medium">Akun aktif</span>
                    </label>
                </div>

                @isset($user)
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Password Baru <span class="text-slate-400 font-normal">(opsional)</span></label>
                        <input type="password" name="password" autocomplete="new-password" placeholder="Kosongkan jika tidak diubah"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/40">
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/40">
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Password</label>
                        <input type="password" name="password" required autocomplete="new-password"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/40">
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" required autocomplete="new-password"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/40">
                    </div>
                @endisset
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="submit"
                    class="px-5 py-2 rounded-lg bg-cyan-600 text-white text-sm font-semibold hover:bg-cyan-700 transition">
                    {{ isset($user) ? 'Simpan Perubahan' : 'Buat User' }}
                </button>
                <a href="{{ route('users.index') }}"
                    class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">Batal</a>
            </div>
        </form>
    </div>
</div>

</x-app-layout>