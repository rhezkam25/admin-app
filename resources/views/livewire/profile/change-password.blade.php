<x-layouts.guest title="Ganti Password">
    <div class="space-y-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">Ganti Password</h1>
            <p class="mt-1 text-sm text-slate-500">
                @if (auth()->user()?->wajib_ganti_password)
                    Anda wajib mengganti password sementara sebelum menggunakan aplikasi.
                @else
                    Gunakan minimal 12 karakter.
                @endif
            </p>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit="simpan" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Password Lama</label>
                <input type="password" wire:model="passwordLama" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('passwordLama') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Password Baru</label>
                <input type="password" wire:model="passwordBaru" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('passwordBaru') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Ulangi Password Baru</label>
                <input type="password" wire:model="passwordBaruKonfirmasi" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Simpan Password
            </button>
        </form>

        @unless (auth()->user()?->wajib_ganti_password)
            <a href="{{ route('dashboard') }}" class="block text-center text-xs text-slate-500 underline">Kembali ke dashboard</a>
        @endunless
    </div>
</x-layouts.guest>
