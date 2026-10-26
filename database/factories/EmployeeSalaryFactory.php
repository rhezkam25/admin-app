<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeSalary>
 */
class EmployeeSalaryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pegawai_id' => Employee::factory(),
            'berlaku_mulai' => now()->startOfYear()->toDateString(),
            'gaji_pokok_sen' => 200_000,
            'mata_uang' => 'USD',
            'dibuat_oleh' => null,
        ];
    }
}
