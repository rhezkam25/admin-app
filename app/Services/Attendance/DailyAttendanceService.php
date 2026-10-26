<?php

namespace App\Services\Attendance;

use App\Enums\JenisHari;
use App\Enums\StatusAbsensi;
use App\Enums\StatusPermintaan;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\Holiday\HolidayService;
use App\Services\Period\PeriodService;
use App\Services\Schedule\ScheduleService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class DailyAttendanceService
{
    public function __construct(
        private readonly HolidayService $holidayService,
        private readonly ScheduleService $scheduleService,
        private readonly PeriodService $periodService,
    ) {}

    public function finalisasi(CarbonInterface|string $tanggal, bool $paksa = false): int
    {
        $tanggal = CarbonImmutable::parse($tanggal)->startOfDay();
        $periode = $this->periodService->periodeUntuk($tanggal);

        if (! $paksa && ! $this->periodService->terbuka($periode)) {
            return 0;
        }

        $jumlah = 0;

        Employee::query()
            ->aktif()
            ->chunkById(100, function ($daftarPegawai) use ($tanggal, &$jumlah): void {
                foreach ($daftarPegawai as $pegawai) {
                    if ($this->finalisasiPegawai($pegawai, $tanggal)) {
                        $jumlah++;
                    }
                }
            });

        return $jumlah;
    }

    public function finalisasiHariSebelumnya(): int
    {
        return $this->finalisasi(CarbonImmutable::parse(now())->subDay());
    }

    private function finalisasiPegawai(Employee $pegawai, CarbonImmutable $tanggal): bool
    {
        $absensi = Attendance::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', $tanggal->toDateString())
            ->first();

        $jenisHari = $this->holidayService->jenisHari($tanggal);

        if ($absensi !== null) {
            if ($absensi->jam_masuk !== null) {
                if ($absensi->jam_pulang === null) {
                    $absensi->update([
                        'belum_lengkap' => true,
                        'status' => StatusAbsensi::BelumLengkap,
                    ]);
                }

                if ($absensi->jenis_hari !== $jenisHari) {
                    $absensi->update(['jenis_hari' => $jenisHari]);
                }

                return false;
            }

            if (! in_array($absensi->status, [StatusAbsensi::Alpa, StatusAbsensi::Libur, StatusAbsensi::HariLibur], true)) {
                return false;
            }

            $cuti = $this->cutiDisetujui($pegawai, $tanggal);
            $status = $this->statusTanggal($pegawai, $tanggal, $jenisHari, $cuti);

            $absensi->update([
                'jenis_hari' => $jenisHari,
                'status' => $status,
            ]);

            return false;
        }

        $cuti = $this->cutiDisetujui($pegawai, $tanggal);
        $status = $this->statusTanggal($pegawai, $tanggal, $jenisHari, $cuti);

        Attendance::query()->create([
            'pegawai_id' => $pegawai->id,
            'tanggal_kerja' => $tanggal->toDateString(),
            'jenis_hari' => $jenisHari,
            'status' => $status,
            'belum_lengkap' => false,
            'catatan' => $cuti !== null ? 'Dibuat otomatis dari pengajuan cuti/izin.' : null,
        ]);

        return true;
    }

    private function statusTanggal(
        Employee $pegawai,
        CarbonImmutable $tanggal,
        JenisHari $jenisHari,
        ?LeaveRequest $cuti,
    ): StatusAbsensi {
        if ($cuti !== null) {
            return $this->statusDariCuti($cuti->leaveType?->kode);
        }

        if ($jenisHari === JenisHari::HariLibur) {
            return StatusAbsensi::HariLibur;
        }

        if (! $this->scheduleService->adalahHariKerja($pegawai, $tanggal)) {
            return StatusAbsensi::Libur;
        }

        return StatusAbsensi::Alpa;
    }

    private function cutiDisetujui(Employee $pegawai, CarbonImmutable $tanggal): ?LeaveRequest
    {
        return LeaveRequest::query()
            ->with('leaveType')
            ->where('pegawai_id', $pegawai->id)
            ->where('status', StatusPermintaan::Disetujui)
            ->whereDate('tanggal_mulai', '<=', $tanggal->toDateString())
            ->whereDate('tanggal_selesai', '>=', $tanggal->toDateString())
            ->first();
    }

    public function sinkronkanCuti(LeaveRequest $pengajuan): void
    {
        $pegawai = $pengajuan->employee;

        if ($pegawai === null) {
            return;
        }

        $status = $this->statusDariCuti($pengajuan->leaveType?->kode);
        $mulai = CarbonImmutable::parse($pengajuan->tanggal_mulai);
        $selesai = CarbonImmutable::parse($pengajuan->tanggal_selesai);

        for ($tanggal = $mulai; $tanggal->lessThanOrEqualTo($selesai); $tanggal = $tanggal->addDay()) {
            if (! $this->scheduleService->adalahHariKerja($pegawai, $tanggal)) {
                continue;
            }

            $absensi = Attendance::query()
                ->where('pegawai_id', $pegawai->id)
                ->whereDate('tanggal_kerja', $tanggal->toDateString())
                ->first();

            if ($absensi === null) {
                Attendance::query()->create([
                    'pegawai_id' => $pegawai->id,
                    'tanggal_kerja' => $tanggal->toDateString(),
                    'jenis_hari' => $this->holidayService->jenisHari($tanggal),
                    'status' => $status,
                    'belum_lengkap' => false,
                    'catatan' => 'Disinkronkan dari pengajuan cuti/izin yang disetujui.',
                ]);

                continue;
            }

            if ($absensi->jam_masuk === null) {
                $absensi->update([
                    'status' => $status,
                    'catatan' => 'Disinkronkan dari pengajuan cuti/izin yang disetujui.',
                ]);

                continue;
            }

            $anomali = $absensi->tanda_anomali ?? [];

            if (! in_array('hadir_saat_cuti', $anomali, true)) {
                $anomali[] = 'hadir_saat_cuti';
            }

            $absensi->update(['tanda_anomali' => $anomali]);
        }
    }

    public function batalkanSinkronisasiCuti(LeaveRequest $pengajuan): void
    {
        $pegawai = $pengajuan->employee;

        if ($pegawai === null) {
            return;
        }

        $statusCuti = [StatusAbsensi::Cuti, StatusAbsensi::Sakit, StatusAbsensi::Izin, StatusAbsensi::DinasLuar];
        $mulai = CarbonImmutable::parse($pengajuan->tanggal_mulai);
        $selesai = CarbonImmutable::parse($pengajuan->tanggal_selesai);

        for ($tanggal = $mulai; $tanggal->lessThanOrEqualTo($selesai); $tanggal = $tanggal->addDay()) {
            $absensi = Attendance::query()
                ->where('pegawai_id', $pegawai->id)
                ->whereDate('tanggal_kerja', $tanggal->toDateString())
                ->first();

            if ($absensi === null || $absensi->jam_masuk !== null) {
                continue;
            }

            if (in_array($absensi->status, $statusCuti, true)) {
                $absensi->update([
                    'status' => StatusAbsensi::Alpa,
                    'catatan' => 'Pengajuan cuti/izin dibatalkan; menunggu koreksi HR.',
                ]);
            }
        }
    }

    private function statusDariCuti(?string $kode): StatusAbsensi
    {
        return match ($kode) {
            'cuti_tahunan' => StatusAbsensi::Cuti,
            'sakit' => StatusAbsensi::Sakit,
            'izin' => StatusAbsensi::Izin,
            'dinas_luar' => StatusAbsensi::DinasLuar,
            default => StatusAbsensi::Cuti,
        };
    }
}
