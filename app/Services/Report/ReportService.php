<?php

namespace App\Services\Report;

use App\Enums\StatusAbsensi;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimePeriodSummary;
use App\Models\User;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return array<int, int>|null
     */
    public function pegawaiIds(User $pengguna): ?array
    {
        if ($pengguna->isAdmin()) {
            return null;
        }

        if ($pengguna->isAtasan() && $pengguna->pegawai_id !== null) {
            return Employee::query()
                ->where('atasan_id', $pengguna->pegawai_id)
                ->pluck('id')
                ->push($pengguna->pegawai_id)
                ->all();
        }

        return $pengguna->pegawai_id !== null ? [$pengguna->pegawai_id] : [];
    }

    /**
     * @return Collection<int, array{
     *     pegawai: Employee|null,
     *     hadir: int,
     *     alpa: int,
     *     cuti: int,
     *     sakit: int,
     *     izin: int,
     *     dinas_luar: int,
     *     terlambat: int,
     *     pulang_cepat: int,
     *     belum_lengkap: int
     * }>
     */
    public function rekapAbsensi(User $pengguna, string $dari, string $sampai, ?int $departemenId = null): Collection
    {
        $pegawaiIds = $this->pegawaiIds($pengguna);

        return Attendance::query()
            ->with('employee')
            ->whereDate('tanggal_kerja', '>=', $dari)
            ->whereDate('tanggal_kerja', '<=', $sampai)
            ->when($pegawaiIds !== null, fn ($query) => $query->whereIn('pegawai_id', $pegawaiIds))
            ->when($departemenId !== null, fn ($query) => $query->whereHas('employee', fn ($sub) => $sub->where('departemen_id', $departemenId)))
            ->get()
            ->groupBy('pegawai_id')
            ->map(function (Collection $baris): array {
                $pertama = $baris->first();

                return [
                    'pegawai' => $pertama->employee,
                    'hadir' => $baris->where('status', StatusAbsensi::Hadir)->count(),
                    'alpa' => $baris->where('status', StatusAbsensi::Alpa)->count(),
                    'cuti' => $baris->where('status', StatusAbsensi::Cuti)->count(),
                    'sakit' => $baris->where('status', StatusAbsensi::Sakit)->count(),
                    'izin' => $baris->where('status', StatusAbsensi::Izin)->count(),
                    'dinas_luar' => $baris->where('status', StatusAbsensi::DinasLuar)->count(),
                    'terlambat' => (int) $baris->sum('menit_terlambat'),
                    'pulang_cepat' => (int) $baris->sum('menit_pulang_cepat'),
                    'belum_lengkap' => $baris->where('belum_lengkap', true)->count(),
                ];
            })
            ->sortBy(fn (array $baris): string => (string) $baris['pegawai']?->nama)
            ->values();
    }

    /**
     * @return Collection<int, OvertimePeriodSummary>
     */
    public function rekapLembur(User $pengguna, string $periode, ?int $departemenId = null): Collection
    {
        $pegawaiIds = $this->pegawaiIds($pengguna);

        return OvertimePeriodSummary::query()
            ->with('employee')
            ->where('periode', $periode)
            ->when($pegawaiIds !== null, fn ($query) => $query->whereIn('pegawai_id', $pegawaiIds))
            ->when($departemenId !== null, fn ($query) => $query->whereHas('employee', fn ($sub) => $sub->where('departemen_id', $departemenId)))
            ->get()
            ->sortBy(fn (OvertimePeriodSummary $baris): string => (string) $baris->employee?->nama)
            ->values();
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function rekapCuti(User $pengguna, int $tahun, ?int $departemenId = null): Collection
    {
        $pegawaiIds = $this->pegawaiIds($pengguna);

        return LeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->whereYear('tanggal_mulai', $tahun)
            ->when($pegawaiIds !== null, fn ($query) => $query->whereIn('pegawai_id', $pegawaiIds))
            ->when($departemenId !== null, fn ($query) => $query->whereHas('employee', fn ($sub) => $sub->where('departemen_id', $departemenId)))
            ->orderByDesc('tanggal_mulai')
            ->limit(1000)
            ->get();
    }

    public function bolehLihatNominal(User $pengguna): bool
    {
        return $pengguna->isAdmin() || $pengguna->isPegawai();
    }
}
