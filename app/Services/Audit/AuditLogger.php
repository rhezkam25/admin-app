<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $nilaiLama
     * @param  array<string, mixed>  $nilaiBaru
     */
    public function catat(
        ?User $pengguna,
        string $peristiwa,
        ?Model $objek = null,
        array $nilaiLama = [],
        array $nilaiBaru = [],
        ?string $alasan = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'pengguna_id' => $pengguna?->id,
            'peristiwa' => $peristiwa,
            'objek_type' => $objek?->getMorphClass(),
            'objek_id' => $objek?->getKey(),
            'nilai_lama' => $nilaiLama === [] ? null : $nilaiLama,
            'nilai_baru' => $nilaiBaru === [] ? null : $nilaiBaru,
            'alasan' => $alasan,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
