<?php

namespace App\Models;

use Database\Factories\WorkScheduleFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nama
 * @property string $zona_waktu
 * @property bool $bawaan
 * @property bool $aktif
 * @property-read Collection<int, WorkScheduleDay> $days
 * @property-read Collection<int, Employee> $employees
 */
class WorkSchedule extends Model
{
    /** @use HasFactory<WorkScheduleFactory> */
    use HasFactory;

    protected $table = 'jadwal_kerja';

    protected $fillable = [
        'nama',
        'zona_waktu',
        'bawaan',
        'aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bawaan' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    /**
     * @return HasMany<WorkScheduleDay, $this>
     */
    public function days(): HasMany
    {
        return $this->hasMany(WorkScheduleDay::class, 'jadwal_kerja_id');
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'jadwal_kerja_id');
    }

    public function dayFor(int $hariKe): ?WorkScheduleDay
    {
        return $this->days->firstWhere('hari_ke', $hariKe);
    }
}
