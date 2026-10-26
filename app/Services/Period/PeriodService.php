<?php

namespace App\Services\Period;

use App\Enums\StatusPeriode;
use App\Enums\StatusPermintaan;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Overtime\OvertimePeriodService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PeriodService
{
    public function __construct(
        private readonly OvertimePeriodService $overtimePeriodService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function periodeUntuk(CarbonInterface|string $tanggal): string
    {
        if (is_string($tanggal) && preg_match('/^\d{4}-\d{2}$/', $tanggal) === 1) {
            return $tanggal;
        }

        return CarbonImmutable::parse($tanggal)->format('Y-m');
    }

    public function ambilAtauBuat(string $periode): PayrollPeriod
    {
        return PayrollPeriod::query()->firstOrCreate(
            ['periode' => $periode],
            ['status' => StatusPeriode::Terbuka],
        );
    }

    public function terbuka(string $periode): bool
    {
        $data = PayrollPeriod::query()->where('periode', $periode)->first();

        return $data === null || $data->status === StatusPeriode::Terbuka;
    }

    public function pastikanTerbuka(CarbonInterface|string $tanggal): void
    {
        $periode = $this->periodeUntuk($tanggal);

        if (! $this->terbuka($periode)) {
            throw ValidationException::withMessages([
                'periode' => "Periode {$periode} sudah ditutup dan tidak dapat diubah.",
            ]);
        }
    }

    public function pastikanRentangTerbuka(CarbonInterface|string $mulai, CarbonInterface|string $selesai): void
    {
        $awal = CarbonImmutable::parse($mulai)->startOfMonth();
        $akhir = CarbonImmutable::parse($selesai)->startOfMonth();

        for ($bulan = $awal; $bulan->lessThanOrEqualTo($akhir); $bulan = $bulan->addMonth()) {
            $this->pastikanTerbuka($bulan->format('Y-m'));
        }
    }

    public function tutup(string $periode, User $pelaku, bool $paksa = false): PayrollPeriod
    {
        $mulai = CarbonImmutable::createFromFormat('Y-m', $periode)->startOfMonth();
        $akhir = $mulai->endOfMonth();

        $adaCutiMenunggu = LeaveRequest::query()
            ->where('status', StatusPermintaan::Diajukan)
            ->whereDate('tanggal_mulai', '<=', $akhir->toDateString())
            ->whereDate('tanggal_selesai', '>=', $mulai->toDateString())
            ->exists();

        $adaLemburMenunggu = OvertimeRequest::query()
            ->where('status', StatusPermintaan::Diajukan)
            ->whereDate('tanggal_kerja', '>=', $mulai->toDateString())
            ->whereDate('tanggal_kerja', '<=', $akhir->toDateString())
            ->exists();

        if (! $paksa && ($adaCutiMenunggu || $adaLemburMenunggu)) {
            throw ValidationException::withMessages([
                'periode' => 'Masih ada pengajuan cuti atau lembur yang menunggu keputusan pada periode ini.',
            ]);
        }

        $this->overtimePeriodService->hitungUlangSemua($periode);

        $periodeModel = $this->ambilAtauBuat($periode);
        $statusLama = $periodeModel->status;

        DB::transaction(function () use ($periodeModel, $pelaku): void {
            $periodeModel->update([
                'status' => StatusPeriode::Tertutup,
                'ditutup_oleh' => $pelaku->id,
                'ditutup_pada' => now(),
            ]);
        });

        $this->auditLogger->catat(
            $pelaku,
            'periode.tutup',
            $periodeModel,
            ['status' => $statusLama->value],
            ['status' => StatusPeriode::Tertutup->value],
        );

        return $periodeModel->fresh();
    }

    public function bukaKembali(string $periode, User $pelaku, string $alasan): PayrollPeriod
    {
        if (! $pelaku->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'periode' => 'Hanya Super Admin yang dapat membuka kembali periode tertutup.',
            ]);
        }

        $periodeModel = $this->ambilAtauBuat($periode);

        if ($periodeModel->status === StatusPeriode::Terbuka) {
            throw ValidationException::withMessages([
                'periode' => 'Periode ini sudah terbuka.',
            ]);
        }

        DB::transaction(function () use ($periodeModel, $pelaku, $alasan): void {
            $periodeModel->update([
                'status' => StatusPeriode::Terbuka,
                'dibuka_oleh' => $pelaku->id,
                'dibuka_pada' => now(),
                'alasan_buka' => $alasan,
            ]);
        });

        $this->auditLogger->catat(
            $pelaku,
            'periode.buka_kembali',
            $periodeModel,
            ['status' => StatusPeriode::Tertutup->value],
            ['status' => StatusPeriode::Terbuka->value],
            $alasan,
        );

        return $periodeModel->fresh();
    }

    public function hitungUlang(string $periode): int
    {
        return $this->overtimePeriodService->hitungUlangSemua($periode);
    }
}
