<?php

namespace App\Models;

use Database\Factories\OvertimeRecordFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pegawai_id
 * @property Carbon $tanggal_kerja
 * @property int|null $absensi_id
 * @property int $menit_pagi_aktual
 * @property int $menit_sore_aktual
 * @property int $menit_libur_aktual
 * @property int $menit_aktual
 * @property int $menit_disetujui
 * @property int $menit_dibayar
 * @property int $tarif_sen
 * @property int $nilai_kotor_sen
 * @property int $nilai_dibayar_sen
 * @property int $nilai_hangus_sen
 * @property array<string, mixed>|null $snapshot_aturan
 * @property Carbon|null $dihitung_pada
 * @property-read Employee|null $employee
 * @property-read Attendance|null $attendance
 * @property-read Collection<int, OvertimeRequest> $requests
 */
class OvertimeRecord extends Model
{
    /** @use HasFactory<OvertimeRecordFactory> */
    use HasFactory;

    protected $table = 'catatan_lembur';

    protected $fillable = [
        'pegawai_id',
        'tanggal_kerja',
        'absensi_id',
        'menit_pagi_aktual',
        'menit_sore_aktual',
        'menit_libur_aktual',
        'menit_aktual',
        'menit_disetujui',
        'menit_dibayar',
        'tarif_sen',
        'nilai_kotor_sen',
        'nilai_dibayar_sen',
        'nilai_hangus_sen',
        'snapshot_aturan',
        'dihitung_pada',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_kerja' => 'date',
            'menit_pagi_aktual' => 'integer',
            'menit_sore_aktual' => 'integer',
            'menit_libur_aktual' => 'integer',
            'menit_aktual' => 'integer',
            'menit_disetujui' => 'integer',
            'menit_dibayar' => 'integer',
            'tarif_sen' => 'integer',
            'nilai_kotor_sen' => 'integer',
            'nilai_dibayar_sen' => 'integer',
            'nilai_hangus_sen' => 'integer',
            'snapshot_aturan' => 'array',
            'dihitung_pada' => 'datetime',
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
     * @return BelongsTo<Attendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'absensi_id');
    }

    /**
     * @return HasMany<OvertimeRequest, $this>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class, 'catatan_lembur_id');
    }
}
