<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk — Absensi Pegawai' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-full items-center justify-center p-6 text-slate-800 antialiased">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Absensi Pegawai</h1>
            <p class="mt-1 text-sm text-slate-500">Masuk menggunakan username atau NIP.</p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            {{ $slot }}
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
            Akses hanya dari jaringan internal yang diizinkan.
        </p>
    </div>

    @livewireScripts
</body>
</html>
