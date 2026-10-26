<?php

namespace App\Enums;

enum JenisHari: string
{
    case HariKerja = 'hari_kerja';
    case AkhirPekan = 'akhir_pekan';
    case HariLibur = 'hari_libur';

    public function label(): string
    {
        return match ($this) {
            self::HariKerja => 'Hari Kerja',
            self::AkhirPekan => 'Akhir Pekan',
            self::HariLibur => 'Hari Libur',
        };
    }

    public function memakaiTarifLibur(): bool
    {
        return $this !== self::HariKerja;
    }
}
