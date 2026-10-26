<?php

namespace App\Livewire\Profile;

use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Ganti Password')]
class ChangePassword extends Component
{
    public string $passwordLama = '';

    public string $passwordBaru = '';

    public string $passwordBaruKonfirmasi = '';

    public function simpan(): void
    {
        $pengguna = auth()->user();

        if ($pengguna === null) {
            return;
        }

        $this->validate([
            'passwordLama' => ['required', 'string'],
            'passwordBaru' => ['required', 'string', 'min:12', 'same:passwordBaruKonfirmasi'],
        ]);

        if (! Hash::check($this->passwordLama, $pengguna->password)) {
            throw ValidationException::withMessages([
                'passwordLama' => 'Password lama tidak sesuai.',
            ]);
        }

        $pengguna->update([
            'password' => $this->passwordBaru,
            'wajib_ganti_password' => false,
        ]);

        app(AuditLogger::class)->catat(
            $pengguna,
            'akun.ganti_password',
            $pengguna,
            [],
            [],
            null,
            request()->ip(),
            request()->userAgent(),
        );

        session()->flash('status', 'Password berhasil diperbarui.');
        $this->redirectRoute('dashboard');
    }

    public function render()
    {
        return view('livewire.profile.change-password');
    }
}
