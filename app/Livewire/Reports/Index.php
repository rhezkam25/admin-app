<?php

namespace App\Livewire\Reports;

use App\Enums\StatusAbsensi;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimePeriodSummary;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Laporan')]
class Index extends Component
{
    public string $dari = '';

    public string $sampai = '';

    public string $periode = '';

    public ?int $departemenId = null;

    public function mount(): void
    {
        $pengguna = auth()->user();

        Gate::authorize('lihat-laporan');

        $this->dari = now()->startOfMonth()->toDateString();
        $this->sampai = now()->toDateString();
        $this->periode = now()->format('Y-m');
    }

    public function render()
    {
        $pengguna = auth()->user();
        $pegawaiIds = $this->pegawaiIds($pengguna);

        $rekapAbsensi = Attendance::query()
            ->with('employee')
            ->whereDate('tanggal_kerja', '>=', $this->dari)
            ->whereDate('tanggal_kerja', '<=', $this->sampai)
            ->when($pegawaiIds !== null, fn ($query) => $query->whereIn('pegawai_id', $pegawaiIds))
            ->when($this->departemenId !== null, fn ($query) => $query->whereHas('employee', fn ($sub) => $sub->where('departemen_id', $this->departemenId)))
            ->get()
            ->groupBy('pegawai_id')
            ->map(function ($baris) {
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
            ->sortBy(fn ($baris) => $baris['pegawai']?->nama)
            ->values();

        $rekapLembur = OvertimePeriodSummary::query()
            ->with('employee')
            ->where('periode', $this->periode)
            ->when($pegawaiIds !== null, fn ($query) => $query->whereIn('pegawai_id', $pegawaiIds))
            ->when($this->departemenId !== null, fn ($query) => $query->whereHas('employee', fn ($sub) => $sub->where('departemen_id', $this->departemenId)))
            ->get()
            ->sortBy(fn ($baris) => $baris->employee?->nama)
            ->values();

        $rekapCuti = LeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->whereYear('tanggal_mulai', now()->year)
            ->when($pegawaiIds !== null, fn ($query) => $query->whereIn('pegawai_id', $pegawaiIds))
            ->when($this->departemenId !== null, fn ($query) => $query->whereHas('employee', fn ($sub) => $sub->where('departemen_id', $this->departemenId)))
            ->orderByDesc('tanggal_mulai')
            ->limit(200)
            ->get();

        return view('livewire.reports.index', [
            'pengguna' => $pengguna,
            'rekapAbsensi' => $rekapAbsensi,
            'rekapLembur' => $rekapLembur,
            'rekapCuti' => $rekapCuti,
            'departemen' => Department::query()->where('aktif', true)->orderBy('nama')->get(),
        ]);
    }

    /**
     * @return array<int, int>|null
     */
    private function pegawaiIds($pengguna): ?array
    {
        if ($pengguna?->isAdmin()) {
            return null;
        }

        if ($pengguna?->isAtasan() && $pengguna->pegawai_id !== null) {
            return Employee::query()
                ->where('atasan_id', $pengguna->pegawai_id)
                ->pluck('id')
                ->push($pengguna->pegawai_id)
                ->all();
        }

        return $pengguna?->pegawai_id !== null ? [$pengguna->pegawai_id] : [];
    }
}
