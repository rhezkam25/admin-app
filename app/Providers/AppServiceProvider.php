<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('kelola-data-master', fn (User $pengguna): bool => $pengguna->isAdmin());
        Gate::define('kelola-pengaturan', fn (User $pengguna): bool => $pengguna->isAdmin());
        Gate::define('lihat-laporan', fn (User $pengguna): bool => $pengguna->isAdmin() || $pengguna->isAtasan() || $pengguna->pegawai_id !== null);

        Event::listen(Login::class, function (Login $event): void {
            $pengguna = $event->user;

            if ($pengguna instanceof User) {
                $pengguna->forceFill(['terakhir_masuk_pada' => now()])->save();

                app(AuditLogger::class)->catat(
                    $pengguna,
                    'login.berhasil',
                    $pengguna,
                    [],
                    ['username' => $pengguna->username],
                    null,
                    request()->ip(),
                    request()->userAgent(),
                );
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            app(AuditLogger::class)->catat(
                null,
                'login.gagal',
                null,
                [],
                ['username' => $event->credentials['username'] ?? null],
                null,
                request()->ip(),
                request()->userAgent(),
            );
        });
    }
}
