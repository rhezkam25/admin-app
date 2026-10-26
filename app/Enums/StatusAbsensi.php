<?php

namespace App\Enums;

enum StatusAbsensi: string
{
    case Hadir = 'hadir';
    case Alpa = 'alpa';
    case Cuti = 'cuti';
    case Sakit = 'sakit';
    case Izin = 'izin';
    case DinasLuar = 'dinas_luar';
    case Libur = 'libur';
    case HariLibur = 'hari_libur';
    case BelumLengkap = 'belum_lengkap';

    public function label(): string
    {
        return match ($this) {
            self::Hadir => 'Hadir',
            self::Alpa => 'Alpa',
            self::Cuti => 'Cuti',
            self::Sakit => 'Sakit',
            self::Izin => 'Izin',
            self::DinasLuar => 'Dinas Luar',
            self::Libur => 'Libur',
            self::HariLibur => 'Hari Libur',
            self::BelumLengkap => 'Belum Lengkap',
        };
    }

    public function dihitungHadir(): bool
    {
        return $this === self::Hadir;
    }

    public function dihitungMangkir(): bool
    {
        return $this === self::Alpa;
    }
}
