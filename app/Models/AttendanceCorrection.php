<?php

namespace App\Models;

use Database\Factories\AttendanceCorrectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $absensi_id
 * @property int|null $dikoreksi_oleh
 * @property array<string, mixed> $nilai_lama
 * @property array<string, mixed> $nilai_baru
 * @property string $alasan
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property-read Attendance|null $attendance
 * @property-read User|null $corrector
 */
class AttendanceCorrection extends Model
{
    /** @use HasFactory<AttendanceCorrectionFactory> */
    use HasFactory;

    protected $table = 'koreksi_absensi';

    protected $fillable = [
        'absensi_id',
        'dikoreksi_oleh',
        'nilai_lama',
        'nilai_baru',
        'alasan',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nilai_lama' => 'array',
            'nilai_baru' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Attendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'absensi_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikoreksi_oleh');
    }
}
