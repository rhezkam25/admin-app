<?php

namespace App\Services\Attendance;

use App\Enums\StatusAbsensi;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Holiday\HolidayService;
use App\Services\Overtime\OvertimePeriodService;
use App\Services\Period\PeriodService;
use App\Services\Schedule\ScheduleService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        private readonly HolidayService $holidayService,
        private readonly ScheduleService $scheduleService,
        private readonly AuditLogger $auditLogger,
        private readonly PeriodService $periodService,
        private readonly OvertimePeriodService $overtimePeriodService,
    ) {}

    public function masuk(
        Employee $pegawai,
        ?string $ip = null,
        ?string $userAgent = null,
        ?CarbonInterface $waktu = null,
    ): Attendance {
        $waktu = CarbonImmutable::parse($waktu ?? now())->setTimezone(config('app.timezone'));
        $tanggal = $waktu->toDateString();

        $this->periodService->pastikanTerbuka($tanggal);

        if (! $pegawai->isActive()) {
            throw ValidationException::withMessages([
                'pegawai' => 'Pegawai tidak aktif dan tidak dapat melakukan absensi.',
            ]);
        }

        $sudahAda = Attendance::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', $tanggal)
            ->exists();

        if ($sudahAda) {
            throw ValidationException::withMessages([
                'absensi' => 'Absensi untuk tanggal ini sudah ada.',
            ]);
        }

        $jenisHari = $this->holidayService->jenisHari($waktu);
        $jamMulai = $this->scheduleService->waktuMasukDiharapkan($pegawai, $waktu);
        $toleransi = (int) Setting::get('absensi.toleransi_terlambat_menit', 0);
        $menitTerlambat = 0;

        if ($jenisHari->value === 'hari_kerja' && $jamMulai !== null) {
            $batasTerlambat = $jamMulai->addMinutes($toleransi);

            if ($waktu->greaterThan($batasTerlambat)) {
                $menitTerlambat = max(0, (int) $jamMulai->diffInMinutes($waktu));
            }
        }

        $absensi = DB::transaction(function () use ($pegawai, $waktu, $jenisHari, $menitTerlambat, $ip, $userAgent, $tanggal): Attendance {
            $absensi = Attendance::query()->create([
                'pegawai_id' => $pegawai->id,
                'tanggal_kerja' => $tanggal,
                'jenis_hari' => $jenisHari,
                'status' => StatusAbsensi::Hadir,
                'jam_masuk' => $waktu,
                'menit_terlambat' => $menitTerlambat,
                'ip_masuk' => $ip,
                'user_agent_masuk' => $userAgent,
            ]);

            $this->auditLogger->catat(
                $pegawai->user,
                'absensi.masuk',
                $absensi,
                [],
                ['jam_masuk' => $waktu->toDateTimeString(), 'status' => StatusAbsensi::Hadir->value],
                null,
                $ip,
                $userAgent,
            );

            return $absensi;
        });

        return $absensi->fresh();
    }

    public function pulang(
        Employee $pegawai,
        ?string $ip = null,
        ?string $userAgent = null,
        ?CarbonInterface $waktu = null,
    ): Attendance {
        $waktu = CarbonImmutable::parse($waktu ?? now())->setTimezone(config('app.timezone'));
        $lintasTengahMalam = false;

        $absensi = Attendance::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', $waktu->toDateString())
            ->first();

        if ($absensi === null && $waktu->hour < 6) {
            $absensi = Attendance::query()
                ->where('pegawai_id', $pegawai->id)
                ->whereDate('tanggal_kerja', $waktu->subDay()->toDateString())
                ->whereNotNull('jam_masuk')
                ->whereNull('jam_pulang')
                ->first();
            $lintasTengahMalam = $absensi !== null;
        }

        if ($absensi === null) {
            throw ValidationException::withMessages([
                'absensi' => 'Tidak ada absensi masuk yang dapat dipulangkan.',
            ]);
        }

        $this->periodService->pastikanTerbuka($absensi->tanggal_kerja);

        if ($absensi->jam_pulang !== null) {
            throw ValidationException::withMessages([
                'absensi' => 'Absensi pulang untuk tanggal ini sudah tercatat.',
            ]);
        }

        if ($absensi->jam_masuk === null) {
            throw ValidationException::withMessages([
                'absensi' => 'Absensi masuk belum tercatat.',
            ]);
        }

        $waktuPulangDiharapkan = $this->scheduleService->waktuPulangDiharapkan($pegawai, $absensi->tanggal_kerja);
        $menitPulangCepat = 0;

        if ($waktuPulangDiharapkan !== null && $waktu->lessThan($waktuPulangDiharapkan)) {
            $menitPulangCepat = max(0, (int) $waktu->diffInMinutes($waktuPulangDiharapkan));
        }

        $anomali = $absensi->tanda_anomali ?? [];

        if ($lintasTengahMalam) {
            $anomali[] = 'lintas_tengah_malam';
        }

        DB::transaction(function () use ($absensi, $waktu, $ip, $userAgent, $menitPulangCepat, $anomali): void {
            $absensi->update([
                'jam_pulang' => $waktu,
                'ip_pulang' => $ip,
                'user_agent_pulang' => $userAgent,
                'menit_pulang_cepat' => $menitPulangCepat,
                'belum_lengkap' => false,
                'tanda_anomali' => $anomali === [] ? null : $anomali,
            ]);

            $this->auditLogger->catat(
                $absensi->employee?->user,
                'absensi.pulang',
                $absensi,
                ['jam_pulang' => null],
                ['jam_pulang' => $waktu->toDateTimeString(), 'menit_pulang_cepat' => $menitPulangCepat],
                null,
                $ip,
                $userAgent,
            );
        });

        $this->overtimePeriodService->hitungUlangTanggal($pegawai, $absensi->tanggal_kerja);

        return $absensi->fresh();
    }

    /**
     * @param  array{jam_masuk?: string|null, jam_pulang?: string|null, status?: string|null, catatan?: string|null}  $data
     */
    public function koreksi(
        Attendance $absensi,
        array $data,
        User $pelaku,
        string $alasan,
        ?string $ip = null,
        ?string $userAgent = null,
    ): Attendance {
        $this->periodService->pastikanTerbuka($absensi->tanggal_kerja);

        $nilaiLama = $this->snapshot($absensi);
        $jamMasuk = array_key_exists('jam_masuk', $data)
            ? $this->parseWaktu($data['jam_masuk'])
            : $absensi->jam_masuk;
        $jamPulang = array_key_exists('jam_pulang', $data)
            ? $this->parseWaktu($data['jam_pulang'])
            : $absensi->jam_pulang;

        $menitTerlambat = 0;
        $waktuMasukDiharapkan = $this->scheduleService->waktuMasukDiharapkan($absensi->employee, $absensi->tanggal_kerja);

        if ($jamMasuk !== null && $waktuMasukDiharapkan !== null) {
            $menitTerlambat = max(0, (int) $waktuMasukDiharapkan->diffInMinutes($jamMasuk));
        }

        $menitPulangCepat = 0;
        $waktuPulangDiharapkan = $this->scheduleService->waktuPulangDiharapkan($absensi->employee, $absensi->tanggal_kerja);

        if ($jamPulang !== null && $waktuPulangDiharapkan !== null && $jamPulang->lessThan($waktuPulangDiharapkan)) {
            $menitPulangCepat = max(0, (int) $jamPulang->diffInMinutes($waktuPulangDiharapkan));
        }

        $status = $absensi->status;

        if (array_key_exists('status', $data) && $data['status'] !== null) {
            $status = StatusAbsensi::from($data['status']);
        }

        if ($jamMasuk !== null && $jamPulang === null && $status === StatusAbsensi::Hadir) {
            $status = StatusAbsensi::BelumLengkap;
        }

        DB::transaction(function () use ($absensi, $jamMasuk, $jamPulang, $status, $data, $menitTerlambat, $menitPulangCepat, $pelaku, $alasan, $ip, $userAgent, $nilaiLama): void {
            $absensi->update([
                'jam_masuk' => $jamMasuk,
                'jam_pulang' => $jamPulang,
                'status' => $status,
                'catatan' => $data['catatan'] ?? $absensi->catatan,
                'menit_terlambat' => $menitTerlambat,
                'menit_pulang_cepat' => $menitPulangCepat,
                'belum_lengkap' => $jamMasuk !== null && $jamPulang === null,
            ]);

            AttendanceCorrection::query()->create([
                'absensi_id' => $absensi->id,
                'dikoreksi_oleh' => $pelaku->id,
                'nilai_lama' => $nilaiLama,
                'nilai_baru' => $this->snapshot($absensi),
                'alasan' => $alasan,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            $this->auditLogger->catat(
                $pelaku,
                'absensi.koreksi',
                $absensi,
                $nilaiLama,
                $this->snapshot($absensi),
                $alasan,
                $ip,
                $userAgent,
            );
        });

        $this->overtimePeriodService->hitungUlangTanggal($absensi->employee, $absensi->tanggal_kerja);

        return $absensi->fresh();
    }

    private function parseWaktu(?string $waktu): ?CarbonImmutable
    {
        if ($waktu === null || $waktu === '') {
            return null;
        }

        return CarbonImmutable::parse($waktu)->setTimezone(config('app.timezone'));
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Attendance $absensi): array
    {
        return [
            'tanggal_kerja' => $absensi->tanggal_kerja->toDateString(),
            'jenis_hari' => $absensi->jenis_hari->value,
            'status' => $absensi->status->value,
            'jam_masuk' => $absensi->jam_masuk?->toDateTimeString(),
            'jam_pulang' => $absensi->jam_pulang?->toDateTimeString(),
            'menit_terlambat' => $absensi->menit_terlambat,
            'menit_pulang_cepat' => $absensi->menit_pulang_cepat,
            'belum_lengkap' => $absensi->belum_lengkap,
            'catatan' => $absensi->catatan,
        ];
    }
}
