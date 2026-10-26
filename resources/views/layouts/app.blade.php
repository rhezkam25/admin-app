<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Absensi Pegawai' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full text-slate-800 antialiased">
    <div class="min-h-full lg:flex">
        <aside class="w-full bg-slate-900 text-slate-100 lg:flex lg:w-64 lg:flex-col">
            <div class="flex items-center justify-between px-5 py-4">
                <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight">
                    Absensi Pegawai
                </a>
                <span class="rounded bg-slate-800 px-2 py-1 text-xs text-slate-300">
                    {{ auth()->user()?->peran?->label() }}
                </span>
            </div>

            <nav class="space-y-1 px-3 pb-4 text-sm">
                <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('dashboard') ? 'bg-slate-800' : '' }}">Dashboard</a>
                <a href="{{ route('attendance') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('attendance') ? 'bg-slate-800' : '' }}">Absensi</a>
                <a href="{{ route('leave') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('leave') ? 'bg-slate-800' : '' }}">Cuti & Izin</a>
                <a href="{{ route('overtime') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('overtime') ? 'bg-slate-800' : '' }}">Lembur</a>
                <a href="{{ route('reports') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('reports') ? 'bg-slate-800' : '' }}">Laporan</a>
                <a href="{{ route('notifications') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('notifications') ? 'bg-slate-800' : '' }}">Notifikasi</a>

                @if (auth()->user()?->isAdmin())
                    <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">Administrasi</p>
                    <a href="{{ route('admin.employees') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('admin.employees') ? 'bg-slate-800' : '' }}">Pegawai</a>
                    <a href="{{ route('admin.attendance') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('admin.attendance') ? 'bg-slate-800' : '' }}">Koreksi Absensi</a>
                    <a href="{{ route('admin.holidays') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('admin.holidays') ? 'bg-slate-800' : '' }}">Hari Libur</a>
                    <a href="{{ route('admin.schedules') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('admin.schedules') ? 'bg-slate-800' : '' }}">Jadwal Kerja</a>
                    <a href="{{ route('admin.periods') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('admin.periods') ? 'bg-slate-800' : '' }}">Periode Penggajian</a>
                    <a href="{{ route('admin.settings') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('admin.settings') ? 'bg-slate-800' : '' }}">Pengaturan</a>
                    <a href="{{ route('audit') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('audit') ? 'bg-slate-800' : '' }}">Audit Log</a>
                @endif
            </nav>

            <div class="mt-auto border-t border-slate-800 px-5 py-4 text-sm">
                <p class="font-medium">{{ auth()->user()?->nama }}</p>
                <p class="text-xs text-slate-400">{{ auth()->user()?->username }}</p>
                <div class="mt-3 flex items-center gap-2">
                    <a href="{{ route('password.change') }}" class="text-xs text-slate-300 hover:text-white">Ganti Password</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-rose-300 hover:text-rose-200">Keluar</button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
