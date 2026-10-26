<?php

namespace Database\Factories;

use App\Enums\SegmenLembur;
use App\Enums\StatusPermintaan;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OvertimeRequest>
 */
class OvertimeRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pegawai_id' => Employee::factory(),
            'catatan_lembur_id' => null,
            'tanggal_kerja' => now()->toDateString(),
            'segmen' => SegmenLembur::Sore,
            'rencana_mulai' => '18:00',
            'rencana_selesai' => '20:00',
            'menit_diajukan' => 120,
            'menit_disetujui' => 0,
            'status' => StatusPermintaan::Draf,
            'ditinjau_oleh' => null,
            'ditinjau_pada' => null,
            'alasan' => fake()->sentence(),
            'alasan_penolakan' => null,
            'alasan_override' => null,
        ];
    }

    public function diajukan(): static
    {
        return $this->state(fn (): array => [
            'status' => StatusPermintaan::Diajukan,
        ]);
    }
}
