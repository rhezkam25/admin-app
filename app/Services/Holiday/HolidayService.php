<?php

namespace App\Services\Holiday;

use App\Enums\JenisHari;
use App\Models\Holiday;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class HolidayService
{
    public function jenisHari(CarbonInterface $tanggal): JenisHari
    {
        if ($this->adalahHariLibur($tanggal)) {
            return JenisHari::HariLibur;
        }

        if ($tanggal->isSaturday() || $tanggal->isSunday()) {
            return JenisHari::AkhirPekan;
        }

        return JenisHari::HariKerja;
    }

    public function adalahHariLibur(CarbonInterface $tanggal): bool
    {
        return Holiday::query()
            ->aktif()
            ->padaTanggal($tanggal->toDateString())
            ->exists();
    }

    public function hariLiburPada(CarbonInterface $tanggal): Collection
    {
        return Holiday::query()
            ->aktif()
            ->padaTanggal($tanggal->toDateString())
            ->get();
    }

    /**
     * @return Collection<int, Holiday>
     */
    public function hariLiburAntara(CarbonInterface $mulai, CarbonInterface $selesai): Collection
    {
        return Holiday::query()
            ->aktif()
            ->whereDate('tanggal', '>=', $mulai->toDateString())
            ->whereDate('tanggal', '<=', $selesai->toDateString())
            ->orderBy('tanggal')
            ->get();
    }

    public function adalahAkhirPekan(CarbonInterface $tanggal): bool
    {
        return $tanggal->isSaturday() || $tanggal->isSunday();
    }
}
