<?php

namespace App\Enums;

enum StatusPeriode: string
{
    case Terbuka = 'terbuka';
    case Tertutup = 'tertutup';

    public function label(): string
    {
        return match ($this) {
            self::Terbuka => 'Terbuka',
            self::Tertutup => 'Tertutup',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Terbuka;
    }
}
