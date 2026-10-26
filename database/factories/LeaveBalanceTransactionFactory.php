<?php

namespace Database\Factories;

use App\Enums\JenisTransaksiSaldoCuti;
use App\Models\LeaveBalance;
use App\Models\LeaveBalanceTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalanceTransaction>
 */
class LeaveBalanceTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'saldo_cuti_id' => LeaveBalance::factory(),
            'jenis' => JenisTransaksiSaldoCuti::Hak,
            'perubahan_hari' => 12,
            'referensi_type' => null,
            'referensi_id' => null,
            'dibuat_oleh' => null,
            'alasan' => 'Hak cuti tahunan',
        ];
    }
}
