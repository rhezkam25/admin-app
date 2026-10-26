<?php

namespace App\Enums;

enum JenisTransaksiSaldoCuti: string
{
    case Hak = 'hak';
    case CarryOver = 'carry_over';
    case Pemakaian = 'pemakaian';
    case Penyesuaian = 'penyesuaian';
    case Kedaluwarsa = 'kedaluwarsa';
    case Pembatalan = 'pembatalan';

    public function label(): string
    {
        return match ($this) {
            self::Hak => 'Hak Cuti',
            self::CarryOver => 'Carry Over',
            self::Pemakaian => 'Pemakaian',
            self::Penyesuaian => 'Penyesuaian',
            self::Kedaluwarsa => 'Kedaluwarsa',
            self::Pembatalan => 'Pembatalan',
        };
    }
}
