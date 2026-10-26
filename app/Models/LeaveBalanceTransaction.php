<?php

namespace App\Models;

use App\Enums\JenisTransaksiSaldoCuti;
use Database\Factories\LeaveBalanceTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $saldo_cuti_id
 * @property JenisTransaksiSaldoCuti $jenis
 * @property int $perubahan_hari
 * @property string|null $referensi_type
 * @property int|null $referensi_id
 * @property int|null $dibuat_oleh
 * @property string|null $alasan
 * @property-read LeaveBalance|null $balance
 * @property-read Model|null $reference
 * @property-read User|null $creator
 */
class LeaveBalanceTransaction extends Model
{
    /** @use HasFactory<LeaveBalanceTransactionFactory> */
    use HasFactory;

    protected $table = 'transaksi_saldo_cuti';

    protected $fillable = [
        'saldo_cuti_id',
        'jenis',
        'perubahan_hari',
        'referensi_type',
        'referensi_id',
        'dibuat_oleh',
        'alasan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisTransaksiSaldoCuti::class,
            'perubahan_hari' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<LeaveBalance, $this>
     */
    public function balance(): BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class, 'saldo_cuti_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('referensi');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
