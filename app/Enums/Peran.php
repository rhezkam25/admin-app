<?php

namespace App\Enums;

enum Peran: string
{
    case SuperAdmin = 'super_admin';
    case AdminHr = 'admin_hr';
    case Atasan = 'atasan';
    case Pegawai = 'pegawai';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminHr => 'Admin HR',
            self::Atasan => 'Atasan',
            self::Pegawai => 'Pegawai',
        };
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::SuperAdmin, self::AdminHr], true);
    }
}
