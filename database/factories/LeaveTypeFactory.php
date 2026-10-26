<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->slug(2),
            'nama' => fake()->words(2, true),
            'memotong_saldo' => false,
            'wajib_lampiran' => false,
            'aktif' => true,
        ];
    }

    public function tahunan(): static
    {
        return $this->state(fn (): array => [
            'kode' => 'cuti_tahunan',
            'nama' => 'Cuti Tahunan',
            'memotong_saldo' => true,
        ]);
    }

    public function sakit(): static
    {
        return $this->state(fn (): array => [
            'kode' => 'sakit',
            'nama' => 'Sakit',
            'memotong_saldo' => false,
            'wajib_lampiran' => true,
        ]);
    }
}
