<?php

namespace Database\Factories;

use App\Enums\StatusPermintaan;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mulai = now()->addWeek()->startOfDay();

        return [
            'pegawai_id' => Employee::factory(),
            'jenis_cuti_id' => LeaveType::factory(),
            'tanggal_mulai' => $mulai->toDateString(),
            'tanggal_selesai' => $mulai->toDateString(),
            'jumlah_hari_kerja' => 1,
            'alasan' => fake()->sentence(),
            'lampiran_path' => null,
            'status' => StatusPermintaan::Draf,
            'ditinjau_oleh' => null,
            'ditinjau_pada' => null,
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
