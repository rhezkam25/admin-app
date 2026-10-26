<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $pengguna): bool
    {
        return true;
    }

    public function view(User $pengguna, LeaveRequest $pengajuan): bool
    {
        if ($pengguna->isAdmin()) {
            return true;
        }

        if ($pengguna->pegawai_id === null) {
            return false;
        }

        return $pengguna->pegawai_id === $pengajuan->pegawai_id
            || $pengguna->pegawai_id === $pengajuan->employee?->atasan_id;
    }

    public function create(User $pengguna): bool
    {
        return $pengguna->pegawai_id !== null;
    }

    public function approve(User $pengguna, LeaveRequest $pengajuan): bool
    {
        if ($pengguna->isAdmin()) {
            return $pengguna->pegawai_id !== $pengajuan->pegawai_id;
        }

        if ($pengguna->pegawai_id === null || $pengguna->pegawai_id === $pengajuan->pegawai_id) {
            return false;
        }

        return $pengajuan->employee?->atasan_id === $pengguna->pegawai_id;
    }

    public function cancel(User $pengguna, LeaveRequest $pengajuan): bool
    {
        return $pengguna->isAdmin() || $pengguna->pegawai_id === $pengajuan->pegawai_id;
    }
}
