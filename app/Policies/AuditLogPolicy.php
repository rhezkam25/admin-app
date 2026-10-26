<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $pengguna): bool
    {
        return $pengguna->isAdmin();
    }

    public function view(User $pengguna, AuditLog $log): bool
    {
        return $pengguna->isAdmin();
    }
}
