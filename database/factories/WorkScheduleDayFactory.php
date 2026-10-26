<?php

namespace Database\Factories;

use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkScheduleDay>
 */
class WorkScheduleDayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jadwal_kerja_id' => WorkSchedule::factory(),
            'hari_ke' => fake()->numberBetween(1, 5),
            'hari_kerja' => true,
            'jam_mulai' => '09:00',
            'jam_selesai' => '17:30',
            'istirahat_mulai' => '12:00',
            'istirahat_selesai' => '13:00',
        ];
    }
}
