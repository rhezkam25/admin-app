<?php

namespace App\Models;

use App\Enums\StatusPeriode;
use Database\Factories\PayrollPeriodFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $periode
 * @property StatusPeriode $status
 * @property int|null $ditutup_oleh
 * @property Carbon|null $ditutup_pada
 * @property int|null $dibuka_oleh
 * @property Carbon|null $dibuka_pada
 * @property string|null $alasan_buka
 * @property-read User|null $closedBy
 * @property-read User|null $reopenedBy
 * @property-read Collection<int, OvertimePeriodSummary> $overtimeSummaries
 */
class PayrollPeriod extends Model
{
    /** @use HasFactory<PayrollPeriodFactory> */
    use HasFactory;

    protected $table = 'periode_penggajian';

    protected $fillable = [
        'periode',
        'status',
        'ditutup_oleh',
        'ditutup_pada',
        'dibuka_oleh',
        'dibuka_pada',
        'alasan_buka',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusPeriode::class,
            'ditutup_pada' => 'datetime',
            'dibuka_pada' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditutup_oleh');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuka_oleh');
    }

    /**
     * @return HasMany<OvertimePeriodSummary, $this>
     */
    public function overtimeSummaries(): HasMany
    {
        return $this->hasMany(OvertimePeriodSummary::class, 'periode', 'periode');
    }

    public function isOpen(): bool
    {
        return $this->status === StatusPeriode::Terbuka;
    }
}
