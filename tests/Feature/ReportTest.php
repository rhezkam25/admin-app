<?php

namespace Tests\Feature;

use App\Enums\StatusAbsensi;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Services\Report\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_rekap_absensi_menyertakan_tanggal_akhir_rentang(): void
    {
        $penggunaHr = User::factory()->adminHr()->create();
        $pegawai = Employee::factory()->create();

        Attendance::factory()->create([
            'pegawai_id' => $pegawai->id,
            'tanggal_kerja' => '2026-10-31',
            'status' => StatusAbsensi::Hadir,
        ]);

        $rekap = app(ReportService::class)->rekapAbsensi($penggunaHr, '2026-10-01', '2026-10-31');

        $this->assertCount(1, $rekap);
        $this->assertSame(1, $rekap->first()['hadir']);
    }

    public function test_atasan_tidak_melihat_nominal_lembur_tim(): void
    {
        $atasan = User::factory()->atasan()->create();

        $this->assertFalse(app(ReportService::class)->bolehLihatNominal($atasan));
        $this->assertTrue(app(ReportService::class)->bolehLihatNominal(User::factory()->adminHr()->create()));
        $this->assertTrue(app(ReportService::class)->bolehLihatNominal(User::factory()->pegawai()->create()));
    }

    public function test_rekap_absensi_pegawai_hanya_berisi_data_sendiri(): void
    {
        $pegawai = Employee::factory()->create();
        $lain = Employee::factory()->create();
        $pengguna = User::factory()->create(['pegawai_id' => $pegawai->id]);

        Attendance::factory()->create(['pegawai_id' => $pegawai->id, 'tanggal_kerja' => '2026-10-26']);
        Attendance::factory()->create(['pegawai_id' => $lain->id, 'tanggal_kerja' => '2026-10-26']);

        $rekap = app(ReportService::class)->rekapAbsensi($pengguna, '2026-10-01', '2026-10-31');

        $this->assertCount(1, $rekap);
        $this->assertSame($pegawai->id, $rekap->first()['pegawai']?->id);
    }
}
