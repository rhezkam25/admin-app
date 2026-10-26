<?php

namespace Database\Factories;

use App\Enums\StatusPeriode;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periode' => now()->format('Y-m'),
            'status' => StatusPeriode::Terbuka,
            'ditutup_oleh' => null,
            'ditutup_pada' => null,
            'dibuka_oleh' => null,
            'dibuka_pada' => null,
            'alasan_buka' => null,
        ];
    }

    public function tertutup(): static
    {
        return $this->state(fn (): array => [
            'status' => StatusPeriode::Tertutup,
            'ditutup_pada' => now(),
        ]);
    }
}
