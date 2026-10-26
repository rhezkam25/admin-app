<?php

namespace App\Services\Leave;

use App\Enums\JenisTransaksiSaldoCuti;
use App\Enums\StatusPermintaan;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\PermintaanNotification;
use App\Services\Attendance\DailyAttendanceService;
use App\Services\Audit\AuditLogger;
use App\Services\Period\PeriodService;
use App\Services\Schedule\ScheduleService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveBalanceService
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly AuditLogger $auditLogger,
        private readonly PeriodService $periodService,
    ) {}

    public function tahunCutiMulai(Employee $pegawai, CarbonInterface|string $tanggal): ?CarbonImmutable
    {
        $masuk = CarbonImmutable::parse($pegawai->tanggal_masuk)->startOfDay();
        $ulangTahunPertama = $masuk->addYear();
        $tanggal = CarbonImmutable::parse($tanggal)->startOfDay();

        if ($tanggal->lessThan($ulangTahunPertama)) {
            return null;
        }

        $ulangTahunTahunIni = $this->ulangTahunPada($masuk, $tanggal->year);

        $kandidat = $tanggal->lessThan($ulangTahunTahunIni)
            ? $this->ulangTahunPada($masuk, $tanggal->year - 1)
            : $ulangTahunTahunIni;

        return $kandidat->lessThan($ulangTahunPertama) ? $ulangTahunPertama : $kandidat;
    }

    public function ulangTahunPada(CarbonImmutable $masuk, int $tahun): CarbonImmutable
    {
        $hariMaksimal = CarbonImmutable::create($tahun, $masuk->month, 1)->daysInMonth;
        $hari = min($masuk->day, $hariMaksimal);

        return CarbonImmutable::create($tahun, $masuk->month, $hari)->startOfDay();
    }

    public function ambilAtauBuatSaldo(Employee $pegawai, CarbonInterface|string $tanggal): ?LeaveBalance
    {
        $awal = $this->tahunCutiMulai($pegawai, $tanggal);

        if ($awal === null) {
            return null;
        }

        $saldo = LeaveBalance::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('awal_tahun_cuti', $awal->toDateString())
            ->first();

        if ($saldo !== null) {
            $this->hitungUlangSaldo($saldo);

            return $saldo->fresh();
        }

        $hakHari = (int) Setting::get('cuti.hak_hari', 12);
        $maksCarryOver = (int) Setting::get('cuti.maks_carry_over_hari', 6);
        $maksSaldo = (int) Setting::get('cuti.maks_saldo_hari', 18);
        $carryOver = min($maksCarryOver, max(0, $this->carryOverTahunSebelumnya($pegawai, $awal)));
        $totalAwal = min($maksSaldo, $hakHari + $carryOver);

        return DB::transaction(function () use ($pegawai, $awal, $hakHari, $carryOver, $totalAwal): LeaveBalance {
            $saldo = LeaveBalance::query()->create([
                'pegawai_id' => $pegawai->id,
                'awal_tahun_cuti' => $awal->toDateString(),
                'hak_hari' => $hakHari,
                'carry_over_hari' => $carryOver,
                'terpakai_hari' => 0,
                'tertunda_hari' => 0,
                'penyesuaian_hari' => 0,
                'sisa_hari' => $totalAwal,
                'kedaluwarsa_pada' => $awal->addYear()->toDateString(),
            ]);

            $this->catatTransaksi($saldo, JenisTransaksiSaldoCuti::Hak, $hakHari, null, 'Hak cuti tahunan.');

            if ($carryOver > 0) {
                $this->catatTransaksi($saldo, JenisTransaksiSaldoCuti::CarryOver, $carryOver, null, 'Carry over dari tahun cuti sebelumnya.');
            }

            return $saldo;
        });
    }

    /**
     * @return array{hari_kerja: int, saldo: LeaveBalance|null, cukup: bool, pesan: string|null}
     */
    public function pratinjau(
        Employee $pegawai,
        LeaveType $jenisCuti,
        CarbonInterface|string $mulai,
        CarbonInterface|string $selesai,
    ): array {
        $mulai = CarbonImmutable::parse($mulai)->startOfDay();
        $selesai = CarbonImmutable::parse($selesai)->startOfDay();

        if ($selesai->lessThan($mulai)) {
            return $this->hasilPratinjau(0, null, 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }

        $hariKerja = $this->scheduleService->hitungHariKerja($pegawai, $mulai, $selesai);

        if ($hariKerja === 0) {
            return $this->hasilPratinjau(0, null, 'Rentang tanggal tidak mengandung hari kerja.');
        }

        $saldo = $jenisCuti->memotong_saldo ? $this->ambilAtauBuatSaldo($pegawai, $mulai) : null;

        if ($jenisCuti->memotong_saldo && $saldo === null) {
            return $this->hasilPratinjau($hariKerja, null, 'Cuti tahunan belum tersedia pada tahun pertama kerja.');
        }

        if ($jenisCuti->memotong_saldo && $saldo->availableDays() < $hariKerja) {
            return $this->hasilPratinjau($hariKerja, $saldo, 'Saldo cuti tahunan tidak mencukupi.');
        }

        $tumpangTindih = LeaveRequest::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereIn('status', [StatusPermintaan::Diajukan, StatusPermintaan::Disetujui])
            ->whereDate('tanggal_mulai', '<=', $selesai->toDateString())
            ->whereDate('tanggal_selesai', '>=', $mulai->toDateString())
            ->exists();

        if ($tumpangTindih) {
            return $this->hasilPratinjau($hariKerja, $saldo, 'Rentang tanggal bertumpang tindih dengan pengajuan cuti/izin lain.');
        }

        return $this->hasilPratinjau($hariKerja, $saldo, null);
    }

    public function ajukan(
        Employee $pegawai,
        LeaveType $jenisCuti,
        CarbonInterface|string $mulai,
        CarbonInterface|string $selesai,
        string $alasan,
        ?string $lampiranPath = null,
        ?User $pelaku = null,
    ): LeaveRequest {
        $mulai = CarbonImmutable::parse($mulai)->startOfDay();
        $selesai = CarbonImmutable::parse($selesai)->startOfDay();

        $this->periodService->pastikanRentangTerbuka($mulai, $selesai);

        $pratinjau = $this->pratinjau($pegawai, $jenisCuti, $mulai, $selesai);

        if (! $pratinjau['cukup']) {
            throw ValidationException::withMessages([
                'pengajuan_cuti' => $pratinjau['pesan'] ?? 'Pengajuan cuti tidak valid.',
            ]);
        }

        $saldo = $pratinjau['saldo'];

        $pengajuan = DB::transaction(function () use ($pegawai, $jenisCuti, $mulai, $selesai, $alasan, $lampiranPath, $saldo, $pratinjau): LeaveRequest {
            $pengajuan = LeaveRequest::query()->create([
                'pegawai_id' => $pegawai->id,
                'jenis_cuti_id' => $jenisCuti->id,
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $selesai->toDateString(),
                'jumlah_hari_kerja' => $pratinjau['hari_kerja'],
                'alasan' => $alasan,
                'lampiran_path' => $lampiranPath,
                'status' => StatusPermintaan::Diajukan,
            ]);

            if ($jenisCuti->memotong_saldo && $saldo !== null) {
                $saldo->tertunda_hari += $pratinjau['hari_kerja'];
                $saldo->save();
                $this->hitungUlangSaldo($saldo);
            }

            return $pengajuan;
        });

        $this->auditLogger->catat(
            $pelaku ?? $pegawai->user,
            'cuti.ajukan',
            $pengajuan,
            [],
            [
                'pegawai_id' => $pegawai->id,
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $selesai->toDateString(),
                'hari_kerja' => $pratinjau['hari_kerja'],
            ],
        );

        $pegawai->supervisor?->user?->notify(new PermintaanNotification(
            'Pengajuan cuti/izin baru',
            "{$pegawai->nama} mengajukan {$jenisCuti->nama} untuk {$mulai->format('d/m/Y')} sampai {$selesai->format('d/m/Y')}.",
            route('leave'),
        ));

        return $pengajuan;
    }

    public function setujui(LeaveRequest $pengajuan, User $peninjau, ?string $alasanOverride = null): LeaveRequest
    {
        $this->pastikanMenunggu($pengajuan);
        $this->pastikanBukanPersetujuanSendiri($pengajuan, $peninjau);
        $this->periodService->pastikanRentangTerbuka($pengajuan->tanggal_mulai, $pengajuan->tanggal_selesai);

        DB::transaction(function () use ($pengajuan, $peninjau, $alasanOverride): void {
            $jenisCuti = $pengajuan->leaveType;
            $saldo = $jenisCuti?->memotong_saldo
                ? $this->ambilAtauBuatSaldo($pengajuan->employee, $pengajuan->tanggal_mulai)
                : null;

            if ($jenisCuti?->memotong_saldo === true && $saldo !== null) {
                $saldo->tertunda_hari = max(0, $saldo->tertunda_hari - $pengajuan->jumlah_hari_kerja);
                $saldo->terpakai_hari += $pengajuan->jumlah_hari_kerja;
                $saldo->save();
                $this->hitungUlangSaldo($saldo);
                $this->catatTransaksi(
                    $saldo,
                    JenisTransaksiSaldoCuti::Pemakaian,
                    -$pengajuan->jumlah_hari_kerja,
                    $pengajuan,
                    'Pemakaian cuti yang disetujui.',
                );
            }

            $pengajuan->update([
                'status' => StatusPermintaan::Disetujui,
                'ditinjau_oleh' => $peninjau->id,
                'ditinjau_pada' => now(),
                'alasan_override' => $alasanOverride,
            ]);
        });

        $this->auditLogger->catat(
            $peninjau,
            'cuti.setujui',
            $pengajuan,
            ['status' => StatusPermintaan::Diajukan->value],
            ['status' => StatusPermintaan::Disetujui->value],
            $alasanOverride,
        );

        $pengajuan->employee?->user?->notify(new PermintaanNotification(
            'Pengajuan cuti disetujui',
            "Pengajuan cuti/izin Anda untuk {$pengajuan->tanggal_mulai->format('d/m/Y')} sampai {$pengajuan->tanggal_selesai->format('d/m/Y')} telah disetujui.",
            route('leave'),
        ));

        app(DailyAttendanceService::class)->sinkronkanCuti($pengajuan);

        return $pengajuan->fresh();
    }

    public function tolak(LeaveRequest $pengajuan, User $peninjau, string $alasan): LeaveRequest
    {
        $this->pastikanMenunggu($pengajuan);
        $this->pastikanBukanPersetujuanSendiri($pengajuan, $peninjau);
        $this->periodService->pastikanRentangTerbuka($pengajuan->tanggal_mulai, $pengajuan->tanggal_selesai);

        DB::transaction(function () use ($pengajuan, $peninjau, $alasan): void {
            $jenisCuti = $pengajuan->leaveType;
            $saldo = $jenisCuti?->memotong_saldo
                ? $this->ambilAtauBuatSaldo($pengajuan->employee, $pengajuan->tanggal_mulai)
                : null;

            if ($jenisCuti?->memotong_saldo === true && $saldo !== null) {
                $saldo->tertunda_hari = max(0, $saldo->tertunda_hari - $pengajuan->jumlah_hari_kerja);
                $saldo->save();
                $this->hitungUlangSaldo($saldo);
            }

            $pengajuan->update([
                'status' => StatusPermintaan::Ditolak,
                'ditinjau_oleh' => $peninjau->id,
                'ditinjau_pada' => now(),
                'alasan_penolakan' => $alasan,
            ]);
        });

        $this->auditLogger->catat(
            $peninjau,
            'cuti.tolak',
            $pengajuan,
            ['status' => StatusPermintaan::Diajukan->value],
            ['status' => StatusPermintaan::Ditolak->value],
            $alasan,
        );

        $pengajuan->employee?->user?->notify(new PermintaanNotification(
            'Pengajuan cuti ditolak',
            "Pengajuan cuti/izin Anda ditolak. Alasan: {$alasan}",
            route('leave'),
        ));

        return $pengajuan->fresh();
    }

    public function batalkan(LeaveRequest $pengajuan, User $pelaku, ?string $alasan = null): LeaveRequest
    {
        if (in_array($pengajuan->status, [StatusPermintaan::Ditolak, StatusPermintaan::Dibatalkan], true)) {
            throw ValidationException::withMessages([
                'pengajuan_cuti' => 'Pengajuan ini sudah tidak dapat dibatalkan.',
            ]);
        }

        $this->periodService->pastikanRentangTerbuka($pengajuan->tanggal_mulai, $pengajuan->tanggal_selesai);

        $statusLama = $pengajuan->status;

        DB::transaction(function () use ($pengajuan, $statusLama, $alasan): void {
            $jenisCuti = $pengajuan->leaveType;
            $saldo = $jenisCuti?->memotong_saldo
                ? $this->ambilAtauBuatSaldo($pengajuan->employee, $pengajuan->tanggal_mulai)
                : null;

            if ($jenisCuti?->memotong_saldo === true && $saldo !== null) {
                if ($statusLama === StatusPermintaan::Diajukan) {
                    $saldo->tertunda_hari = max(0, $saldo->tertunda_hari - $pengajuan->jumlah_hari_kerja);
                }

                if ($statusLama === StatusPermintaan::Disetujui) {
                    $saldo->terpakai_hari = max(0, $saldo->terpakai_hari - $pengajuan->jumlah_hari_kerja);
                    $this->catatTransaksi(
                        $saldo,
                        JenisTransaksiSaldoCuti::Pembatalan,
                        $pengajuan->jumlah_hari_kerja,
                        $pengajuan,
                        'Pengembalian saldo karena pembatalan cuti.',
                    );
                }

                $saldo->save();
                $this->hitungUlangSaldo($saldo);
            }

            $pengajuan->update([
                'status' => StatusPermintaan::Dibatalkan,
                'alasan_override' => $alasan,
            ]);
        });

        $this->auditLogger->catat(
            $pelaku,
            'cuti.batalkan',
            $pengajuan,
            ['status' => $statusLama->value],
            ['status' => StatusPermintaan::Dibatalkan->value],
            $alasan,
        );

        if ($statusLama === StatusPermintaan::Disetujui) {
            app(DailyAttendanceService::class)->batalkanSinkronisasiCuti($pengajuan);
        }

        return $pengajuan->fresh();
    }

    public function sesuaikan(LeaveBalance $saldo, int $perubahanHari, User $pelaku, string $alasan): LeaveBalance
    {
        DB::transaction(function () use ($saldo, $perubahanHari, $pelaku, $alasan): void {
            $saldo->penyesuaian_hari += $perubahanHari;
            $saldo->save();
            $this->hitungUlangSaldo($saldo);
            $this->catatTransaksi($saldo, JenisTransaksiSaldoCuti::Penyesuaian, $perubahanHari, null, $alasan, $pelaku);
        });

        $this->auditLogger->catat(
            $pelaku,
            'cuti.sesuaikan_saldo',
            $saldo,
            [],
            ['perubahan_hari' => $perubahanHari, 'sisa_hari' => $saldo->fresh()->sisa_hari],
            $alasan,
        );

        return $saldo->fresh();
    }

    public function hitungUlangSaldo(LeaveBalance $saldo): LeaveBalance
    {
        $saldo->sisa_hari = $saldo->hak_hari
            + $saldo->carry_over_hari
            + $saldo->penyesuaian_hari
            - $saldo->terpakai_hari
            - $saldo->tertunda_hari;

        $saldo->save();

        return $saldo;
    }

    private function carryOverTahunSebelumnya(Employee $pegawai, CarbonImmutable $awal): int
    {
        $saldoSebelumnya = LeaveBalance::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('awal_tahun_cuti', $awal->subYear()->toDateString())
            ->first();

        if ($saldoSebelumnya === null) {
            return 0;
        }

        $this->hitungUlangSaldo($saldoSebelumnya);

        return max(0, $saldoSebelumnya->sisa_hari);
    }

    private function catatTransaksi(
        LeaveBalance $saldo,
        JenisTransaksiSaldoCuti $jenis,
        int $perubahanHari,
        ?Model $referensi = null,
        ?string $alasan = null,
        ?User $pelaku = null,
    ): LeaveBalanceTransaction {
        $dibuatOleh = $pelaku !== null ? $pelaku->id : $saldo->employee?->user?->id;

        return LeaveBalanceTransaction::query()->create([
            'saldo_cuti_id' => $saldo->id,
            'jenis' => $jenis,
            'perubahan_hari' => $perubahanHari,
            'referensi_type' => $referensi?->getMorphClass(),
            'referensi_id' => $referensi?->getKey(),
            'dibuat_oleh' => $dibuatOleh,
            'alasan' => $alasan,
        ]);
    }

    /**
     * @return array{hari_kerja: int, saldo: LeaveBalance|null, cukup: bool, pesan: string|null}
     */
    private function hasilPratinjau(int $hariKerja, ?LeaveBalance $saldo, ?string $pesan): array
    {
        return [
            'hari_kerja' => $hariKerja,
            'saldo' => $saldo,
            'cukup' => $pesan === null,
            'pesan' => $pesan,
        ];
    }

    private function pastikanMenunggu(LeaveRequest $pengajuan): void
    {
        if ($pengajuan->status !== StatusPermintaan::Diajukan) {
            throw ValidationException::withMessages([
                'pengajuan_cuti' => 'Hanya pengajuan berstatus diajukan yang dapat diproses.',
            ]);
        }
    }

    private function pastikanBukanPersetujuanSendiri(LeaveRequest $pengajuan, User $peninjau): void
    {
        if ($peninjau->pegawai_id !== null && $peninjau->pegawai_id === $pengajuan->pegawai_id) {
            throw ValidationException::withMessages([
                'pengajuan_cuti' => 'Pemohon tidak dapat menyetujui atau menolak pengajuannya sendiri.',
            ]);
        }
    }
}
