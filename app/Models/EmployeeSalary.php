<?php

namespace App\Models;

use Database\Factories\EmployeeSalaryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pegawai_id
 * @property Carbon $berlaku_mulai
 * @property int $gaji_pokok_sen
 * @property string $mata_uang
 * @property int|null $dibuat_oleh
 * @property-read Employee|null $employee
 * @property-read User|null $creator
 */
class EmployeeSalary extends Model
{
    /** @use HasFactory<EmployeeSalaryFactory> */
    use HasFactory;

    protected $table = 'riwayat_gaji';

    protected $fillable = [
        'pegawai_id',
        'berlaku_mulai',
        'gaji_pokok_sen',
        'mata_uang',
        'dibuat_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'berlaku_mulai' => 'date',
            'gaji_pokok_sen' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
