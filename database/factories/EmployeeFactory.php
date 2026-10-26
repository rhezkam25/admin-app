<?php

namespace Database\Factories;

use App\Enums\StatusKepegawaian;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nip' => fake()->unique()->numerify('EMP####'),
            'nama' => fake()->name(),
            'departemen_id' => Department::factory(),
            'jabatan_id' => Position::factory(),
            'atasan_id' => null,
            'jadwal_kerja_id' => WorkSchedule::factory()->withWeekdays(),
            'status_kepegawaian' => StatusKepegawaian::Aktif,
            'tanggal_masuk' => now()->subYears(2)->toDateString(),
            'tanggal_keluar' => null,
            'telepon' => fake()->phoneNumber(),
            'alamat' => fake()->address(),
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn (): array => [
            'status_kepegawaian' => StatusKepegawaian::Nonaktif,
            'tanggal_keluar' => now()->toDateString(),
        ]);
    }

    public function withSupervisor(?Employee $atasan = null): static
    {
        return $this->state(fn (): array => [
            'atasan_id' => $atasan?->id ?? Employee::factory(),
        ]);
    }
}
