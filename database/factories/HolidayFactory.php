<?php

namespace Database\Factories;

use App\Enums\NegaraLibur;
use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'negara' => NegaraLibur::Indonesia,
            'nama' => fake()->randomElement(['Hari Kemerdekaan', 'Tahun Baru', 'Hari Buruh']),
            'jenis' => 'nasional',
            'diamati' => true,
            'aktif' => true,
            'sumber' => 'demo',
        ];
    }
}
