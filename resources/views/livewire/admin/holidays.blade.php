<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Hari Libur</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola kalender libur Indonesia dan Malaysia yang diakui perusahaan.</p>
    </div>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @error('periode')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">{{ $idEdit === null ? 'Tambah Hari Libur' : 'Ubah Hari Libur' }}</h2>
                @if ($idEdit !== null)
                    <button wire:click="$set('idEdit', null)" class="text-xs text-slate-500 underline">Batal ubah</button>
                @endif
            </div>

            <form wire:submit="simpan" class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tanggal</label>
                    <input type="date" wire:model="tanggal" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('tanggal') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Negara</label>
                    <select wire:model="negara" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="ID">Indonesia</option>
                        <option value="MY">Malaysia</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Nama Libur</label>
                    <input type="text" wire:model="nama" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('nama') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jenis</label>
                    <input type="text" wire:model="jenis" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="flex items-end gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="diamati" class="rounded border-slate-300"> Diamati
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="aktif" class="rounded border-slate-300"> Aktif
                    </label>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Simpan</button>
                </div>
            </form>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Impor CSV</h2>
            <p class="mt-1 text-xs text-slate-500">Format: tanggal,negara,nama,jenis. Contoh: 2026-08-17,ID,Hari Kemerdekaan,nasional</p>
            <form wire:submit="impor" class="mt-4 space-y-3">
                <input type="file" wire:model="berkasCsv" accept=".csv,text/csv" class="block w-full text-sm">
                @error('berkasCsv') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Impor</button>
            </form>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Negara</th>
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Jenis</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarHariLibur as $hariLibur)
                        <tr>
                            <td class="px-5 py-3">{{ $hariLibur->tanggal?->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">{{ $hariLibur->negara?->label() }}</td>
                            <td class="px-5 py-3">{{ $hariLibur->nama }}</td>
                            <td class="px-5 py-3">{{ $hariLibur->jenis }}</td>
                            <td class="px-5 py-3">{{ $hariLibur->aktif ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="px-5 py-3">
                                <div class="flex gap-2">
                                    <button wire:click="edit({{ $hariLibur->id }})" class="text-xs text-slate-700 underline">Ubah</button>
                                    @if ($hariLibur->aktif)
                                        <button wire:click="nonaktifkan({{ $hariLibur->id }})" class="text-xs text-rose-700 underline">Nonaktifkan</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-6 text-center text-slate-500">Belum ada hari libur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
