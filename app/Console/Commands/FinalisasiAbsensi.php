<?php

namespace App\Console\Commands;

use App\Services\Attendance\DailyAttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class FinalisasiAbsensi extends Command
{
    protected $signature = 'absensi:finalisasi {tanggal?} {--paksa : Finalisasi meskipun periode tertutup}';

    protected $description = 'Finalisasi status absensi harian untuk tanggal tertentu (default: kemarin).';

    public function handle(DailyAttendanceService $dailyAttendanceService): int
    {
        $tanggal = $this->argument('tanggal') !== null
            ? CarbonImmutable::parse($this->argument('tanggal'))
            : CarbonImmutable::parse(now())->subDay();

        $jumlah = $dailyAttendanceService->finalisasi($tanggal, (bool) $this->option('paksa'));

        $this->info("Finalisasi absensi {$tanggal->toDateString()} selesai. {$jumlah} record dibuat.");

        return self::SUCCESS;
    }
}
