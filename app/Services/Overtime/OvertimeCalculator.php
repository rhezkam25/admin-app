<?php

namespace App\Services\Overtime;

use App\Enums\JenisHari;
use App\Enums\SegmenLembur;
use App\Support\Overtime\HasilHitungLembur;
use App\Support\Overtime\HasilSegmenLembur;
use App\Support\Overtime\InputHitungLembur;
use Carbon\CarbonImmutable;

class OvertimeCalculator
{
    public function hitung(InputHitungLembur $masukan): HasilHitungLembur
    {
        $tanggal = $masukan->tanggal->startOfDay();
        $awalHari = $tanggal->setTime(0, 1);
        $akhirHari = $tanggal->setTime(23, 59);

        if ($masukan->jenisHari === JenisHari::HariKerja) {
            return $this->hitungHariKerja($masukan, $awalHari, $akhirHari);
        }

        return $this->hitungHariLibur($masukan, $awalHari, $akhirHari);
    }

    private function hitungHariKerja(
        InputHitungLembur $masukan,
        CarbonImmutable $awalHari,
        CarbonImmutable $akhirHari,
    ): HasilHitungLembur {
        $tanggal = $masukan->tanggal->startOfDay();
        $jamMulaiKerja = $tanggal->setTimeFromTimeString($masukan->jamMulaiKerja);
        $jamSelesaiKerja = $tanggal->setTimeFromTimeString($masukan->jamSelesaiKerja);
        $jamSoreMulai = $jamSelesaiKerja->addMinutes($masukan->tenggangMenit);

        $menitPagiAktual = $this->irisanMenit($masukan->jamMasuk, $masukan->jamPulang, $awalHari, $jamMulaiKerja);
        $menitSoreAktual = $this->irisanMenit($masukan->jamMasuk, $masukan->jamPulang, $jamSoreMulai, $akhirHari);

        $disetujuiPagi = max(0, $masukan->menitPagiDisetujui);
        $disetujuiSore = max(0, $masukan->menitSoreDisetujui);

        $dibayarPagi = min($menitPagiAktual, $disetujuiPagi);
        $dibayarSore = min($menitSoreAktual, $disetujuiSore);

        $nilaiPagi = $this->nilaiSen($dibayarPagi, $masukan->tarifHariKerjaSen);
        $nilaiSore = $this->nilaiSen($dibayarSore, $masukan->tarifHariKerjaSen);

        $segmen = [
            new HasilSegmenLembur(
                segmen: SegmenLembur::Pagi,
                menitAktual: $menitPagiAktual,
                menitDisetujui: $disetujuiPagi,
                menitDibayar: $dibayarPagi,
                tarifSen: $masukan->tarifHariKerjaSen,
                nilaiSen: $nilaiPagi,
            ),
            new HasilSegmenLembur(
                segmen: SegmenLembur::Sore,
                menitAktual: $menitSoreAktual,
                menitDisetujui: $disetujuiSore,
                menitDibayar: $dibayarSore,
                tarifSen: $masukan->tarifHariKerjaSen,
                nilaiSen: $nilaiSore,
            ),
        ];

        return new HasilHitungLembur(
            menitPagiAktual: $menitPagiAktual,
            menitSoreAktual: $menitSoreAktual,
            menitLiburAktual: 0,
            menitAktual: $menitPagiAktual + $menitSoreAktual,
            menitDisetujui: $disetujuiPagi + $disetujuiSore,
            menitDibayar: $dibayarPagi + $dibayarSore,
            nilaiKotorSen: $nilaiPagi + $nilaiSore,
            segmen: $segmen,
            snapshot: $this->snapshot($masukan, [
                'jam_mulai_kerja' => $masukan->jamMulaiKerja,
                'jam_selesai_kerja' => $masukan->jamSelesaiKerja,
                'jam_sore_mulai' => $jamSoreMulai->format('H:i'),
            ]),
        );
    }

    private function hitungHariLibur(
        InputHitungLembur $masukan,
        CarbonImmutable $awalHari,
        CarbonImmutable $akhirHari,
    ): HasilHitungLembur {
        $menitAktual = $this->irisanMenit($masukan->jamMasuk, $masukan->jamPulang, $awalHari, $akhirHari);

        if ($menitAktual > $masukan->ambangIstirahatLibur) {
            $menitAktual = max(0, $menitAktual - $masukan->menitIstirahatLibur);
        }

        $disetujui = max(0, $masukan->menitLiburDisetujui);
        $dibayar = min($menitAktual, $disetujui);
        $nilai = $this->nilaiSen($dibayar, $masukan->tarifLiburSen);

        $segmen = [
            new HasilSegmenLembur(
                segmen: SegmenLembur::Libur,
                menitAktual: $menitAktual,
                menitDisetujui: $disetujui,
                menitDibayar: $dibayar,
                tarifSen: $masukan->tarifLiburSen,
                nilaiSen: $nilai,
            ),
        ];

        return new HasilHitungLembur(
            menitPagiAktual: 0,
            menitSoreAktual: 0,
            menitLiburAktual: $menitAktual,
            menitAktual: $menitAktual,
            menitDisetujui: $disetujui,
            menitDibayar: $dibayar,
            nilaiKotorSen: $nilai,
            segmen: $segmen,
            snapshot: $this->snapshot($masukan, [
                'istirahat_libur_dipotong' => $menitAktual > 0
                    ? max(0, $this->irisanMenit($masukan->jamMasuk, $masukan->jamPulang, $awalHari, $akhirHari) - $menitAktual)
                    : 0,
            ]),
        );
    }

    private function irisanMenit(
        ?CarbonImmutable $mulai,
        ?CarbonImmutable $selesai,
        CarbonImmutable $jendelaMulai,
        CarbonImmutable $jendelaSelesai,
    ): int {
        if ($mulai === null || $selesai === null || $selesai->lessThanOrEqualTo($mulai)) {
            return 0;
        }

        $awal = $mulai->greaterThan($jendelaMulai) ? $mulai : $jendelaMulai;
        $akhir = $selesai->lessThan($jendelaSelesai) ? $selesai : $jendelaSelesai;

        if ($akhir->lessThanOrEqualTo($awal)) {
            return 0;
        }

        return (int) $awal->diffInMinutes($akhir);
    }

    private function nilaiSen(int $menit, int $tarifSen): int
    {
        if ($menit <= 0 || $tarifSen <= 0) {
            return 0;
        }

        return intdiv($menit * $tarifSen + 30, 60);
    }

    /**
     * @param  array<string, mixed>  $tambahan
     * @return array<string, mixed>
     */
    private function snapshot(InputHitungLembur $masukan, array $tambahan = []): array
    {
        return array_merge([
            'jenis_hari' => $masukan->jenisHari->value,
            'tarif_hari_kerja_sen' => $masukan->tarifHariKerjaSen,
            'tarif_libur_sen' => $masukan->tarifLiburSen,
            'tenggang_menit' => $masukan->tenggangMenit,
            'menit_istirahat_libur' => $masukan->menitIstirahatLibur,
            'ambang_istirahat_libur' => $masukan->ambangIstirahatLibur,
        ], $tambahan);
    }
}
