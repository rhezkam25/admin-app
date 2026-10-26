<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pengaturan</h1>
        <p class="mt-1 text-sm text-slate-500">Kebijakan lembur, absensi, dan cuti. Perubahan langsung memengaruhi perhitungan bulan berjalan.</p>
    </div>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @error('periode')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</div>
    @enderror

    <form wire:submit="simpan" class="space-y-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Lembur</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tarif Hari Kerja (USD/jam)</label>
                    <input type="number" step="0.01" wire:model="tarifHariKerja" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('tarifHariKerja') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tarif Libur/Akhir Pekan (USD/jam)</label>
                    <input type="number" step="0.01" wire:model="tarifLibur" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('tarifLibur') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Batas Bulanan (% gaji pokok)</label>
                    <input type="number" step="0.01" wire:model="batasPersen" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('batasPersen') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tenggang Setelah Pulang (menit)</label>
                    <input type="number" wire:model="tenggangMenit" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Istirahat Libur (menit)</label>
                    <input type="number" wire:model="istirahatLiburMenit" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Ambang Potong Istirahat (menit)</label>
                    <input type="number" wire:model="ambangIstirahatLiburMenit" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Absensi</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Toleransi Terlambat (menit)</label>
                    <input type="number" wire:model="toleransiTerlambatMenit" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Cuti Tahunan</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Hak Cuti per Tahun (hari)</label>
                    <input type="number" wire:model="hakCutiHari" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Maksimum Carry Over (hari)</label>
                    <input type="number" wire:model="maksCarryOverHari" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Maksimum Saldo (hari)</label>
                    <input type="number" wire:model="maksSaldoCutiHari" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Simpan Pengaturan</button>
        </div>
    </form>
</div>
