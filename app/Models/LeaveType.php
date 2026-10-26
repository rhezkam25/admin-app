<?php

namespace App\Models;

use Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $kode
 * @property string $nama
 * @property bool $memotong_saldo
 * @property bool $wajib_lampiran
 * @property bool $aktif
 * @property-read Collection<int, LeaveRequest> $requests
 */
class LeaveType extends Model
{
    /** @use HasFactory<LeaveTypeFactory> */
    use HasFactory;

    protected $table = 'jenis_cuti';

    protected $fillable = [
        'kode',
        'nama',
        'memotong_saldo',
        'wajib_lampiran',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'memotong_saldo' => 'boolean',
            'wajib_lampiran' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<LeaveRequest, $this>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'jenis_cuti_id');
    }
}
