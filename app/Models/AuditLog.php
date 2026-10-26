<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int|null $pengguna_id
 * @property string $peristiwa
 * @property string|null $objek_type
 * @property int|null $objek_id
 * @property array<string, mixed>|null $nilai_lama
 * @property array<string, mixed>|null $nilai_baru
 * @property string|null $alasan
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property-read User|null $pengguna
 * @property-read Model|null $objek
 */
class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'log_audit';

    protected $fillable = [
        'pengguna_id',
        'peristiwa',
        'objek_type',
        'objek_id',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function objek(): MorphTo
    {
        return $this->morphTo('objek');
    }
}
