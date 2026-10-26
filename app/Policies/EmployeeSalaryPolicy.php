<?php

namespace App\Policies;

use App\Models\EmployeeSalary;
use App\Models\User;

class EmployeeSalaryPolicy
{
    public function viewAny(User $pengguna): bool
    {
        return $pengguna->isAdmin();
    }

    public function view(User $pengguna, EmployeeSalary $gaji): bool
    {
        return $pengguna->isAdmin();
    }

    public function create(User $pengguna): bool
    {
        return $pengguna->isAdmin();
    }

    public function update(User $pengguna, EmployeeSalary $gaji): bool
    {
        return $pengguna->isAdmin();
    }

    public function delete(User $pengguna, EmployeeSalary $gaji): bool
    {
        return $pengguna->isAdmin();
    }
}
