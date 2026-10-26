<?php

namespace App\Support\Overtime;

final readonly class HasilHitungLembur
{
    /**
     * @param  list<HasilSegmenLembur>  $segmen
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public int $menitPagiAktual,
        public int $menitSoreAktual,
        public int $menitLiburAktual,
        public int $menitAktual,
        public int $menitDisetujui,
        public int $menitDibayar,
        public int $nilaiKotorSen,
        public array $segmen,
        public array $snapshot,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toRecordAttributes(): array
    {
        return [
            'menit_pagi_aktual' => $this->menitPagiAktual,
            'menit_sore_aktual' => $this->menitSoreAktual,
            'menit_libur_aktual' => $this->menitLiburAktual,
            'menit_aktual' => $this->menitAktual,
            'menit_disetujui' => $this->menitDisetujui,
            'menit_dibayar' => $this->menitDibayar,
            'nilai_kotor_sen' => $this->nilaiKotorSen,
        ];
    }
}
