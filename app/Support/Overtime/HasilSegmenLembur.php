<?php

namespace App\Support\Overtime;

use App\Enums\SegmenLembur;

final readonly class HasilSegmenLembur
{
    public function __construct(
        public SegmenLembur $segmen,
        public int $menitAktual,
        public int $menitDisetujui,
        public int $menitDibayar,
        public int $tarifSen,
        public int $nilaiSen,
    ) {}

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'segmen' => $this->segmen->value,
            'menit_aktual' => $this->menitAktual,
            'menit_disetujui' => $this->menitDisetujui,
            'menit_dibayar' => $this->menitDibayar,
            'tarif_sen' => $this->tarifSen,
            'nilai_sen' => $this->nilaiSen,
        ];
    }
}
