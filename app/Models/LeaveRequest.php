<?php

namespace App\Models;

use App\Enums\StatusPermintaan;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pegawai_id
 * @property int $jenis_cuti_id
 * @property Carbon $tanggal_mulai
 * @property Carbon $tanggal_selesai
 * @property int $jumlah_hari_kerja
 * @property string $alasan
 * @property string|null $lampiran_path
 * @property StatusPermintaan $status
 * @property int|null $ditinjau_oleh
 * @property Carbon|null $ditinjau_pada
 * @property string|null $alasan_penolakan
 * @property string|null $alasan_override
 * @property-read Employee|null $employee
 * @property-read LeaveType|null $leaveType
 * @property-read User|null $reviewer
 */
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    protected $table = 'pengajuan_cuti';

    protected $fillable = [
        'pegawai_id',
        'jenis_cuti_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah_hari_kerja',
        'alasan',
        'lampiran_path',
        'status',
        'ditinjau_oleh',
        'ditinjau_pada',
        'alasan_penolakan',
        'alasan_override',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'jumlah_hari_kerja' => 'integer',
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
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'jenis_cuti_id');
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

    public function overlaps(string $mulai, string $selesai): bool
    {
        return $this->tanggal_mulai->toDateString() <= $selesai
            && $this->tanggal_selesai->toDateString() >= $mulai;
    }
}
