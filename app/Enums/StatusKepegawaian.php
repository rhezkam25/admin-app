<?php

namespace App\Enums;

enum StatusKepegawaian: string
{
    case Aktif = 'aktif';
    case Nonaktif = 'nonaktif';
    case Resign = 'resign';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Nonaktif => 'Nonaktif',
            self::Resign => 'Resign',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Aktif;
    }
}
