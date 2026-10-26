<?php

namespace App\Models;

use Database\Factories\OvertimePeriodSummaryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pegawai_id
 * @property string $periode
 * @property int $gaji_pokok_sen
 * @property int $batas_sen
 * @property int $nilai_kotor_sen
 * @property int $nilai_dibayar_sen
 * @property int $nilai_hangus_sen
 * @property int $total_menit
 * @property array<string, mixed>|null $snapshot_aturan
 * @property-read Employee|null $employee
 */
class OvertimePeriodSummary extends Model
{
    /** @use HasFactory<OvertimePeriodSummaryFactory> */
    use HasFactory;

    protected $table = 'ringkasan_lembur_bulanan';

    protected $fillable = [
        'pegawai_id',
        'periode',
        'gaji_pokok_sen',
        'batas_sen',
        'nilai_kotor_sen',
        'nilai_dibayar_sen',
        'nilai_hangus_sen',
        'total_menit',
        'snapshot_aturan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gaji_pokok_sen' => 'integer',
            'batas_sen' => 'integer',
            'nilai_kotor_sen' => 'integer',
            'nilai_dibayar_sen' => 'integer',
            'nilai_hangus_sen' => 'integer',
            'total_menit' => 'integer',
            'snapshot_aturan' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pegawai_id');
    }
}
