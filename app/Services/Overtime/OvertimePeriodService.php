<?php

namespace App\Services\Overtime;

use App\Enums\SegmenLembur;
use App\Enums\StatusPermintaan;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OvertimePeriodSummary;
use App\Models\OvertimeRecord;
use App\Models\OvertimeRequest;
use App\Models\Setting;
use App\Services\Holiday\HolidayService;
use App\Services\Schedule\ScheduleService;
use App\Support\Overtime\InputHitungLembur;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OvertimePeriodService
{
    public function __construct(
        private readonly OvertimeCalculator $calculator,
        private readonly ScheduleService $scheduleService,
        private readonly HolidayService $holidayService,
    ) {}

    public function hitungUlangTanggal(Employee $pegawai, CarbonInterface $tanggal): ?OvertimeRecord
    {
        $catatan = $this->hitungUlangCatatan($pegawai, $tanggal);

        $this->hitungUlangPeriode($pegawai, CarbonImmutable::parse($tanggal)->format('Y-m'));

        return $catatan;
    }

    public function hitungUlangCatatan(Employee $pegawai, CarbonInterface $tanggal): ?OvertimeRecord
    {
        $tanggal = CarbonImmutable::parse($tanggal)->startOfDay();

        $absensi = Attendance::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', $tanggal->toDateString())
            ->first();

        if ($absensi === null || $absensi->jam_masuk === null || $absensi->isIncomplete()) {
            OvertimeRecord::query()
                ->where('pegawai_id', $pegawai->id)
                ->whereDate('tanggal_kerja', $tanggal->toDateString())
                ->delete();

            return null;
        }

        $jenisHari = $absensi->jenis_hari ?? $this->holidayService->jenisHari($tanggal);

        $permintaan = OvertimeRequest::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', $tanggal->toDateString())
            ->where('status', StatusPermintaan::Disetujui)
            ->get();

        if ($jenisHari->value === 'hari_kerja') {
            $menitPagi = $this->jumlahMenit($permintaan, SegmenLembur::Pagi);
            $menitSore = $this->jumlahMenit($permintaan, SegmenLembur::Sore);
            $menitLibur = 0;
        } else {
            $menitPagi = 0;
            $menitSore = 0;
            $menitLibur = (int) $permintaan->sum('menit_disetujui');
        }

        $input = new InputHitungLembur(
            tanggal: $tanggal,
            jenisHari: $jenisHari,
            jamMasuk: $absensi->jam_masuk->toImmutable(),
            jamPulang: $absensi->jam_pulang->toImmutable(),
            menitPagiDisetujui: $menitPagi,
            menitSoreDisetujui: $menitSore,
            menitLiburDisetujui: $menitLibur,
            tarifHariKerjaSen: (int) Setting::get('lembur.tarif_hari_kerja_sen', 600),
            tarifLiburSen: (int) Setting::get('lembur.tarif_libur_sen', 900),
            menitIstirahatLibur: (int) Setting::get('lembur.menit_istirahat_libur', 60),
            ambangIstirahatLibur: (int) Setting::get('lembur.ambang_istirahat_libur_menit', 360),
            jamMulaiKerja: $this->scheduleService->jamMulai($pegawai, $tanggal) ?? '09:00',
            jamSelesaiKerja: $this->scheduleService->jamSelesai($pegawai, $tanggal) ?? '17:30',
            tenggangMenit: (int) Setting::get('lembur.tenggang_menit', 30),
        );

        $hasil = $this->calculator->hitung($input);
        $tarifSen = $hasil->segmen[0]->tarifSen;

        $catatan = OvertimeRecord::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', $tanggal->toDateString())
            ->first() ?? new OvertimeRecord([
                'pegawai_id' => $pegawai->id,
                'tanggal_kerja' => $tanggal->toDateString(),
            ]);

        $catatan->fill(array_merge($hasil->toRecordAttributes(), [
            'absensi_id' => $absensi->id,
            'tarif_sen' => $tarifSen,
            'nilai_dibayar_sen' => 0,
            'nilai_hangus_sen' => 0,
            'snapshot_aturan' => $hasil->snapshot,
            'dihitung_pada' => now(),
        ]));

        $catatan->save();

        return $catatan;
    }

    public function hitungUlangPeriode(Employee $pegawai, string $periode): OvertimePeriodSummary
    {
        $mulai = CarbonImmutable::createFromFormat('Y-m', $periode)->startOfMonth();
        $akhir = $mulai->endOfMonth();

        $tanggal = Attendance::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', '>=', $mulai->toDateString())
            ->whereDate('tanggal_kerja', '<=', $akhir->toDateString())
            ->whereNotNull('jam_masuk')
            ->pluck('tanggal_kerja')
            ->merge(
                OvertimeRecord::query()
                    ->where('pegawai_id', $pegawai->id)
                    ->whereDate('tanggal_kerja', '>=', $mulai->toDateString())
                    ->whereDate('tanggal_kerja', '<=', $akhir->toDateString())
                    ->pluck('tanggal_kerja'),
            )
            ->map(fn ($nilai): string => CarbonImmutable::parse($nilai)->toDateString())
            ->unique()
            ->values();

        foreach ($tanggal as $hari) {
            $this->hitungUlangCatatan($pegawai, CarbonImmutable::parse($hari));
        }

        $catatan = OvertimeRecord::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', '>=', $mulai->toDateString())
            ->whereDate('tanggal_kerja', '<=', $akhir->toDateString())
            ->orderBy('tanggal_kerja')
            ->orderBy('id')
            ->get();

        $gaji = $pegawai->salaryOn($akhir);
        $gajiPokokSen = $gaji !== null ? $gaji->gaji_pokok_sen : 0;
        $persenBatas = (float) Setting::get('lembur.batas_persen', 28);
        $batasSen = (int) round($gajiPokokSen * $persenBatas / 100);
        $sisaBatas = $batasSen;

        $totalKotor = 0;
        $totalDibayar = 0;
        $totalHangus = 0;
        $totalMenit = 0;

        DB::transaction(function () use ($catatan, &$sisaBatas, &$totalKotor, &$totalDibayar, &$totalHangus, &$totalMenit): void {
            foreach ($catatan as $record) {
                $kotor = max(0, $record->nilai_kotor_sen);
                $dibayar = min($kotor, max(0, $sisaBatas));
                $hangus = $kotor - $dibayar;
                $sisaBatas -= $dibayar;

                $record->update([
                    'nilai_dibayar_sen' => $dibayar,
                    'nilai_hangus_sen' => $hangus,
                ]);

                $totalKotor += $kotor;
                $totalDibayar += $dibayar;
                $totalHangus += $hangus;
                $totalMenit += $record->menit_dibayar;
            }
        });

        return OvertimePeriodSummary::query()->updateOrCreate(
            [
                'pegawai_id' => $pegawai->id,
                'periode' => $periode,
            ],
            [
                'gaji_pokok_sen' => $gajiPokokSen,
                'batas_sen' => $batasSen,
                'nilai_kotor_sen' => $totalKotor,
                'nilai_dibayar_sen' => $totalDibayar,
                'nilai_hangus_sen' => $totalHangus,
                'total_menit' => $totalMenit,
                'snapshot_aturan' => [
                    'gaji_pokok_sen' => $gajiPokokSen,
                    'batas_persen' => $persenBatas,
                    'batas_sen' => $batasSen,
                    'dihitung_pada' => now()->toDateTimeString(),
                ],
            ],
        );
    }

    public function hitungUlangSemua(string $periode): int
    {
        $jumlah = 0;

        Employee::query()
            ->aktif()
            ->chunkById(100, function ($daftarPegawai) use ($periode, &$jumlah): void {
                foreach ($daftarPegawai as $pegawai) {
                    $this->hitungUlangPeriode($pegawai, $periode);
                    $jumlah++;
                }
            });

        return $jumlah;
    }

    public function hitungUlangBulan(CarbonInterface $tanggal): int
    {
        return $this->hitungUlangSemua(CarbonImmutable::parse($tanggal)->format('Y-m'));
    }

    private function jumlahMenit(Collection $permintaan, SegmenLembur $segmen): int
    {
        return (int) $permintaan
            ->where('segmen', $segmen)
            ->sum('menit_disetujui');
    }
}
