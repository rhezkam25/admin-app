<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\OvertimePeriodSummary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OvertimePeriodSummary>
 */
class OvertimePeriodSummaryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pegawai_id' => Employee::factory(),
            'periode' => now()->format('Y-m'),
            'gaji_pokok_sen' => 200_000,
            'batas_sen' => 56_000,
            'nilai_kotor_sen' => 0,
            'nilai_dibayar_sen' => 0,
            'nilai_hangus_sen' => 0,
            'total_menit' => 0,
            'snapshot_aturan' => null,
        ];
    }
}
