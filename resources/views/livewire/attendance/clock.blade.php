<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Absensi</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $hariIni->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm ring-1 ring-slate-200">
            {{ $jenisHari?->label() ?? 'Hari Kerja' }}
        </div>
    </div>

    @if ($pegawai === null)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Akun ini tidak terhubung ke data pegawai. Hubungi HR untuk menautkan akun.
        </div>
    @else
        @error('absensi')
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</div>
        @enderror

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Status Hari Ini</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            {{ $absensi?->status?->label() ?? 'Belum clock-in' }}
                        </p>
                        @if ($absensi?->belum_lengkap)
                            <p class="mt-1 text-sm text-rose-600">Absensi belum lengkap. Lakukan clock-out atau hubungi HR.</p>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            wire:click="masuk"
                            wire:loading.attr="disabled"
                            @disabled($absensi !== null)
                            class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            Clock-in
                        </button>
                        <button
                            type="button"
                            wire:click="pulang"
                            wire:loading.attr="disabled"
                            @disabled($absensi === null || $absensi->jam_pulang !== null)
                            class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            Clock-out
                        </button>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">Jadwal</dt>
                        <dd class="mt-1 font-semibold text-slate-900">
                            {{ $jadwal?->jam_mulai ?? '—' }}–{{ $jadwal?->jam_selesai ?? '—' }}
                        </dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">Masuk</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $absensi?->jam_masuk?->format('H:i') ?? '—' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">Pulang</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $absensi?->jam_pulang?->format('H:i') ?? '—' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <dt class="text-xs text-slate-500">Terlambat</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $absensi?->menit_terlambat ?? 0 }} menit</dd>
                    </div>
                </dl>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Saldo Cuti</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ $saldoCuti?->sisa_hari ?? 0 }} <span class="text-sm font-medium text-slate-500">hari</span>
                    </p>
                    <a href="{{ route('leave') }}" class="mt-2 inline-block text-sm text-slate-700 underline">Kelola cuti</a>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Lembur Bulan Ini</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        USD {{ number_format(($ringkasanLembur?->nilai_dibayar_sen ?? 0) / 100, 2, ',', '.') }}
                    </p>
                    <a href="{{ route('overtime') }}" class="mt-2 inline-block text-sm text-slate-700 underline">Lihat lembur</a>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Riwayat 30 Hari Terakhir</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Jenis Hari</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Masuk</th>
                            <th class="px-5 py-3">Pulang</th>
                            <th class="px-5 py-3">Terlambat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($riwayat as $baris)
                            <tr>
                                <td class="px-5 py-3">{{ $baris->tanggal_kerja?->format('d/m/Y') }}</td>
                                <td class="px-5 py-3">{{ $baris->jenis_hari?->label() }}</td>
                                <td class="px-5 py-3">{{ $baris->status?->label() }}</td>
                                <td class="px-5 py-3">{{ $baris->jam_masuk?->format('H:i') ?? '—' }}</td>
                                <td class="px-5 py-3">{{ $baris->jam_pulang?->format('H:i') ?? '—' }}</td>
                                <td class="px-5 py-3">{{ $baris->menit_terlambat }} menit</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-6 text-center text-slate-500">Belum ada riwayat absensi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
