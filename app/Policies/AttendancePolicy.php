<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $pengguna): bool
    {
        return true;
    }

    public function view(User $pengguna, Attendance $absensi): bool
    {
        if ($pengguna->isAdmin()) {
            return true;
        }

        if ($pengguna->pegawai_id === null) {
            return false;
        }

        return $pengguna->pegawai_id === $absensi->pegawai_id
            || $pengguna->pegawai_id === $absensi->employee?->atasan_id;
    }

    public function correct(User $pengguna, Attendance $absensi): bool
    {
        return $pengguna->isAdmin();
    }
}
