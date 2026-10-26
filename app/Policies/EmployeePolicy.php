<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $pengguna): bool
    {
        return $pengguna->isAdmin();
    }

    public function view(User $pengguna, Employee $pegawai): bool
    {
        if ($pengguna->isAdmin()) {
            return true;
        }

        if ($pengguna->pegawai_id === null) {
            return false;
        }

        return $pengguna->pegawai_id === $pegawai->id
            || $pengguna->pegawai_id === $pegawai->atasan_id;
    }

    public function viewTeam(User $pengguna, Employee $pegawai): bool
    {
        return $pengguna->isAdmin()
            || ($pengguna->pegawai_id !== null && $pegawai->atasan_id === $pengguna->pegawai_id);
    }

    public function create(User $pengguna): bool
    {
        return $pengguna->isAdmin();
    }

    public function update(User $pengguna, Employee $pegawai): bool
    {
        return $pengguna->isAdmin();
    }

    public function delete(User $pengguna, Employee $pegawai): bool
    {
        return $pengguna->isAdmin();
    }

    public function viewSalary(User $pengguna, Employee $pegawai): bool
    {
        return $pengguna->isAdmin();
    }
}
