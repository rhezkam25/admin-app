<?php

namespace App\Enums;

enum StatusPermintaan: string
{
    case Draf = 'draf';
    case Diajukan = 'diajukan';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Diajukan => 'Diajukan',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function masihMenunggu(): bool
    {
        return $this === self::Diajukan;
    }

    public function sudahFinal(): bool
    {
        return in_array($this, [self::Disetujui, self::Ditolak, self::Dibatalkan], true);
    }
}
