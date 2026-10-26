<?php

namespace App\Models;

use App\Enums\SegmenLembur;
use App\Enums\StatusPermintaan;
use Database\Factories\OvertimeRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pegawai_id
 * @property int|null $catatan_lembur_id
 * @property Carbon $tanggal_kerja
 * @property SegmenLembur $segmen
 * @property string|null $rencana_mulai
 * @property string|null $rencana_selesai
 * @property int $menit_diajukan
 * @property int $menit_disetujui
 * @property StatusPermintaan $status
 * @property int|null $ditinjau_oleh
 * @property Carbon|null $ditinjau_pada
 * @property string $alasan
 * @property string|null $alasan_penolakan
 * @property string|null $alasan_override
 * @property-read Employee|null $employee
 * @property-read OvertimeRecord|null $record
 * @property-read User|null $reviewer
 */
class OvertimeRequest extends Model
{
    /** @use HasFactory<OvertimeRequestFactory> */
    use HasFactory;

    protected $table = 'pengajuan_lembur';

    protected $fillable = [
        'pegawai_id',
        'catatan_lembur_id',
        'tanggal_kerja',
        'segmen',
        'rencana_mulai',
        'rencana_selesai',
        'menit_diajukan',
        'menit_disetujui',
        'status',
        'ditinjau_oleh',
        'ditinjau_pada',
        'alasan',
        'alasan_penolakan',
        'alasan_override',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_kerja' => 'date',
            'segmen' => SegmenLembur::class,
            'menit_diajukan' => 'integer',
            'menit_disetujui' => 'integer',
            'status' => StatusPermintaan::class,
            'ditinjau_pada' => 'datetime',
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
     * @return BelongsTo<OvertimeRecord, $this>
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(OvertimeRecord::class, 'catatan_lembur_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', StatusPermintaan::Diajukan);
    }

    public function scopeUntukPegawai(Builder $query, int $pegawaiId): Builder
    {
        return $query->where('pegawai_id', $pegawaiId);
    }
}
