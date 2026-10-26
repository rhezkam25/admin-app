<?php

namespace App\Policies;

use App\Models\PayrollPeriod;
use App\Models\User;

class PayrollPeriodPolicy
{
    public function viewAny(User $pengguna): bool
    {
        return $pengguna->isAdmin();
    }

    public function close(User $pengguna, PayrollPeriod $periode): bool
    {
        return $pengguna->isAdmin();
    }

    public function reopen(User $pengguna, PayrollPeriod $periode): bool
    {
        return $pengguna->isSuperAdmin();
    }
}
