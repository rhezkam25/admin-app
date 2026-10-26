<x-layouts.guest title="Masuk — Absensi Pegawai">
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div>
            <label for="username" class="block text-sm font-medium text-slate-700">Username / NIP</label>
            <input
                id="username"
                name="username"
                type="text"
                value="{{ old('username') }}"
                required
                autofocus
                autocomplete="username"
                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
            >
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
        >
            Masuk
        </button>
    </form>
</x-layouts.guest>
