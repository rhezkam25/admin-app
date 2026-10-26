<?php

namespace App\Support\Overtime;

use App\Enums\JenisHari;
use Carbon\CarbonImmutable;

final readonly class InputHitungLembur
{
    public function __construct(
        public CarbonImmutable $tanggal,
        public JenisHari $jenisHari,
        public ?CarbonImmutable $jamMasuk,
        public ?CarbonImmutable $jamPulang,
        public int $menitPagiDisetujui = 0,
        public int $menitSoreDisetujui = 0,
        public int $menitLiburDisetujui = 0,
        public int $tarifHariKerjaSen = 600,
        public int $tarifLiburSen = 900,
        public int $menitIstirahatLibur = 60,
        public int $ambangIstirahatLibur = 360,
        public string $jamMulaiKerja = '09:00',
        public string $jamSelesaiKerja = '17:30',
        public int $tenggangMenit = 30,
    ) {}
}
