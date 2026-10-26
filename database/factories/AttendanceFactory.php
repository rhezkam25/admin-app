<?php

namespace Database\Factories;

use App\Enums\JenisHari;
use App\Enums\StatusAbsensi;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tanggal = now()->startOfDay();

        return [
            'pegawai_id' => Employee::factory(),
            'tanggal_kerja' => $tanggal->toDateString(),
            'jenis_hari' => JenisHari::HariKerja,
            'status' => StatusAbsensi::Hadir,
            'jam_masuk' => $tanggal->copy()->setTime(9, 0),
            'jam_pulang' => $tanggal->copy()->setTime(17, 30),
            'ip_masuk' => '127.0.0.1',
            'ip_pulang' => '127.0.0.1',
            'user_agent_masuk' => 'factory',
            'user_agent_pulang' => 'factory',
            'menit_terlambat' => 0,
            'menit_pulang_cepat' => 0,
            'belum_lengkap' => false,
            'tanda_anomali' => null,
            'catatan' => null,
        ];
    }

    public function belumLengkap(): static
    {
        return $this->state(fn (): array => [
            'jam_pulang' => null,
            'belum_lengkap' => true,
        ]);
    }
}
