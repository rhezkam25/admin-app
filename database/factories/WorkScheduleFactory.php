<?php

namespace Database\Factories;

use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkSchedule>
 */
class WorkScheduleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Jadwal Tetap',
            'zona_waktu' => config('app.timezone', 'Asia/Jakarta'),
            'bawaan' => true,
            'aktif' => true,
        ];
    }

    public function withWeekdays(): static
    {
        return $this->afterCreating(function (WorkSchedule $jadwal): void {
            foreach (range(1, 7) as $hariKe) {
                $kerja = $hariKe <= 5;
                $jumat = $hariKe === 5;

                WorkScheduleDay::query()->create([
                    'jadwal_kerja_id' => $jadwal->id,
                    'hari_ke' => $hariKe,
                    'hari_kerja' => $kerja,
                    'jam_mulai' => $kerja ? '09:00' : null,
                    'jam_selesai' => $kerja ? ($jumat ? '18:00' : '17:30') : null,
                    'istirahat_mulai' => $kerja ? '12:00' : null,
                    'istirahat_selesai' => $kerja ? '13:00' : null,
                ]);
            }
        });
    }
}
