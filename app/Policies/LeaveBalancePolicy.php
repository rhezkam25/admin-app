<?php

namespace App\Policies;

use App\Models\LeaveBalance;
use App\Models\User;

class LeaveBalancePolicy
{
    public function view(User $pengguna, LeaveBalance $saldo): bool
    {
        if ($pengguna->isAdmin()) {
            return true;
        }

        if ($pengguna->pegawai_id === null) {
            return false;
        }

        return $pengguna->pegawai_id === $saldo->pegawai_id
            || $pengguna->pegawai_id === $saldo->employee?->atasan_id;
    }

    public function adjust(User $pengguna, LeaveBalance $saldo): bool
    {
        return $pengguna->isAdmin();
    }
}
