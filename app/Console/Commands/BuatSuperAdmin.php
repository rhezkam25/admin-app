<?php

namespace App\Console\Commands;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Console\Command;

class BuatSuperAdmin extends Command
{
    protected $signature = 'akun:buat-super-admin';

    protected $description = 'Membuat akun Super Admin pertama dengan password sementara.';

    public function handle(): int
    {
        $username = (string) $this->ask('Username Super Admin');
        $nama = (string) $this->ask('Nama lengkap');
        $password = (string) $this->secret('Password sementara (minimal 12 karakter)');

        if (strlen($username) < 3) {
            $this->error('Username minimal 3 karakter.');

            return self::FAILURE;
        }

        if (strlen($password) < 12) {
            $this->error('Password minimal 12 karakter.');

            return self::FAILURE;
        }

        if (User::query()->where('username', $username)->exists()) {
            $this->error('Username sudah digunakan.');

            return self::FAILURE;
        }

        $pengguna = User::query()->create([
            'username' => $username,
            'nama' => $nama !== '' ? $nama : $username,
            'password' => $password,
            'peran' => Peran::SuperAdmin,
            'aktif' => true,
            'wajib_ganti_password' => true,
        ]);

        $this->info('Akun Super Admin berhasil dibuat.');
        $this->line('Username: '.$pengguna->username);
        $this->line('Pengguna wajib mengganti password saat login pertama.');

        return self::SUCCESS;
    }
}
