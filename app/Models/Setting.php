<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $kunci
 * @property string|null $grup
 * @property string $tipe
 * @property string|null $nilai
 * @property int|null $diperbarui_oleh
 */
class Setting extends Model
{
    use HasFactory;

    protected $table = 'pengaturan';

    protected $fillable = [
        'kunci',
        'grup',
        'tipe',
        'nilai',
        'diperbarui_oleh',
    ];

    public static function get(string $kunci, mixed $bawaan = null): mixed
    {
        $pengaturan = static::query()->where('kunci', $kunci)->first();

        if ($pengaturan === null || $pengaturan->nilai === null) {
            return $bawaan;
        }

        $nilai = json_decode($pengaturan->nilai, true);

        return match ($pengaturan->tipe) {
            'integer' => (int) $nilai,
            'boolean' => (bool) $nilai,
            'json', 'array' => $nilai,
            default => (string) $nilai,
        };
    }

    public static function put(string $kunci, mixed $nilai, string $grup = 'umum', ?int $diperbaruiOleh = null): self
    {
        $tipe = match (true) {
            is_bool($nilai) => 'boolean',
            is_int($nilai) => 'integer',
            is_array($nilai) => 'json',
            default => 'string',
        };

        return static::query()->updateOrCreate(
            ['kunci' => $kunci],
            [
                'grup' => $grup,
                'tipe' => $tipe,
                'nilai' => json_encode($nilai),
                'diperbarui_oleh' => $diperbaruiOleh,
            ],
        );
    }
}
