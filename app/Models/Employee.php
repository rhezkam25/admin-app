<?php

namespace App\Models;

use App\Enums\StatusKepegawaian;
use Carbon\CarbonInterface;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nip
 * @property string $nama
 * @property int|null $departemen_id
 * @property int|null $jabatan_id
 * @property int|null $atasan_id
 * @property int|null $jadwal_kerja_id
 * @property StatusKepegawaian $status_kepegawaian
 * @property Carbon $tanggal_masuk
 * @property Carbon|null $tanggal_keluar
 * @property string|null $telepon
 * @property string|null $alamat
 * @property-read Department|null $department
 * @property-read Position|null $position
 * @property-read Employee|null $supervisor
 * @property-read Collection<int, Employee> $subordinates
 * @property-read WorkSchedule|null $workSchedule
 * @property-read User|null $user
 * @property-read Collection<int, EmployeeSalary> $salaries
 * @property-read Collection<int, Attendance> $attendances
 * @property-read Collection<int, LeaveRequest> $leaveRequests
 * @property-read Collection<int, LeaveBalance> $leaveBalances
 * @property-read Collection<int, OvertimeRequest> $overtimeRequests
 * @property-read Collection<int, OvertimeRecord> $overtimeRecords
 * @property-read Collection<int, OvertimePeriodSummary> $overtimeSummaries
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected $table = 'pegawai';

    protected $fillable = [
        'nip',
        'nama',
        'departemen_id',
        'jabatan_id',
        'atasan_id',
        'jadwal_kerja_id',
        'status_kepegawaian',
        'tanggal_masuk',
        'tanggal_keluar',
        'telepon',
        'alamat',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_kepegawaian' => StatusKepegawaian::class,
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'departemen_id');
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'jabatan_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'atasan_id');
    }

    /**
     * @return HasMany<Employee, $this>
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'atasan_id');
    }

    /**
     * @return BelongsTo<WorkSchedule, $this>
     */
    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'jadwal_kerja_id');
    }

    /**
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'pegawai_id');
    }

    /**
     * @return HasMany<EmployeeSalary, $this>
     */
    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class, 'pegawai_id');
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'pegawai_id');
    }

    /**
     * @return HasMany<LeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'pegawai_id');
    }

    /**
     * @return HasMany<LeaveBalance, $this>
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'pegawai_id');
    }

    /**
     * @return HasMany<OvertimeRequest, $this>
     */
    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class, 'pegawai_id');
    }

    /**
     * @return HasMany<OvertimeRecord, $this>
     */
    public function overtimeRecords(): HasMany
    {
        return $this->hasMany(OvertimeRecord::class, 'pegawai_id');
    }

    /**
     * @return HasMany<OvertimePeriodSummary, $this>
     */
    public function overtimeSummaries(): HasMany
    {
        return $this->hasMany(OvertimePeriodSummary::class, 'pegawai_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status_kepegawaian', StatusKepegawaian::Aktif);
    }

    public function isActive(): bool
    {
        return $this->status_kepegawaian === StatusKepegawaian::Aktif;
    }

    public function salaryOn(?CarbonInterface $date = null): ?EmployeeSalary
    {
        $date ??= now();

        return $this->salaries()
            ->whereDate('berlaku_mulai', '<=', $date->toDateString())
            ->orderByDesc('berlaku_mulai')
            ->first();
    }
}
