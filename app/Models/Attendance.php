<?php

namespace App\Models;

use App\Enums\JenisHari;
use App\Enums\StatusAbsensi;
use Database\Factories\AttendanceFactory;
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
 * @property int $pegawai_id
 * @property Carbon $tanggal_kerja
 * @property JenisHari $jenis_hari
 * @property StatusAbsensi $status
 * @property Carbon|null $jam_masuk
 * @property Carbon|null $jam_pulang
 * @property string|null $ip_masuk
 * @property string|null $ip_pulang
 * @property string|null $user_agent_masuk
 * @property string|null $user_agent_pulang
 * @property int $menit_terlambat
 * @property int $menit_pulang_cepat
 * @property bool $belum_lengkap
 * @property array<string, mixed>|null $tanda_anomali
 * @property string|null $catatan
 * @property-read Employee|null $employee
 * @property-read Collection<int, AttendanceCorrection> $corrections
 * @property-read OvertimeRecord|null $overtimeRecord
 */
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    protected $table = 'absensi';

    protected $fillable = [
        'pegawai_id',
        'tanggal_kerja',
        'jenis_hari',
        'status',
        'jam_masuk',
        'jam_pulang',
        'ip_masuk',
        'ip_pulang',
        'user_agent_masuk',
        'user_agent_pulang',
        'menit_terlambat',
        'menit_pulang_cepat',
        'belum_lengkap',
        'tanda_anomali',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_kerja' => 'date',
            'jenis_hari' => JenisHari::class,
            'status' => StatusAbsensi::class,
            'jam_masuk' => 'datetime',
            'jam_pulang' => 'datetime',
            'menit_terlambat' => 'integer',
            'menit_pulang_cepat' => 'integer',
            'belum_lengkap' => 'boolean',
            'tanda_anomali' => 'array',
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
     * @return HasMany<AttendanceCorrection, $this>
     */
    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class, 'absensi_id');
    }

    /**
     * @return HasOne<OvertimeRecord, $this>
     */
    public function overtimeRecord(): HasOne
    {
        return $this->hasOne(OvertimeRecord::class, 'absensi_id');
    }

    public function scopeUntukTanggal(Builder $query, string $tanggal): Builder
    {
        return $query->whereDate('tanggal_kerja', $tanggal);
    }

    public function isIncomplete(): bool
    {
        return $this->belum_lengkap || ($this->jam_masuk !== null && $this->jam_pulang === null);
    }
}
