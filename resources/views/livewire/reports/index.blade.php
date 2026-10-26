<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Laporan</h1>
            <p class="mt-1 text-sm text-slate-500">Rekap absensi, lembur, dan cuti sesuai hak akses.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('reports.attendance.excel', ['dari' => $dari, 'sampai' => $sampai, 'departemen_id' => $departemenId]) }}" class="rounded-lg bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Absensi Excel</a>
            <a href="{{ route('reports.attendance.pdf', ['dari' => $dari, 'sampai' => $sampai, 'departemen_id' => $departemenId]) }}" class="rounded-lg bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Absensi PDF</a>
            <a href="{{ route('reports.overtime.excel', ['periode' => $periode, 'departemen_id' => $departemenId]) }}" class="rounded-lg bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Lembur Excel</a>
            <a href="{{ route('reports.overtime.pdf', ['periode' => $periode, 'departemen_id' => $departemenId]) }}" class="rounded-lg bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Lembur PDF</a>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Dari</label>
                <input type="date" wire:model.live="dari" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Sampai</label>
                <input type="date" wire:model.live="sampai" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Periode Lembur</label>
                <input type="month" wire:model.live="periode" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Departemen</label>
                <select wire:model.live="departemenId" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    @foreach ($departemen as $item)
                        <option value="{{ $item->id }}">{{ $item->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Rekap Absensi</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2">Pegawai</th>
                        <th class="px-4 py-2">Hadir</th>
                        <th class="px-4 py-2">Alpa</th>
                        <th class="px-4 py-2">Cuti</th>
                        <th class="px-4 py-2">Sakit</th>
                        <th class="px-4 py-2">Izin</th>
                        <th class="px-4 py-2">Dinas Luar</th>
                        <th class="px-4 py-2">Terlambat</th>
                        <th class="px-4 py-2">Pulang Cepat</th>
                        <th class="px-4 py-2">Belum Lengkap</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rekapAbsensi as $baris)
                        <tr>
                            <td class="px-4 py-2">
                                <p class="font-medium text-slate-900">{{ $baris['pegawai']?->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $baris['pegawai']?->nip }}</p>
                            </td>
                            <td class="px-4 py-2">{{ $baris['hadir'] }}</td>
                            <td class="px-4 py-2">{{ $baris['alpa'] }}</td>
                            <td class="px-4 py-2">{{ $baris['cuti'] }}</td>
                            <td class="px-4 py-2">{{ $baris['sakit'] }}</td>
                            <td class="px-4 py-2">{{ $baris['izin'] }}</td>
                            <td class="px-4 py-2">{{ $baris['dinas_luar'] }}</td>
                            <td class="px-4 py-2">{{ $baris['terlambat'] }} mnt</td>
                            <td class="px-4 py-2">{{ $baris['pulang_cepat'] }} mnt</td>
                            <td class="px-4 py-2">{{ $baris['belum_lengkap'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-6 text-center text-slate-500">Tidak ada data absensi pada rentang ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Rekap Lembur {{ $periode }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2">Pegawai</th>
                        <th class="px-4 py-2">Total Menit</th>
                        <th class="px-4 py-2">Kotor</th>
                        <th class="px-4 py-2">Batas</th>
                        <th class="px-4 py-2">Dibayar</th>
                        <th class="px-4 py-2">Hangus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rekapLembur as $baris)
                        <tr>
                            <td class="px-4 py-2">
                                <p class="font-medium text-slate-900">{{ $baris->employee?->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $baris->employee?->nip }}</p>
                            </td>
                            <td class="px-4 py-2">{{ $baris->total_menit }} mnt</td>
                            @if ($pengguna?->isAdmin() || $pengguna?->isPegawai())
                                <td class="px-4 py-2">USD {{ number_format($baris->nilai_kotor_sen / 100, 2, ',', '.') }}</td>
                                <td class="px-4 py-2">USD {{ number_format($baris->batas_sen / 100, 2, ',', '.') }}</td>
                                <td class="px-4 py-2">USD {{ number_format($baris->nilai_dibayar_sen / 100, 2, ',', '.') }}</td>
                                <td class="px-4 py-2">USD {{ number_format($baris->nilai_hangus_sen / 100, 2, ',', '.') }}</td>
                            @else
                                <td colspan="4" class="px-4 py-2 text-xs text-slate-400">Nominal tidak ditampilkan untuk peran atasan.</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada ringkasan lembur pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Riwayat Cuti/Izin {{ now()->year }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2">Pegawai</th>
                        <th class="px-4 py-2">Jenis</th>
                        <th class="px-4 py-2">Tanggal</th>
                        <th class="px-4 py-2">Hari</th>
                        <th class="px-4 py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rekapCuti as $cuti)
                        <tr>
                            <td class="px-4 py-2">{{ $cuti->employee?->nama }}</td>
                            <td class="px-4 py-2">{{ $cuti->leaveType?->nama }}</td>
                            <td class="px-4 py-2">{{ $cuti->tanggal_mulai?->format('d/m/Y') }}–{{ $cuti->tanggal_selesai?->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">{{ $cuti->jumlah_hari_kerja }}</td>
                            <td class="px-4 py-2">{{ $cuti->status?->label() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada pengajuan cuti/izin tahun ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
