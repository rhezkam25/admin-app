<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pegawai_id' => Employee::factory(),
            'awal_tahun_cuti' => now()->startOfYear()->toDateString(),
            'hak_hari' => 12,
            'carry_over_hari' => 0,
            'terpakai_hari' => 0,
            'tertunda_hari' => 0,
            'penyesuaian_hari' => 0,
            'sisa_hari' => 12,
            'kedaluwarsa_pada' => now()->addYear()->startOfYear()->toDateString(),
        ];
    }
}
