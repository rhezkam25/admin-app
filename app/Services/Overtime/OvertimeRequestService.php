<?php

namespace App\Services\Overtime;

use App\Enums\JenisHari;
use App\Enums\SegmenLembur;
use App\Enums\StatusPermintaan;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRecord;
use App\Models\OvertimeRequest;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\PermintaanNotification;
use App\Services\Audit\AuditLogger;
use App\Services\Holiday\HolidayService;
use App\Services\Period\PeriodService;
use App\Services\Schedule\ScheduleService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OvertimeRequestService
{
    public function __construct(
        private readonly HolidayService $holidayService,
        private readonly ScheduleService $scheduleService,
        private readonly PeriodService $periodService,
        private readonly OvertimePeriodService $overtimePeriodService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function ajukan(
        Employee $pegawai,
        CarbonInterface|string $tanggal,
        SegmenLembur $segmen,
        ?string $rencanaMulai,
        ?string $rencanaSelesai,
        int $menitDiajukan,
        string $alasan,
        ?User $pelaku = null,
    ): OvertimeRequest {
        $tanggal = CarbonImmutable::parse($tanggal)->startOfDay();

        $this->periodService->pastikanTerbuka($tanggal);

        if (! $pegawai->isActive()) {
            throw ValidationException::withMessages([
                'pengajuan_lembur' => 'Pegawai tidak aktif.',
            ]);
        }

        $jenisHari = $this->holidayService->jenisHari($tanggal);
        $this->validasiSegmen($jenisHari, $segmen);

        if ($menitDiajukan <= 0 || $menitDiajukan > 1440) {
            throw ValidationException::withMessages([
                'menitDiajukan' => 'Durasi lembur harus antara 1 dan 1440 menit.',
            ]);
        }

        $adaCuti = LeaveRequest::query()
            ->where('pegawai_id', $pegawai->id)
            ->where('status', StatusPermintaan::Disetujui)
            ->whereDate('tanggal_mulai', '<=', $tanggal->toDateString())
            ->whereDate('tanggal_selesai', '>=', $tanggal->toDateString())
            ->exists();

        if ($adaCuti) {
            throw ValidationException::withMessages([
                'pengajuan_lembur' => 'Tidak dapat mengajukan lembur pada tanggal cuti/izin yang disetujui.',
            ]);
        }

        $tumpangTindih = OvertimeRequest::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', $tanggal->toDateString())
            ->where('segmen', $segmen)
            ->whereIn('status', [StatusPermintaan::Diajukan, StatusPermintaan::Disetujui])
            ->exists();

        if ($tumpangTindih) {
            throw ValidationException::withMessages([
                'pengajuan_lembur' => 'Sudah ada pengajuan lembur pada tanggal dan segmen yang sama.',
            ]);
        }

        $this->validasiJendela($pegawai, $tanggal, $segmen, $rencanaMulai, $rencanaSelesai);

        $catatan = OvertimeRecord::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', $tanggal->toDateString())
            ->first();

        $pengajuan = DB::transaction(function () use ($pegawai, $tanggal, $segmen, $rencanaMulai, $rencanaSelesai, $menitDiajukan, $alasan, $catatan): OvertimeRequest {
            return OvertimeRequest::query()->create([
                'pegawai_id' => $pegawai->id,
                'catatan_lembur_id' => $catatan?->id,
                'tanggal_kerja' => $tanggal->toDateString(),
                'segmen' => $segmen,
                'rencana_mulai' => $rencanaMulai,
                'rencana_selesai' => $rencanaSelesai,
                'menit_diajukan' => $menitDiajukan,
                'menit_disetujui' => 0,
                'status' => StatusPermintaan::Diajukan,
                'alasan' => $alasan,
            ]);
        });

        $this->auditLogger->catat(
            $pelaku ?? $pegawai->user,
            'lembur.ajukan',
            $pengajuan,
            [],
            [
                'pegawai_id' => $pegawai->id,
                'tanggal_kerja' => $tanggal->toDateString(),
                'segmen' => $segmen->value,
                'menit_diajukan' => $menitDiajukan,
            ],
        );

        $pegawai->supervisor?->user?->notify(new PermintaanNotification(
            'Pengajuan lembur baru',
            "{$pegawai->nama} mengajukan lembur {$segmen->label()} pada {$tanggal->format('d/m/Y')} selama {$menitDiajukan} menit.",
            route('overtime'),
        ));

        return $pengajuan;
    }

    public function setujui(
        OvertimeRequest $pengajuan,
        User $peninjau,
        ?int $menitDisetujui = null,
        ?string $alasanOverride = null,
    ): OvertimeRequest {
        $this->pastikanMenunggu($pengajuan);
        $this->pastikanBukanPersetujuanSendiri($pengajuan, $peninjau);
        $this->periodService->pastikanTerbuka($pengajuan->tanggal_kerja);

        $menit = $menitDisetujui ?? $pengajuan->menit_diajukan;
        $menit = max(0, min($menit, $pengajuan->menit_diajukan));

        DB::transaction(function () use ($pengajuan, $peninjau, $menit, $alasanOverride): void {
            $pengajuan->update([
                'status' => StatusPermintaan::Disetujui,
                'menit_disetujui' => $menit,
                'ditinjau_oleh' => $peninjau->id,
                'ditinjau_pada' => now(),
                'alasan_override' => $alasanOverride,
            ]);
        });

        $this->overtimePeriodService->hitungUlangTanggal($pengajuan->employee, $pengajuan->tanggal_kerja);

        $this->auditLogger->catat(
            $peninjau,
            'lembur.setujui',
            $pengajuan,
            ['status' => StatusPermintaan::Diajukan->value, 'menit_disetujui' => 0],
            ['status' => StatusPermintaan::Disetujui->value, 'menit_disetujui' => $menit],
            $alasanOverride,
        );

        $pengajuan->employee?->user?->notify(new PermintaanNotification(
            'Pengajuan lembur disetujui',
            "Pengajuan lembur Anda pada {$pengajuan->tanggal_kerja->format('d/m/Y')} disetujui sebanyak {$menit} menit.",
            route('overtime'),
        ));

        return $pengajuan->fresh();
    }

    public function tolak(OvertimeRequest $pengajuan, User $peninjau, string $alasan): OvertimeRequest
    {
        $this->pastikanMenunggu($pengajuan);
        $this->pastikanBukanPersetujuanSendiri($pengajuan, $peninjau);
        $this->periodService->pastikanTerbuka($pengajuan->tanggal_kerja);

        DB::transaction(function () use ($pengajuan, $peninjau, $alasan): void {
            $pengajuan->update([
                'status' => StatusPermintaan::Ditolak,
                'menit_disetujui' => 0,
                'ditinjau_oleh' => $peninjau->id,
                'ditinjau_pada' => now(),
                'alasan_penolakan' => $alasan,
            ]);
        });

        $this->overtimePeriodService->hitungUlangTanggal($pengajuan->employee, $pengajuan->tanggal_kerja);

        $this->auditLogger->catat(
            $peninjau,
            'lembur.tolak',
            $pengajuan,
            ['status' => StatusPermintaan::Diajukan->value],
            ['status' => StatusPermintaan::Ditolak->value],
            $alasan,
        );

        $pengajuan->employee?->user?->notify(new PermintaanNotification(
            'Pengajuan lembur ditolak',
            "Pengajuan lembur Anda ditolak. Alasan: {$alasan}",
            route('overtime'),
        ));

        return $pengajuan->fresh();
    }

    public function batalkan(OvertimeRequest $pengajuan, User $pelaku, ?string $alasan = null): OvertimeRequest
    {
        if (in_array($pengajuan->status, [StatusPermintaan::Ditolak, StatusPermintaan::Dibatalkan], true)) {
            throw ValidationException::withMessages([
                'pengajuan_lembur' => 'Pengajuan ini sudah tidak dapat dibatalkan.',
            ]);
        }

        $this->periodService->pastikanTerbuka($pengajuan->tanggal_kerja);

        $statusLama = $pengajuan->status;

        DB::transaction(function () use ($pengajuan, $alasan): void {
            $pengajuan->update([
                'status' => StatusPermintaan::Dibatalkan,
                'alasan_override' => $alasan,
            ]);
        });

        $this->overtimePeriodService->hitungUlangTanggal($pengajuan->employee, $pengajuan->tanggal_kerja);

        $this->auditLogger->catat(
            $pelaku,
            'lembur.batalkan',
            $pengajuan,
            ['status' => $statusLama->value],
            ['status' => StatusPermintaan::Dibatalkan->value],
            $alasan,
        );

        return $pengajuan->fresh();
    }

    private function validasiSegmen(JenisHari $jenisHari, SegmenLembur $segmen): void
    {
        if ($jenisHari === JenisHari::HariKerja && ! in_array($segmen, [SegmenLembur::Pagi, SegmenLembur::Sore], true)) {
            throw ValidationException::withMessages([
                'segmen' => 'Pada hari kerja, pilih segmen pagi atau sore.',
            ]);
        }

        if ($jenisHari !== JenisHari::HariKerja && $segmen !== SegmenLembur::Libur) {
            throw ValidationException::withMessages([
                'segmen' => 'Pada akhir pekan atau hari libur, gunakan segmen libur.',
            ]);
        }
    }

    private function validasiJendela(
        Employee $pegawai,
        CarbonImmutable $tanggal,
        SegmenLembur $segmen,
        ?string $rencanaMulai,
        ?string $rencanaSelesai,
    ): void {
        if ($rencanaMulai === null || $rencanaSelesai === null) {
            return;
        }

        $mulai = CarbonImmutable::parse($tanggal->toDateString().' '.$rencanaMulai);
        $selesai = CarbonImmutable::parse($tanggal->toDateString().' '.$rencanaSelesai);

        if ($selesai->lessThanOrEqualTo($mulai)) {
            throw ValidationException::withMessages([
                'rencanaSelesai' => 'Waktu selesai harus setelah waktu mulai.',
            ]);
        }

        $awalHari = $tanggal->setTime(0, 1);
        $akhirHari = $tanggal->setTime(23, 59);

        if ($segmen === SegmenLembur::Pagi) {
            $jamMulaiKerja = $this->scheduleService->jamMulai($pegawai, $tanggal) ?? '09:00';
            $akhirPagi = CarbonImmutable::parse($tanggal->toDateString().' '.$jamMulaiKerja);

            if ($mulai->lessThan($awalHari) || $selesai->greaterThan($akhirPagi)) {
                throw ValidationException::withMessages([
                    'rencanaMulai' => 'Lembur pagi harus berada dalam jendela 00.01 sampai jam mulai kerja.',
                ]);
            }
        }

        if ($segmen === SegmenLembur::Sore) {
            $jamSelesaiKerja = $this->scheduleService->jamSelesai($pegawai, $tanggal) ?? '17:30';
            $tenggang = (int) Setting::get('lembur.tenggang_menit', 30);
            $mulaiSore = CarbonImmutable::parse($tanggal->toDateString().' '.$jamSelesaiKerja)->addMinutes($tenggang);

            if ($mulai->lessThan($mulaiSore) || $selesai->greaterThan($akhirHari)) {
                throw ValidationException::withMessages([
                    'rencanaMulai' => 'Lembur sore dimulai setelah tenggang pulang dan berakhir paling lambat 23.59.',
                ]);
            }
        }

        if ($segmen === SegmenLembur::Libur) {
            if ($mulai->lessThan($awalHari) || $selesai->greaterThan($akhirHari)) {
                throw ValidationException::withMessages([
                    'rencanaMulai' => 'Lembur libur harus berada dalam tanggal yang sama, 00.01 sampai 23.59.',
                ]);
            }
        }
    }

    private function pastikanMenunggu(OvertimeRequest $pengajuan): void
    {
        if ($pengajuan->status !== StatusPermintaan::Diajukan) {
            throw ValidationException::withMessages([
                'pengajuan_lembur' => 'Hanya pengajuan berstatus diajukan yang dapat diproses.',
            ]);
        }
    }

    private function pastikanBukanPersetujuanSendiri(OvertimeRequest $pengajuan, User $peninjau): void
    {
        if ($peninjau->pegawai_id !== null && $peninjau->pegawai_id === $pengajuan->pegawai_id) {
            throw ValidationException::withMessages([
                'pengajuan_lembur' => 'Pemohon tidak dapat menyetujui atau menolak pengajuannya sendiri.',
            ]);
        }
    }
}
