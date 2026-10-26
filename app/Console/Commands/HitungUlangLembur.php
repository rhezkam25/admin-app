<?php

namespace App\Console\Commands;

use App\Services\Overtime\OvertimePeriodService;
use Illuminate\Console\Command;

class HitungUlangLembur extends Command
{
    protected $signature = 'lembur:hitung-ulang {periode? : Format YYYY-MM}';

    protected $description = 'Hitung ulang ringkasan lembur bulanan untuk seluruh pegawai aktif.';

    public function handle(OvertimePeriodService $overtimePeriodService): int
    {
        $periode = $this->argument('periode') ?? now()->format('Y-m');
        $jumlah = $overtimePeriodService->hitungUlangSemua($periode);

        $this->info("Hitung ulang lembur periode {$periode} selesai untuk {$jumlah} pegawai.");

        return self::SUCCESS;
    }
}
