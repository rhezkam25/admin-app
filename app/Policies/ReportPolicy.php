<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $pengguna): bool
    {
        return $pengguna->isAdmin();
    }

    public function viewTeam(User $pengguna): bool
    {
        return $pengguna->isAdmin() || $pengguna->isAtasan();
    }

    public function viewOwn(User $pengguna): bool
    {
        return $pengguna->pegawai_id !== null;
    }
}
