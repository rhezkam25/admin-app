<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\OvertimeRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OvertimeRecord>
 */
class OvertimeRecordFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pegawai_id' => Employee::factory(),
            'tanggal_kerja' => now()->toDateString(),
            'absensi_id' => null,
            'menit_pagi_aktual' => 0,
            'menit_sore_aktual' => 120,
            'menit_libur_aktual' => 0,
            'menit_aktual' => 120,
            'menit_disetujui' => 120,
            'menit_dibayar' => 120,
            'tarif_sen' => 600,
            'nilai_kotor_sen' => 1200,
            'nilai_dibayar_sen' => 1200,
            'nilai_hangus_sen' => 0,
            'snapshot_aturan' => ['tarif_hari_kerja_sen' => 600],
            'dihitung_pada' => now(),
        ];
    }
}
