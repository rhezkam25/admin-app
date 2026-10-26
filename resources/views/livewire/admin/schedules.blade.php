<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Jadwal Kerja</h1>
            <p class="mt-1 text-sm text-slate-500">Atur hari kerja, jam masuk/pulang, dan istirahat.</p>
        </div>
        <button wire:click="buatBaru" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Jadwal Baru</button>
    </div>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @error('periode')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-4">
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Daftar Jadwal</h2>
            <ul class="mt-3 space-y-1 text-sm">
                @foreach ($daftarJadwal as $jadwal)
                    <li>
                        <button wire:click="pilihJadwal({{ $jadwal->id }})" class="w-full rounded-lg px-3 py-2 text-left hover:bg-slate-50 {{ $idJadwal === $jadwal->id ? 'bg-slate-100 font-semibold' : '' }}">
                            {{ $jadwal->nama }}
                            @if ($jadwal->bawaan)
                                <span class="ml-1 rounded bg-slate-200 px-1.5 py-0.5 text-[10px] uppercase text-slate-600">Bawaan</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-3">
            <form wire:submit="simpan" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nama Jadwal</label>
                        <input type="text" wire:model="nama" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('nama') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Zona Waktu</label>
                        <input type="text" wire:model="zonaWaktu" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="bawaan" class="rounded border-slate-300"> Jadwal bawaan
                        </label>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-3 py-2">Hari</th>
                                <th class="px-3 py-2">Hari Kerja</th>
                                <th class="px-3 py-2">Masuk</th>
                                <th class="px-3 py-2">Pulang</th>
                                <th class="px-3 py-2">Istirahat Mulai</th>
                                <th class="px-3 py-2">Istirahat Selesai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach (range(1, 7) as $hariKe)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-900">{{ \Carbon\CarbonImmutable::now()->startOfWeek()->addDays($hariKe - 1)->translatedFormat('l') }}</td>
                                    <td class="px-3 py-2">
                                        <input type="checkbox" wire:model="hari.{{ $hariKe }}.hari_kerja" class="rounded border-slate-300">
                                    </td>
                                    <td class="px-3 py-2"><input type="time" wire:model="hari.{{ $hariKe }}.jam_mulai" class="rounded-lg border border-slate-300 px-2 py-1 text-sm"></td>
                                    <td class="px-3 py-2"><input type="time" wire:model="hari.{{ $hariKe }}.jam_selesai" class="rounded-lg border border-slate-300 px-2 py-1 text-sm"></td>
                                    <td class="px-3 py-2"><input type="time" wire:model="hari.{{ $hariKe }}.istirahat_mulai" class="rounded-lg border border-slate-300 px-2 py-1 text-sm"></td>
                                    <td class="px-3 py-2"><input type="time" wire:model="hari.{{ $hariKe }}.istirahat_selesai" class="rounded-lg border border-slate-300 px-2 py-1 text-sm"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('hari') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>
