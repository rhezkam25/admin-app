<?php

namespace App\Services\Schedule;

use App\Models\Employee;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use App\Services\Holiday\HolidayService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ScheduleService
{
    public function __construct(private readonly HolidayService $holidayService) {}

    public function jadwalUntuk(Employee $pegawai): ?WorkSchedule
    {
        if ($pegawai->workSchedule !== null) {
            return $pegawai->workSchedule;
        }

        return WorkSchedule::query()
            ->where('aktif', true)
            ->where('bawaan', true)
            ->first();
    }

    public function hariUntuk(Employee $pegawai, CarbonInterface $tanggal): ?WorkScheduleDay
    {
        $jadwal = $this->jadwalUntuk($pegawai);

        if ($jadwal === null) {
            return null;
        }

        return $jadwal->days()
            ->where('hari_ke', $tanggal->isoWeekday())
            ->first();
    }

    public function adalahHariKerja(Employee $pegawai, CarbonInterface $tanggal): bool
    {
        if ($this->holidayService->adalahHariLibur($tanggal)) {
            return false;
        }

        $hari = $this->hariUntuk($pegawai, $tanggal);

        return $hari !== null && $hari->hari_kerja;
    }

    public function jamMulai(Employee $pegawai, CarbonInterface $tanggal): ?string
    {
        return $this->hariUntuk($pegawai, $tanggal)?->jam_mulai;
    }

    public function jamSelesai(Employee $pegawai, CarbonInterface $tanggal): ?string
    {
        return $this->hariUntuk($pegawai, $tanggal)?->jam_selesai;
    }

    public function jamSoreMulai(Employee $pegawai, CarbonInterface $tanggal, int $tenggangMenit = 30): ?string
    {
        $jamSelesai = $this->jamSelesai($pegawai, $tanggal);

        if ($jamSelesai === null) {
            return null;
        }

        $waktu = CarbonImmutable::parse($tanggal->toDateString().' '.$jamSelesai);

        return $waktu->addMinutes($tenggangMenit)->format('H:i');
    }

    public function menitIstirahat(Employee $pegawai, CarbonInterface $tanggal): int
    {
        $hari = $this->hariUntuk($pegawai, $tanggal);

        if ($hari?->istirahat_mulai === null || $hari->istirahat_selesai === null) {
            return 0;
        }

        $mulai = CarbonImmutable::parse($tanggal->toDateString().' '.$hari->istirahat_mulai);
        $selesai = CarbonImmutable::parse($tanggal->toDateString().' '.$hari->istirahat_selesai);

        return max(0, (int) $mulai->diffInMinutes($selesai));
    }

    public function hitungHariKerja(Employee $pegawai, CarbonInterface $mulai, CarbonInterface $selesai): int
    {
        $tanggal = CarbonImmutable::parse($mulai)->startOfDay();
        $akhir = CarbonImmutable::parse($selesai)->startOfDay();
        $jumlah = 0;

        while ($tanggal->lessThanOrEqualTo($akhir)) {
            if ($this->adalahHariKerja($pegawai, $tanggal)) {
                $jumlah++;
            }

            $tanggal = $tanggal->addDay();
        }

        return $jumlah;
    }

    public function waktuPadaTanggal(CarbonInterface $tanggal, string $jam): CarbonImmutable
    {
        return CarbonImmutable::parse($tanggal->toDateString().' '.$jam);
    }

    public function waktuMasukDiharapkan(Employee $pegawai, CarbonInterface $tanggal): ?CarbonImmutable
    {
        $jam = $this->jamMulai($pegawai, $tanggal);

        if ($jam === null) {
            return null;
        }

        return $this->waktuPadaTanggal($tanggal, $jam);
    }

    public function waktuPulangDiharapkan(Employee $pegawai, CarbonInterface $tanggal): ?CarbonImmutable
    {
        $jam = $this->jamSelesai($pegawai, $tanggal);

        if ($jam === null) {
            return null;
        }

        return $this->waktuPadaTanggal($tanggal, $jam);
    }
}
