<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Cuti & Izin</h1>
            <p class="mt-1 text-sm text-slate-500">Ajukan cuti/izin dan tinjau pengajuan tim.</p>
        </div>
        @if ($saldo !== null)
            <div class="rounded-xl bg-white px-4 py-2 text-sm shadow-sm ring-1 ring-slate-200">
                Saldo cuti tahunan: <strong>{{ $saldo->sisa_hari }} hari</strong>
            </div>
        @endif
    </div>

    @error('pengajuan_cuti')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</div>
    @enderror

    @if ($pegawai !== null)
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Ajukan Cuti / Izin</h2>
            <form wire:submit="ajukan" class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jenis</label>
                    <select wire:model.live="jenisCutiId" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Pilih jenis</option>
                        @foreach ($jenisCuti as $jenis)
                            <option value="{{ $jenis->id }}">{{ $jenis->nama }}</option>
                        @endforeach
                    </select>
                    @error('jenisCutiId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Mulai</label>
                        <input type="date" wire:model.live="tanggalMulai" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('tanggalMulai') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Selesai</label>
                        <input type="date" wire:model.live="tanggalSelesai" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('tanggalSelesai') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Alasan</label>
                    <textarea wire:model="alasanCuti" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                    @error('alasanCuti') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Lampiran (opsional, maks. 5 MB)</label>
                    <input type="file" wire:model="lampiran" class="mt-1 block w-full text-sm">
                    @error('lampiran') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-end justify-between gap-3">
                    <div class="text-sm text-slate-600">
                        @if ($pratinjau !== null)
                            <p>Hari kerja: <strong>{{ $pratinjau['hari_kerja'] }}</strong></p>
                            @if ($pratinjau['pesan'] !== null)
                                <p class="text-rose-600">{{ $pratinjau['pesan'] }}</p>
                            @endif
                        @endif
                    </div>
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
                {{ $aksiTinjau === 'setujui' ? 'Setujui' : 'Tolak' }} Pengajuan #{{ $idTinjau }}
            </h2>
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
            <h2 class="text-sm font-semibold text-slate-900">Daftar Pengajuan</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Pegawai</th>
                        <th class="px-5 py-3">Jenis</th>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Hari</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarCuti as $cuti)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">{{ $cuti->employee?->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $cuti->employee?->nip }}</p>
                            </td>
                            <td class="px-5 py-3">{{ $cuti->leaveType?->nama }}</td>
                            <td class="px-5 py-3">{{ $cuti->tanggal_mulai?->format('d/m/Y') }}–{{ $cuti->tanggal_selesai?->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">{{ $cuti->jumlah_hari_kerja }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $cuti->status?->label() }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @can('approve', $cuti)
                                        @if ($cuti->status === \App\Enums\StatusPermintaan::Diajukan)
                                            <button wire:click="bukaTinjau({{ $cuti->id }}, 'setujui')" class="text-xs font-medium text-emerald-700 underline">Setujui</button>
                                            <button wire:click="bukaTinjau({{ $cuti->id }}, 'tolak')" class="text-xs font-medium text-rose-700 underline">Tolak</button>
                                        @endif
                                    @endcan
                                    @can('cancel', $cuti)
                                        @if (in_array($cuti->status, [\App\Enums\StatusPermintaan::Diajukan, \App\Enums\StatusPermintaan::Disetujui], true))
                                            <button wire:click="batalkan({{ $cuti->id }})" class="text-xs font-medium text-slate-600 underline">Batalkan</button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-6 text-center text-slate-500">Belum ada pengajuan cuti/izin.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
