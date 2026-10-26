<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Data Pegawai</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola master pegawai, status kerja, dan riwayat gaji.</p>
        </div>
        <input
            type="search"
            wire:model.live.debounce.400ms="cari"
            placeholder="Cari nama atau NIP..."
            class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm"
        >
    </div>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @if ($passwordSementara !== null)
        <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            Password sementara: <strong class="font-mono">{{ $passwordSementara }}</strong>.
            Tampilkan sekali ini dan minta pegawai menggantinya saat login pertama.
            <button wire:click="$set('passwordSementara', null)" class="ml-2 text-xs underline">Sembunyikan</button>
        </div>
    @endif

    @error('akun')
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ $message }}</div>
    @enderror

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">{{ $idEdit === null ? 'Tambah Pegawai' : 'Ubah Pegawai' }}</h2>
            @if ($idEdit !== null)
                <button wire:click="resetForm" class="text-xs text-slate-500 underline">Batal ubah</button>
            @endif
        </div>

        <form wire:submit="simpan" class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">NIP</label>
                <input type="text" wire:model="nip" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('nip') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Nama</label>
                <input type="text" wire:model="nama" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('nama') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Departemen</label>
                <select wire:model="departemenId" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">—</option>
                    @foreach ($departemen as $item)
                        <option value="{{ $item->id }}">{{ $item->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jabatan</label>
                <select wire:model="jabatanId" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">—</option>
                    @foreach ($jabatan as $item)
                        <option value="{{ $item->id }}">{{ $item->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Atasan Langsung</label>
                <select wire:model="atasanId" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">—</option>
                    @foreach ($calonAtasan as $item)
                        <option value="{{ $item->id }}">{{ $item->nama }} ({{ $item->nip }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Jadwal Kerja</label>
                <select wire:model="jadwalKerjaId" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">—</option>
                    @foreach ($jadwal as $item)
                        <option value="{{ $item->id }}">{{ $item->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Status Kepegawaian</label>
                <select wire:model="statusKepegawaian" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                    <option value="resign">Resign</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tanggal Masuk</label>
                    <input type="date" wire:model="tanggalMasuk" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('tanggalMasuk') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tanggal Keluar</label>
                    <input type="date" wire:model="tanggalKeluar" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Telepon</label>
                <input type="text" wire:model="telepon" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Alamat</label>
                <textarea wire:model="alamat" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                    {{ $idEdit === null ? 'Simpan Pegawai' : 'Simpan Perubahan' }}
                </button>
            </div>
        </form>
    </div>

    @if ($pegawaiGaji !== null)
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Riwayat Gaji — {{ $pegawaiGaji->nama }}</h2>
                <button wire:click="$set('idGajiPegawai', null)" class="text-xs text-slate-500 underline">Tutup</button>
            </div>

            <form wire:submit="simpanGaji" class="mt-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Berlaku Mulai</label>
                    <input type="date" wire:model="gajiBerlakuMulai" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Gaji Pokok (USD)</label>
                    <input type="number" step="0.01" wire:model="gajiPokok" class="mt-1 w-40 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('gajiPokok') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Simpan Gaji</button>
            </form>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2">Berlaku Mulai</th>
                            <th class="px-4 py-2">Gaji Pokok</th>
                            <th class="px-4 py-2">Mata Uang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($riwayatGaji as $gaji)
                            <tr>
                                <td class="px-4 py-2">{{ $gaji->berlaku_mulai?->format('d/m/Y') }}</td>
                                <td class="px-4 py-2">USD {{ number_format($gaji->gaji_pokok_sen / 100, 2, ',', '.') }}</td>
                                <td class="px-4 py-2">{{ $gaji->mata_uang }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-4 text-center text-slate-500">Belum ada riwayat gaji.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($pegawaiSaldo !== null)
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Saldo Cuti — {{ $pegawaiSaldo->nama }}</h2>
                <button wire:click="$set('idSaldoPegawai', null)" class="text-xs text-slate-500 underline">Tutup</button>
            </div>

            @if ($saldoTerpilih === null)
                <p class="mt-3 text-sm text-amber-700">Saldo cuti tahunan belum tersedia pada tahun pertama kerja.</p>
            @else
                <p class="mt-2 text-sm text-slate-600">
                    Sisa saat ini: <strong>{{ $saldoTerpilih->sisa_hari }} hari</strong>
                    (hak {{ $saldoTerpilih->hak_hari }}, carry over {{ $saldoTerpilih->carry_over_hari }},
                    terpakai {{ $saldoTerpilih->terpakai_hari }}, tertunda {{ $saldoTerpilih->tertunda_hari }}).
                </p>

                <form wire:submit="simpanSaldo" class="mt-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Perubahan (hari)</label>
                        <input type="number" wire:model="perubahanSaldoHari" class="mt-1 w-32 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('perubahanSaldoHari') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="min-w-64 flex-1">
                        <label class="block text-sm font-medium text-slate-700">Alasan</label>
                        <input type="text" wire:model="alasanSaldo" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @error('alasanSaldo') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Sesuaikan Saldo</button>
                </form>
            @endif
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">NIP</th>
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3">Departemen</th>
                        <th class="px-5 py-3">Atasan</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Akun</th>
                        <th class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarPegawai as $pegawai)
                        <tr>
                            <td class="px-5 py-3 font-mono text-xs">{{ $pegawai->nip }}</td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">{{ $pegawai->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $pegawai->position?->nama }}</p>
                            </td>
                            <td class="px-5 py-3">{{ $pegawai->department?->nama ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $pegawai->supervisor?->nama ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $pegawai->status_kepegawaian?->label() }}</td>
                            <td class="px-5 py-3">
                                @if ($pegawai->user !== null)
                                    <span class="text-xs text-emerald-700">{{ $pegawai->user->username }}</span>
                                    <button wire:click="resetPassword({{ $pegawai->id }})" class="ml-1 text-xs text-slate-600 underline">Reset password</button>
                                @else
                                    <button wire:click="buatAkun({{ $pegawai->id }})" class="text-xs text-slate-600 underline">Buat akun</button>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button wire:click="edit({{ $pegawai->id }})" class="text-xs text-slate-700 underline">Ubah</button>
                                    <button wire:click="pilihGaji({{ $pegawai->id }})" class="text-xs text-slate-700 underline">Gaji</button>
                                    <button wire:click="pilihSaldo({{ $pegawai->id }})" class="text-xs text-slate-700 underline">Saldo Cuti</button>
                                    @if ($pegawai->status_kepegawaian === \App\Enums\StatusKepegawaian::Aktif)
                                        <button wire:click="nonaktifkan({{ $pegawai->id }})" class="text-xs text-rose-700 underline">Nonaktifkan</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-6 text-center text-slate-500">Belum ada data pegawai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
