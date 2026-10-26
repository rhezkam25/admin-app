<?php

namespace Tests\Feature;

use App\Enums\StatusPeriode;
use App\Enums\StatusPermintaan;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Attendance\DailyAttendanceService;
use App\Services\Leave\LeaveBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_penolakan_mengembalikan_saldo_tertunda(): void
    {
        [$pegawai, $penggunaAtasan, $jenisCuti] = $this->siapkanPegawai();
        $layanan = app(LeaveBalanceService::class);

        $pengajuan = $layanan->ajukan(
            $pegawai,
            $jenisCuti,
            CarbonImmutable::parse('2026-10-26'),
            CarbonImmutable::parse('2026-10-26'),
            'Keperluan keluarga.',
        );

        $this->assertSame(1, $layanan->ambilAtauBuatSaldo($pegawai, '2026-10-26')?->tertunda_hari);

        $layanan->tolak($pengajuan, $penggunaAtasan, 'Tidak dapat disetujui.');

        $saldo = $layanan->ambilAtauBuatSaldo($pegawai, '2026-10-26');
        $this->assertNotNull($saldo);
        $this->assertSame(0, $saldo->tertunda_hari);
        $this->assertSame(0, $saldo->terpakai_hari);
        $this->assertSame(12, $saldo->sisa_hari);
        $this->assertSame(StatusPermintaan::Ditolak, $pengajuan->fresh()->status);
    }

    public function test_pembatalan_cuti_disetujui_mengembalikan_saldo(): void
    {
        [$pegawai, $penggunaAtasan, $jenisCuti] = $this->siapkanPegawai();
        $penggunaPegawai = User::factory()->create(['pegawai_id' => $pegawai->id]);
        $layanan = app(LeaveBalanceService::class);

        $pengajuan = $layanan->ajukan(
            $pegawai,
            $jenisCuti,
            CarbonImmutable::parse('2026-10-26'),
            CarbonImmutable::parse('2026-10-26'),
            'Keperluan keluarga.',
        );

        $layanan->setujui($pengajuan, $penggunaAtasan);
        $this->assertSame(1, $layanan->ambilAtauBuatSaldo($pegawai, '2026-10-26')?->terpakai_hari);

        $layanan->batalkan($pengajuan, $penggunaPegawai, 'Rencana berubah.');

        $saldo = $layanan->ambilAtauBuatSaldo($pegawai, '2026-10-26');
        $this->assertNotNull($saldo);
        $this->assertSame(0, $saldo->terpakai_hari);
        $this->assertSame(12, $saldo->sisa_hari);
        $this->assertSame(StatusPermintaan::Dibatalkan, $pengajuan->fresh()->status);
    }

    public function test_persetujuan_cuti_langsung_tercermin_di_absensi(): void
    {
        [$pegawai, $penggunaAtasan, $jenisCuti] = $this->siapkanPegawai();
        $layanan = app(LeaveBalanceService::class);

        $pengajuan = $layanan->ajukan(
            $pegawai,
            $jenisCuti,
            CarbonImmutable::parse('2026-10-26'),
            CarbonImmutable::parse('2026-10-26'),
            'Keperluan keluarga.',
        );

        $layanan->setujui($pengajuan, $penggunaAtasan);

        $absensi = Attendance::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', '2026-10-26')
            ->first();

        $this->assertNotNull($absensi);
        $this->assertSame('cuti', $absensi->status->value);
    }

    public function test_cuti_disetujui_tercermin_di_absensi_setelah_finalisasi(): void
    {
        [$pegawai, , $jenisCuti] = $this->siapkanPegawai();
        $tanggal = '2026-10-26';

        LeaveRequest::factory()->create([
            'pegawai_id' => $pegawai->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'status' => StatusPermintaan::Disetujui,
            'tanggal_mulai' => $tanggal,
            'tanggal_selesai' => $tanggal,
            'jumlah_hari_kerja' => 1,
        ]);

        app(DailyAttendanceService::class)->finalisasi($tanggal, true);

        $absensi = Attendance::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', $tanggal)
            ->first();

        $this->assertNotNull($absensi);
        $this->assertSame('cuti', $absensi->status->value);
    }

    public function test_periode_tertutup_menolak_pengajuan_cuti(): void
    {
        [$pegawai, , $jenisCuti] = $this->siapkanPegawai();

        PayrollPeriod::factory()->create([
            'periode' => '2026-10',
            'status' => StatusPeriode::Tertutup,
        ]);

        $this->expectException(ValidationException::class);

        app(LeaveBalanceService::class)->ajukan(
            $pegawai,
            $jenisCuti,
            CarbonImmutable::parse('2026-10-26'),
            CarbonImmutable::parse('2026-10-26'),
            'Keperluan keluarga.',
        );
    }

    /**
     * @return array{0: Employee, 1: User, 2: LeaveType}
     */
    private function siapkanPegawai(): array
    {
        $atasan = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);
        $pegawai = Employee::factory()->create([
            'tanggal_masuk' => '2020-01-01',
            'atasan_id' => $atasan->id,
        ]);

        $penggunaAtasan = User::factory()->create(['pegawai_id' => $atasan->id]);
        $jenisCuti = LeaveType::factory()->tahunan()->create();

        app(LeaveBalanceService::class)->ambilAtauBuatSaldo($pegawai, '2026-10-26');

        return [$pegawai, $penggunaAtasan, $jenisCuti];
    }
}
