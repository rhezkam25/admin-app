<?php

namespace Tests\Feature;

use App\Enums\StatusPeriode;
use App\Enums\StatusPermintaan;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pegawai_tidak_dapat_melihat_data_pegawai_lain(): void
    {
        $atasan = Employee::factory()->create();
        $pegawai = Employee::factory()->create(['atasan_id' => $atasan->id]);
        $lain = Employee::factory()->create(['atasan_id' => $atasan->id]);

        $penggunaPegawai = User::factory()->create(['pegawai_id' => $pegawai->id]);

        $absensiLain = Attendance::factory()->create(['pegawai_id' => $lain->id]);
        $cutiLain = LeaveRequest::factory()->create([
            'pegawai_id' => $lain->id,
            'status' => StatusPermintaan::Diajukan,
        ]);

        $this->assertFalse(Gate::forUser($penggunaPegawai)->allows('view', $absensiLain));
        $this->assertFalse(Gate::forUser($penggunaPegawai)->allows('view', $cutiLain));
        $this->assertFalse(Gate::forUser($penggunaPegawai)->allows('correct', $absensiLain));
    }

    public function test_atasan_dapat_melihat_dan_menyetujui_bawahan(): void
    {
        $atasan = Employee::factory()->create();
        $pegawai = Employee::factory()->create(['atasan_id' => $atasan->id]);

        $penggunaAtasan = User::factory()->create(['pegawai_id' => $atasan->id]);

        $absensi = Attendance::factory()->create(['pegawai_id' => $pegawai->id]);
        $cuti = LeaveRequest::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => StatusPermintaan::Diajukan,
        ]);

        $this->assertTrue(Gate::forUser($penggunaAtasan)->allows('view', $absensi));
        $this->assertTrue(Gate::forUser($penggunaAtasan)->allows('view', $cuti));
        $this->assertTrue(Gate::forUser($penggunaAtasan)->allows('approve', $cuti));
        $this->assertFalse(Gate::forUser($penggunaAtasan)->allows('correct', $absensi));
    }

    public function test_pemohon_tidak_dapat_menyetujui_pengajuan_sendiri(): void
    {
        $pegawai = Employee::factory()->create();
        $pengguna = User::factory()->create(['pegawai_id' => $pegawai->id]);

        $cuti = LeaveRequest::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => StatusPermintaan::Diajukan,
        ]);

        $this->assertFalse(Gate::forUser($pengguna)->allows('approve', $cuti));
    }

    public function test_hr_dapat_mengoreksi_absensi_dan_melihat_gaji(): void
    {
        $pegawai = Employee::factory()->create();
        $penggunaHr = User::factory()->adminHr()->create();

        $absensi = Attendance::factory()->create(['pegawai_id' => $pegawai->id]);

        $this->assertTrue(Gate::forUser($penggunaHr)->allows('correct', $absensi));
        $this->assertTrue(Gate::forUser($penggunaHr)->allows('viewSalary', $pegawai));
    }

    public function test_hanya_super_admin_dapat_membuka_periode(): void
    {
        $periode = PayrollPeriod::factory()->create([
            'periode' => '2026-10',
            'status' => StatusPeriode::Tertutup,
        ]);

        $penggunaHr = User::factory()->adminHr()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertFalse(Gate::forUser($penggunaHr)->allows('reopen', $periode));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('reopen', $periode));
        $this->assertTrue(Gate::forUser($penggunaHr)->allows('close', $periode));
    }
}
