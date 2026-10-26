<?php

namespace App\Models;

use Database\Factories\LeaveBalanceFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pegawai_id
 * @property Carbon $awal_tahun_cuti
 * @property int $hak_hari
 * @property int $carry_over_hari
 * @property int $terpakai_hari
 * @property int $tertunda_hari
 * @property int $penyesuaian_hari
 * @property int $sisa_hari
 * @property Carbon|null $kedaluwarsa_pada
 * @property-read Employee|null $employee
 * @property-read Collection<int, LeaveBalanceTransaction> $transactions
 */
class LeaveBalance extends Model
{
    /** @use HasFactory<LeaveBalanceFactory> */
    use HasFactory;

    protected $table = 'saldo_cuti';

    protected $fillable = [
        'pegawai_id',
        'awal_tahun_cuti',
        'hak_hari',
        'carry_over_hari',
        'terpakai_hari',
        'tertunda_hari',
        'penyesuaian_hari',
        'sisa_hari',
        'kedaluwarsa_pada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'awal_tahun_cuti' => 'date',
            'hak_hari' => 'integer',
            'carry_over_hari' => 'integer',
            'terpakai_hari' => 'integer',
            'tertunda_hari' => 'integer',
            'penyesuaian_hari' => 'integer',
            'sisa_hari' => 'integer',
            'kedaluwarsa_pada' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pegawai_id');
    }

    /**
     * @return HasMany<LeaveBalanceTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(LeaveBalanceTransaction::class, 'saldo_cuti_id');
    }

    public function availableDays(): int
    {
        return $this->hak_hari
            + $this->carry_over_hari
            + $this->penyesuaian_hari
            - $this->terpakai_hari
            - $this->tertunda_hari;
    }
}
