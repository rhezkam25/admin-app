<?php

namespace Tests\Feature;

use App\Enums\JenisHari;
use App\Enums\StatusAbsensi;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Services\Attendance\DailyAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalisasi_memperbarui_jenis_hari_setelah_libur_ditambahkan(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);

        $absensi = Attendance::factory()->create([
            'pegawai_id' => $pegawai->id,
            'tanggal_kerja' => '2026-10-26',
            'jenis_hari' => JenisHari::HariKerja,
            'status' => StatusAbsensi::Hadir,
            'jam_masuk' => '2026-10-26 09:00:00',
            'jam_pulang' => '2026-10-26 20:00:00',
        ]);

        Holiday::factory()->create([
            'tanggal' => '2026-10-26',
            'negara' => 'ID',
            'aktif' => true,
            'diamati' => true,
        ]);

        app(DailyAttendanceService::class)->finalisasi('2026-10-26', true);

        $absensi->refresh();

        $this->assertSame(JenisHari::HariLibur, $absensi->jenis_hari);
        $this->assertSame(StatusAbsensi::Hadir, $absensi->status);
    }

    public function test_finalisasi_menandai_clock_in_tanpa_clock_out_sebagai_belum_lengkap(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);

        $absensi = Attendance::factory()->create([
            'pegawai_id' => $pegawai->id,
            'tanggal_kerja' => '2026-10-26',
            'jenis_hari' => JenisHari::HariKerja,
            'status' => StatusAbsensi::Hadir,
            'jam_masuk' => '2026-10-26 09:00:00',
            'jam_pulang' => null,
            'belum_lengkap' => false,
        ]);

        app(DailyAttendanceService::class)->finalisasi('2026-10-26', true);

        $absensi->refresh();

        $this->assertTrue($absensi->belum_lengkap);
        $this->assertSame(StatusAbsensi::BelumLengkap, $absensi->status);
    }
}
