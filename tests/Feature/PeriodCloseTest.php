<?php

namespace Tests\Feature;

use App\Enums\StatusPermintaan;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Period\PeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PeriodCloseTest extends TestCase
{
    use RefreshDatabase;

    public function test_periode_tidak_dapat_ditutup_jika_ada_pengajuan_menunggu(): void
    {
        $pegawai = Employee::factory()->create();
        LeaveRequest::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => StatusPermintaan::Diajukan,
            'tanggal_mulai' => '2026-10-26',
            'tanggal_selesai' => '2026-10-26',
        ]);

        $penggunaHr = User::factory()->adminHr()->create();

        $this->expectException(ValidationException::class);

        app(PeriodService::class)->tutup('2026-10', $penggunaHr);
    }

    public function test_hr_dapat_menutup_dan_super_admin_dapat_membuka_kembali(): void
    {
        $penggunaHr = User::factory()->adminHr()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $periode = app(PeriodService::class)->tutup('2026-10', $penggunaHr);

        $this->assertSame('tertutup', $periode->status->value);

        $this->expectException(ValidationException::class);
        app(PeriodService::class)->bukaKembali('2026-10', $penggunaHr, 'Koreksi data.');
    }

    public function test_super_admin_membuka_kembali_periode_dengan_alasan(): void
    {
        $penggunaHr = User::factory()->adminHr()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        app(PeriodService::class)->tutup('2026-10', $penggunaHr);

        $periode = app(PeriodService::class)->bukaKembali('2026-10', $superAdmin, 'Koreksi data setelah payroll.');

        $this->assertSame('terbuka', $periode->status->value);
        $this->assertSame('Koreksi data setelah payroll.', $periode->alasan_buka);
    }

    public function test_periode_terbuka_tidak_dapat_dibuka_kembali(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        PayrollPeriod::factory()->create(['periode' => '2026-10']);

        $this->expectException(ValidationException::class);

        app(PeriodService::class)->bukaKembali('2026-10', $superAdmin, 'Alasan.');
    }
}
