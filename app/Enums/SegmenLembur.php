<?php

namespace App\Enums;

enum SegmenLembur: string
{
    case Pagi = 'pagi';
    case Sore = 'sore';
    case Libur = 'libur';

    public function label(): string
    {
        return match ($this) {
            self::Pagi => 'Pagi',
            self::Sore => 'Sore',
            self::Libur => 'Libur',
        };
    }
}
