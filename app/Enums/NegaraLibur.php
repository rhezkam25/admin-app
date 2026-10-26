<?php

namespace App\Enums;

enum NegaraLibur: string
{
    case Indonesia = 'ID';
    case Malaysia = 'MY';

    public function label(): string
    {
        return match ($this) {
            self::Indonesia => 'Indonesia',
            self::Malaysia => 'Malaysia',
        };
    }
}
