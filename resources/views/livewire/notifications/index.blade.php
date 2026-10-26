<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Notifikasi</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $jumlahBelumDibaca }} notifikasi belum dibaca.</p>
        </div>
        @if ($jumlahBelumDibaca > 0)
            <button wire:click="tandaiSemuaDibaca" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Tandai semua dibaca
            </button>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <ul class="divide-y divide-slate-100">
            @forelse ($daftarNotifikasi as $notifikasi)
                <li class="flex items-start justify-between gap-4 px-5 py-4 {{ $notifikasi->read_at === null ? 'bg-slate-50' : '' }}">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            {{ $notifikasi->data['judul'] ?? 'Notifikasi' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-600">{{ $notifikasi->data['pesan'] ?? '' }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $notifikasi->created_at?->translatedFormat('d M Y H:i') }}</p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-2">
                        @if (! empty($notifikasi->data['url']))
                            <a href="{{ $notifikasi->data['url'] }}" class="text-xs font-medium text-slate-700 underline">Buka</a>
                        @endif
                        @if ($notifikasi->read_at === null)
                            <button wire:click="tandaiDibaca('{{ $notifikasi->id }}')" class="text-xs text-slate-500 underline">Tandai dibaca</button>
                        @endif
                    </div>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-500">Belum ada notifikasi.</li>
            @endforelse
        </ul>
    </div>
</div>
