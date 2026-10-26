<?php

namespace App\Models;

use App\Enums\Peran;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $username
 * @property string $nama
 * @property string $password
 * @property Peran $peran
 * @property int|null $pegawai_id
 * @property bool $aktif
 * @property bool $wajib_ganti_password
 * @property Carbon|null $terakhir_masuk_pada
 * @property-read Employee|null $employee
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'nama',
        'password',
        'peran',
        'pegawai_id',
        'aktif',
        'wajib_ganti_password',
        'terakhir_masuk_pada',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'peran' => Peran::class,
            'aktif' => 'boolean',
            'wajib_ganti_password' => 'boolean',
            'terakhir_masuk_pada' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pegawai_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->peran === Peran::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->peran->isAdmin();
    }

    public function isAtasan(): bool
    {
        return $this->peran === Peran::Atasan;
    }

    public function isPegawai(): bool
    {
        return $this->peran === Peran::Pegawai;
    }
}
