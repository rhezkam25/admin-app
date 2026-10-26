<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Periode Penggajian</h1>
        <p class="mt-1 text-sm text-slate-500">Tutup periode setelah semua pengajuan diputuskan. Hanya Super Admin yang dapat membuka kembali.</p>
    </div>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @error('periode')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</div>
    @enderror

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <form wire:submit="buat" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">Periode (YYYY-MM)</label>
                <input type="month" wire:model="periode" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('periode') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Pastikan Periode</button>
        </form>
    </div>

    @if ($idBuka !== null)
        <div class="rounded-2xl border border-amber-300 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Buka Kembali Periode</h2>
            <textarea wire:model="alasanBuka" rows="3" placeholder="Alasan wajib diisi" class="mt-3 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            @error('alasanBuka') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            <div class="mt-3 flex gap-2">
                <button wire:click="buka({{ $idBuka }})" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500">Buka Kembali</button>
                <button wire:click="$set('idBuka', null)" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Batal</button>
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Periode</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Ditutup Oleh</th>
                        <th class="px-5 py-3">Ditutup Pada</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarPeriode as $periode)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $periode->periode }}</td>
                            <td class="px-5 py-3">{{ $periode->status?->label() }}</td>
                            <td class="px-5 py-3">{{ $periode->closedBy?->nama ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $periode->ditutup_pada?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @if ($periode->isOpen())
                                        <button wire:click="tutup({{ $periode->id }})" class="text-xs font-medium text-rose-700 underline">Tutup</button>
                                    @else
                                        @can('reopen', $periode)
                                            <button wire:click="$set('idBuka', {{ $periode->id }})" class="text-xs font-medium text-amber-700 underline">Buka kembali</button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-6 text-center text-slate-500">Belum ada periode.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
