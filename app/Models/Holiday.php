<?php

namespace App\Models;

use App\Enums\NegaraLibur;
use Database\Factories\HolidayFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $tanggal
 * @property NegaraLibur $negara
 * @property string $nama
 * @property string|null $jenis
 * @property bool $diamati
 * @property bool $aktif
 * @property string|null $sumber
 */
class Holiday extends Model
{
    /** @use HasFactory<HolidayFactory> */
    use HasFactory;

    protected $table = 'hari_libur';

    protected $fillable = [
        'tanggal',
        'negara',
        'nama',
        'jenis',
        'diamati',
        'aktif',
        'sumber',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'negara' => NegaraLibur::class,
            'diamati' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true)->where('diamati', true);
    }

    public function scopePadaTanggal(Builder $query, string $tanggal): Builder
    {
        return $query->whereDate('tanggal', $tanggal);
    }
}
