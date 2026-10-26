<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Audit Log</h1>
        <p class="mt-1 text-sm text-slate-500">Jejak perubahan sensitif. Log bersifat append-only dari aplikasi.</p>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">Cari peristiwa/alasan</label>
                <input type="search" wire:model.live.debounce.400ms="cari" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Dari</label>
                <input type="date" wire:model.live="dari" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Sampai</label>
                <input type="date" wire:model.live="sampai" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2">Waktu</th>
                        <th class="px-4 py-2">Pelaku</th>
                        <th class="px-4 py-2">Peristiwa</th>
                        <th class="px-4 py-2">Objek</th>
                        <th class="px-4 py-2">Alasan</th>
                        <th class="px-4 py-2">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarLog as $log)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-2">{{ $log->user?->nama ?? 'Sistem' }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $log->peristiwa }}</td>
                            <td class="px-4 py-2 text-xs text-slate-500">
                                {{ $log->objek_type !== null ? class_basename($log->objek_type).' #'.$log->objek_id : '—' }}
                            </td>
                            <td class="px-4 py-2">{{ $log->alasan ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-slate-500">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Tidak ada log pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
