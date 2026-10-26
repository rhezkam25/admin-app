<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceCorrection>
 */
class AttendanceCorrectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'absensi_id' => Attendance::factory(),
            'dikoreksi_oleh' => null,
            'nilai_lama' => ['jam_masuk' => null],
            'nilai_baru' => ['jam_masuk' => '2026-10-26 09:00:00'],
            'alasan' => 'Koreksi demo',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'factory',
        ];
    }
}
