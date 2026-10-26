<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceClockTest extends TestCase
{
    use RefreshDatabase;

    public function test_pegawai_dapat_clock_in_dan_clock_out(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);
        User::factory()->create(['pegawai_id' => $pegawai->id]);

        $this->travelTo(CarbonImmutable::parse('2026-10-26 09:00:00'));
        $absensi = app(AttendanceService::class)->masuk($pegawai, '127.0.0.1', 'phpunit');

        $this->assertSame('09:00', $absensi->jam_masuk?->format('H:i'));
        $this->assertSame('hadir', $absensi->status->value);

        $this->travelTo(CarbonImmutable::parse('2026-10-26 17:30:00'));
        $absensi = app(AttendanceService::class)->pulang($pegawai, '127.0.0.1', 'phpunit');

        $this->assertSame('17:30', $absensi->jam_pulang?->format('H:i'));
        $this->assertFalse($absensi->belum_lengkap);
    }

    public function test_clock_in_ganda_ditolak(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);

        $this->travelTo(CarbonImmutable::parse('2026-10-26 09:00:00'));
        app(AttendanceService::class)->masuk($pegawai);

        $this->expectException(ValidationException::class);

        app(AttendanceService::class)->masuk($pegawai);
    }

    public function test_pegawai_nonaktif_tidak_dapat_clock_in(): void
    {
        $pegawai = Employee::factory()->nonaktif()->create();

        $this->travelTo(CarbonImmutable::parse('2026-10-26 09:00:00'));

        $this->expectException(ValidationException::class);

        app(AttendanceService::class)->masuk($pegawai);
    }

    public function test_keterlambatan_dan_pulang_cepat_dihitung(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);

        $this->travelTo(CarbonImmutable::parse('2026-10-26 09:15:00'));
        $absensi = app(AttendanceService::class)->masuk($pegawai);
        $this->assertSame(15, $absensi->menit_terlambat);

        $this->travelTo(CarbonImmutable::parse('2026-10-26 17:00:00'));
        $absensi = app(AttendanceService::class)->pulang($pegawai);
        $this->assertSame(30, $absensi->menit_pulang_cepat);
    }

    public function test_clock_out_lintas_tengah_malam_ditandai_anomali(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);

        $this->travelTo(CarbonImmutable::parse('2026-10-26 09:00:00'));
        app(AttendanceService::class)->masuk($pegawai);

        $this->travelTo(CarbonImmutable::parse('2026-10-27 00:30:00'));
        $absensi = app(AttendanceService::class)->pulang($pegawai);

        $this->assertContains('lintas_tengah_malam', $absensi->tanda_anomali ?? []);
        $this->assertSame(1, Attendance::query()->count());
    }
}
