<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Lembur</h1>
            <p class="mt-1 text-sm text-slate-500">Ajukan lembur dan tinjau persetujuan tim.</p>
        </div>
        <div class="rounded-xl bg-white px-4 py-2 text-sm shadow-sm ring-1 ring-slate-200">
            {{ $jenisHari?->label() ?? 'Pilih tanggal' }}
            @if ($jamSoreMulai !== null)
                · sore mulai {{ $jamSoreMulai }}
            @endif
        </div>
    </div>

    @error('pengajuan_lembur')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</div>
    @enderror

    @if ($pegawai !== null)
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Ajukan Lembur</h2>
            <form wire:submit="ajukan" class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tanggal</label>
                    <input type="date" wire:model.live="tanggalLembur" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('tanggalLembur') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Segmen</label>
                    <select wire:model.live="segmen" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="pagi">Pagi</option>
                        <option value="sore">Sore</option>
                        <option value="libur">Libur</option>
                    </select>
                    @error('segmen') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Mulai</label>
                        <input type="time" wire:model.live="rencanaMulai" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('rencanaMulai') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Selesai</label>
                        <input type="time" wire:model.live="rencanaSelesai" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('rencanaSelesai') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Menit Diajukan</label>
                    <input type="number" wire:model="menitDiajukan" min="1" max="1440" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('menitDiajukan') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Alasan</label>
                    <textarea wire:model="alasanLembur" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                    @error('alasanLembur') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                        Kirim Pengajuan
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if ($idTinjau !== null)
        <div class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">
                {{ $aksiTinjau === 'setujui' ? 'Setujui' : 'Tolak' }} Pengajuan Lembur #{{ $idTinjau }}
            </h2>
            @if ($aksiTinjau === 'setujui')
                <div class="mt-3">
                    <label class="block text-sm font-medium text-slate-700">Menit Disetujui</label>
                    <input type="number" wire:model="menitTinjau" min="0" class="mt-1 block w-40 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('menitTinjau') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            @endif
            <textarea wire:model="alasanTinjau" rows="3" placeholder="Alasan (wajib untuk penolakan, opsional untuk override HR)" class="mt-3 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            @error('alasanTinjau') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            <div class="mt-3 flex gap-2">
                <button wire:click="prosesTinjau" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Proses</button>
                <button wire:click="$set('idTinjau', null)" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Batal</button>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Daftar Pengajuan Lembur</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Pegawai</th>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Segmen</th>
                        <th class="px-5 py-3">Diajukan</th>
                        <th class="px-5 py-3">Disetujui</th>
                        <th class="px-5 py-3">Aktual</th>
                        <th class="px-5 py-3">Dibayar</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarLembur as $lembur)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">{{ $lembur->employee?->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $lembur->employee?->nip }}</p>
                            </td>
                            <td class="px-5 py-3">{{ $lembur->tanggal_kerja?->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">{{ $lembur->segmen?->label() }}</td>
                            <td class="px-5 py-3">{{ $lembur->menit_diajukan }} mnt</td>
                            <td class="px-5 py-3">{{ $lembur->menit_disetujui }} mnt</td>
                            <td class="px-5 py-3">{{ $lembur->record?->menit_aktual ?? 0 }} mnt</td>
                            <td class="px-5 py-3">
                                USD {{ number_format(($lembur->record?->nilai_dibayar_sen ?? 0) / 100, 2, ',', '.') }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $lembur->status?->label() }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @can('approve', $lembur)
                                        @if ($lembur->status === \App\Enums\StatusPermintaan::Diajukan)
                                            <button wire:click="bukaTinjau({{ $lembur->id }}, 'setujui')" class="text-xs font-medium text-emerald-700 underline">Setujui</button>
                                            <button wire:click="bukaTinjau({{ $lembur->id }}, 'tolak')" class="text-xs font-medium text-rose-700 underline">Tolak</button>
                                        @endif
                                    @endcan
                                    @can('cancel', $lembur)
                                        @if (in_array($lembur->status, [\App\Enums\StatusPermintaan::Diajukan, \App\Enums\StatusPermintaan::Disetujui], true))
                                            <button wire:click="batalkan({{ $lembur->id }})" class="text-xs font-medium text-slate-600 underline">Batalkan</button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-6 text-center text-slate-500">Belum ada pengajuan lembur.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
