<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Koreksi Absensi</h1>
            <p class="mt-1 text-sm text-slate-500">Perbaiki jam masuk/pulang atau status dengan alasan wajib.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <input type="date" wire:model.live="tanggal" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <span class="text-sm text-slate-500">{{ $tanggalTerpilih }}</span>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @error('absensi')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</div>
    @enderror

    @if ($absensiTerpilih !== null)
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">
                    Koreksi — {{ $absensiTerpilih->employee?->nama }} ({{ $absensiTerpilih->employee?->nip }})
                </h2>
                <button wire:click="$set('idAbsensi', null)" class="text-xs text-slate-500 underline">Tutup</button>
            </div>

            <form wire:submit="simpan" class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jam Masuk</label>
                    <input type="datetime-local" wire:model="jamMasuk" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('jamMasuk') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jam Pulang</label>
                    <input type="datetime-local" wire:model="jamPulang" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('jamPulang') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Status</label>
                    <select wire:model="status" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($pilihanStatus as $pilihan)
                            <option value="{{ $pilihan->value }}">{{ $pilihan->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Catatan</label>
                    <input type="text" wire:model="catatan" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Alasan Koreksi (wajib)</label>
                    <textarea wire:model="alasan" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                    @error('alasan') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Simpan Koreksi</button>
                </div>
            </form>
        </div>
    @endif

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <input type="search" wire:model.live.debounce.400ms="cari" placeholder="Cari nama atau NIP..." class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2">Pegawai</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Masuk</th>
                        <th class="px-4 py-2">Pulang</th>
                        <th class="px-4 py-2">Terlambat</th>
                        <th class="px-4 py-2">Pulang Cepat</th>
                        <th class="px-4 py-2">Catatan</th>
                        <th class="px-4 py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarAbsensi as $absensi)
                        <tr>
                            <td class="px-4 py-2">
                                <p class="font-medium text-slate-900">{{ $absensi->employee?->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $absensi->employee?->nip }}</p>
                            </td>
                            <td class="px-4 py-2">{{ $absensi->status?->label() }}</td>
                            <td class="px-4 py-2">{{ $absensi->jam_masuk?->format('H:i') ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $absensi->jam_pulang?->format('H:i') ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $absensi->menit_terlambat }} mnt</td>
                            <td class="px-4 py-2">{{ $absensi->menit_pulang_cepat }} mnt</td>
                            <td class="px-4 py-2 text-xs text-slate-500">{{ $absensi->catatan ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <button wire:click="pilih({{ $absensi->id }})" class="text-xs font-medium text-slate-700 underline">Koreksi</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">Tidak ada absensi pada tanggal ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
