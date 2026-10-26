<?php

namespace Tests\Feature;

use App\Enums\SegmenLembur;
use App\Enums\StatusPermintaan;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\LeaveRequest;
use App\Models\OvertimePeriodSummary;
use App\Models\OvertimeRecord;
use App\Models\User;
use App\Services\Overtime\OvertimeRequestService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OvertimeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengajuan_dan_persetujuan_lembur_menghasilkan_nilai(): void
    {
        [$pegawai, $atasan, $penggunaAtasan] = $this->siapkanPegawaiDanAtasan();
        $tanggal = CarbonImmutable::parse('2026-10-26');

        $this->buatAbsensi($pegawai, $tanggal, '20:00');

        $layanan = app(OvertimeRequestService::class);
        $pengajuan = $layanan->ajukan(
            $pegawai,
            $tanggal,
            SegmenLembur::Sore,
            '18:00',
            '20:00',
            120,
            'Penyelesaian rilis.',
        );

        $layanan->setujui($pengajuan, $penggunaAtasan);

        $this->assertSame(StatusPermintaan::Disetujui, $pengajuan->fresh()->status);
        $this->assertSame(120, $pengajuan->fresh()->menit_disetujui);

        $record = OvertimeRecord::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', $tanggal->toDateString())
            ->first();

        $this->assertNotNull($record);
        $this->assertSame(120, $record->menit_dibayar);
        $this->assertSame(1200, $record->nilai_kotor_sen);
        $this->assertSame(1200, $record->nilai_dibayar_sen);

        $ringkasan = OvertimePeriodSummary::query()
            ->where('pegawai_id', $pegawai->id)
            ->where('periode', '2026-10')
            ->first();

        $this->assertNotNull($ringkasan);
        $this->assertSame(1200, $ringkasan->nilai_kotor_sen);
        $this->assertSame(1200, $ringkasan->nilai_dibayar_sen);
    }

    public function test_override_menit_persetujuan_membatasi_nilai(): void
    {
        [$pegawai, $atasan, $penggunaAtasan] = $this->siapkanPegawaiDanAtasan();
        $tanggal = CarbonImmutable::parse('2026-10-26');

        $this->buatAbsensi($pegawai, $tanggal, '20:00');

        $layanan = app(OvertimeRequestService::class);
        $pengajuan = $layanan->ajukan(
            $pegawai,
            $tanggal,
            SegmenLembur::Sore,
            '18:00',
            '20:00',
            120,
            'Penyelesaian rilis.',
        );

        $layanan->setujui($pengajuan, $penggunaAtasan, 60, 'Override HR karena laporan tidak lengkap.');

        $record = OvertimeRecord::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', $tanggal->toDateString())
            ->first();

        $this->assertNotNull($record);
        $this->assertSame(60, $record->menit_dibayar);
        $this->assertSame(600, $record->nilai_kotor_sen);
    }

    public function test_pemohon_tidak_dapat_menyetujui_lembur_sendiri(): void
    {
        [$pegawai, $atasan, $penggunaAtasan] = $this->siapkanPegawaiDanAtasan();
        $tanggal = CarbonImmutable::parse('2026-10-26');

        $pengajuan = app(OvertimeRequestService::class)->ajukan(
            $atasan,
            $tanggal,
            SegmenLembur::Sore,
            '18:00',
            '20:00',
            120,
            'Pekerjaan tambahan.',
        );

        $this->expectException(ValidationException::class);

        app(OvertimeRequestService::class)->setujui($pengajuan, $penggunaAtasan);
    }

    public function test_lembur_pada_tanggal_cuti_ditolak(): void
    {
        [$pegawai] = $this->siapkanPegawaiDanAtasan();
        $tanggal = CarbonImmutable::parse('2026-10-26');

        LeaveRequest::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => StatusPermintaan::Disetujui,
            'tanggal_mulai' => $tanggal->toDateString(),
            'tanggal_selesai' => $tanggal->toDateString(),
            'jumlah_hari_kerja' => 1,
        ]);

        $this->expectException(ValidationException::class);

        app(OvertimeRequestService::class)->ajukan(
            $pegawai,
            $tanggal,
            SegmenLembur::Sore,
            '18:00',
            '20:00',
            120,
            'Pekerjaan tambahan.',
        );
    }

    public function test_cap_bulanan_diterapkan_dan_kelebihan_hangus(): void
    {
        [$pegawai, $atasan, $penggunaAtasan] = $this->siapkanPegawaiDanAtasan();
        $pegawai->salaries()->delete();
        EmployeeSalary::factory()->create([
            'pegawai_id' => $pegawai->id,
            'berlaku_mulai' => '2026-01-01',
            'gaji_pokok_sen' => 10_000,
        ]);

        $tanggal = CarbonImmutable::parse('2026-10-31');
        $absensi = Attendance::factory()->create([
            'pegawai_id' => $pegawai->id,
            'tanggal_kerja' => $tanggal->toDateString(),
            'jenis_hari' => 'akhir_pekan',
            'status' => 'hadir',
            'jam_masuk' => $tanggal->setTime(9, 0),
            'jam_pulang' => $tanggal->setTime(15, 0),
            'belum_lengkap' => false,
        ]);

        $layanan = app(OvertimeRequestService::class);
        $pengajuan = $layanan->ajukan(
            $pegawai,
            $tanggal,
            SegmenLembur::Libur,
            '09:00',
            '15:00',
            360,
            'Pekerjaan akhir pekan.',
        );

        $layanan->setujui($pengajuan, $penggunaAtasan);

        $ringkasan = OvertimePeriodSummary::query()
            ->where('pegawai_id', $pegawai->id)
            ->where('periode', '2026-10')
            ->first();

        $this->assertNotNull($ringkasan);
        $this->assertSame(2_800, $ringkasan->batas_sen);
        $this->assertSame(5_400, $ringkasan->nilai_kotor_sen);
        $this->assertSame(2_800, $ringkasan->nilai_dibayar_sen);
        $this->assertSame(2_600, $ringkasan->nilai_hangus_sen);
    }

    /**
     * @return array{0: Employee, 1: Employee, 2: User}
     */
    private function siapkanPegawaiDanAtasan(): array
    {
        $atasan = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);
        $pegawai = Employee::factory()->create([
            'tanggal_masuk' => '2020-01-01',
            'atasan_id' => $atasan->id,
        ]);

        EmployeeSalary::factory()->create([
            'pegawai_id' => $pegawai->id,
            'berlaku_mulai' => '2026-01-01',
            'gaji_pokok_sen' => 200_000,
        ]);

        $penggunaAtasan = User::factory()->create(['pegawai_id' => $atasan->id]);
        User::factory()->create(['pegawai_id' => $pegawai->id]);

        return [$pegawai, $atasan, $penggunaAtasan];
    }

    private function buatAbsensi(Employee $pegawai, CarbonImmutable $tanggal, string $pulang): Attendance
    {
        return Attendance::factory()->create([
            'pegawai_id' => $pegawai->id,
            'tanggal_kerja' => $tanggal->toDateString(),
            'jenis_hari' => 'hari_kerja',
            'status' => 'hadir',
            'jam_masuk' => $tanggal->setTime(9, 0),
            'jam_pulang' => CarbonImmutable::parse($tanggal->toDateString().' '.$pulang),
            'belum_lengkap' => false,
        ]);
    }
}
