<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">
            Selamat datang, {{ $pengguna?->nama }}. Ringkasan hari ini dan periode {{ $periode }}.
        </p>
    </div>

    @if ($ringkasanHr !== null)
        <div class="flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <div>
                <label class="block text-xs font-medium uppercase tracking-wide text-slate-500">Tanggal</label>
                <input type="date" wire:model.live="tanggalDashboard" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium uppercase tracking-wide text-slate-500">Departemen</label>
                <select wire:model.live="departemenDashboard" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    @foreach ($departemenHr as $departemen)
                        <option value="{{ $departemen->id }}">{{ $departemen->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pegawai Aktif</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $ringkasanHr['total_pegawai'] }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Hadir</p>
                <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $ringkasanHr['hadir'] }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Terlambat</p>
                <p class="mt-2 text-3xl font-bold text-amber-600">{{ $ringkasanHr['terlambat'] }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Belum Lengkap</p>
                <p class="mt-2 text-3xl font-bold text-rose-600">{{ $ringkasanHr['belum_lengkap'] }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-sm">
            <a href="{{ route('admin.periods') }}" class="rounded-lg bg-white px-4 py-2 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">
                Status periode: <strong>{{ $periodePenggajian?->status?->label() ?? 'Belum dibuat' }}</strong>
            </a>
            <a href="{{ route('reports') }}" class="rounded-lg bg-white px-4 py-2 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Buka laporan</a>
        </div>
    @endif

    @if ($pegawai !== null)
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Absensi Hari Ini</p>
                @if ($absensiHariIni === null)
                    <p class="mt-2 text-lg font-semibold text-slate-900">Belum clock-in</p>
                    <a href="{{ route('attendance') }}" class="mt-3 inline-block text-sm font-medium text-slate-700 underline">Buka halaman absensi</a>
                @else
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $absensiHariIni->status?->label() }}</p>
                    <dl class="mt-3 space-y-1 text-sm text-slate-600">
                        <div class="flex justify-between"><dt>Masuk</dt><dd>{{ $absensiHariIni->jam_masuk?->format('H:i') ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt>Pulang</dt><dd>{{ $absensiHariIni->jam_pulang?->format('H:i') ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt>Terlambat</dt><dd>{{ $absensiHariIni->menit_terlambat }} menit</dd></div>
                    </dl>
                @endif
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Saldo Cuti Tahunan</p>
                @if ($saldoCuti === null)
                    <p class="mt-2 text-lg font-semibold text-slate-900">Belum tersedia</p>
                    <p class="mt-1 text-sm text-slate-500">Cuti tahunan baru tersedia setelah 12 bulan masa kerja.</p>
                @else
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $saldoCuti->sisa_hari }} hari</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Hak {{ $saldoCuti->hak_hari }} + carry over {{ $saldoCuti->carry_over_hari }}
                        − terpakai {{ $saldoCuti->terpakai_hari }} − tertunda {{ $saldoCuti->tertunda_hari }}
                    </p>
                    <a href="{{ route('leave') }}" class="mt-3 inline-block text-sm font-medium text-slate-700 underline">Ajukan cuti</a>
                @endif
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Lembur {{ $periode }}</p>
                @if ($ringkasanLembur === null)
                    <p class="mt-2 text-lg font-semibold text-slate-900">Belum ada perhitungan</p>
                @else
                    <p class="mt-2 text-3xl font-bold text-slate-900">USD {{ number_format($ringkasanLembur->nilai_dibayar_sen / 100, 2, ',', '.') }}</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Kotor USD {{ number_format($ringkasanLembur->nilai_kotor_sen / 100, 2, ',', '.') }},
                        batas USD {{ number_format($ringkasanLembur->batas_sen / 100, 2, ',', '.') }}
                    </p>
                @endif
                <a href="{{ route('overtime') }}" class="mt-3 inline-block text-sm font-medium text-slate-700 underline">Lihat lembur</a>
            </div>
        </div>
    @endif

    @if ($cutiMenunggu->isNotEmpty() || $lemburMenunggu->isNotEmpty())
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Cuti Menunggu Persetujuan</h2>
                @forelse ($cutiMenunggu as $cuti)
                    <div class="mt-3 flex items-center justify-between border-b border-slate-100 pb-2 text-sm last:border-0">
                        <div>
                            <p class="font-medium text-slate-900">{{ $cuti->employee?->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $cuti->leaveType?->nama }} · {{ $cuti->tanggal_mulai?->format('d/m/Y') }}–{{ $cuti->tanggal_selesai?->format('d/m/Y') }}</p>
                        </div>
                        <a href="{{ route('leave') }}" class="text-xs font-medium text-slate-700 underline">Tinjau</a>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-slate-500">Tidak ada pengajuan cuti yang menunggu.</p>
                @endforelse
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Lembur Menunggu Persetujuan</h2>
                @forelse ($lemburMenunggu as $lembur)
                    <div class="mt-3 flex items-center justify-between border-b border-slate-100 pb-2 text-sm last:border-0">
                        <div>
                            <p class="font-medium text-slate-900">{{ $lembur->employee?->nama }}</p>
                            <p class="text-xs text-slate-500">{{ $lembur->tanggal_kerja?->format('d/m/Y') }} · {{ $lembur->segmen?->label() }} · {{ $lembur->menit_diajukan }} menit</p>
                        </div>
                        <a href="{{ route('overtime') }}" class="text-xs font-medium text-slate-700 underline">Tinjau</a>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-slate-500">Tidak ada pengajuan lembur yang menunggu.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
