<?php

namespace App\Models;

use Database\Factories\WorkScheduleDayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $jadwal_kerja_id
 * @property int $hari_ke
 * @property bool $hari_kerja
 * @property string|null $jam_mulai
 * @property string|null $jam_selesai
 * @property string|null $istirahat_mulai
 * @property string|null $istirahat_selesai
 * @property-read WorkSchedule|null $schedule
 */
class WorkScheduleDay extends Model
{
    /** @use HasFactory<WorkScheduleDayFactory> */
    use HasFactory;

    protected $table = 'hari_jadwal';

    protected $fillable = [
        'jadwal_kerja_id',
        'hari_ke',
        'hari_kerja',
        'jam_mulai',
        'jam_selesai',
        'istirahat_mulai',
        'istirahat_selesai',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hari_ke' => 'integer',
            'hari_kerja' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<WorkSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'jadwal_kerja_id');
    }
}
