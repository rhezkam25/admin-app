<?php

namespace App\Livewire;

use App\Enums\StatusAbsensi;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimePeriodSummary;
use App\Models\OvertimeRequest;
use App\Models\PayrollPeriod;
use App\Services\Leave\LeaveBalanceService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $tanggalDashboard = '';

    public ?int $departemenDashboard = null;

    public function mount(): void
    {
        $this->tanggalDashboard = now()->toDateString();
    }

    public function render()
    {
        $pengguna = auth()->user();
        $pegawai = $pengguna?->employee;
        $hariIni = now()->toDateString();
        $tanggalHr = $this->tanggalDashboard !== '' ? $this->tanggalDashboard : $hariIni;
        $periode = now()->format('Y-m');

        $absensiHariIni = null;
        $saldoCuti = null;
        $ringkasanLembur = null;
        $cutiMenunggu = collect();
        $lemburMenunggu = collect();
        $ringkasanHr = null;
        $periodePenggajian = null;

        if ($pegawai !== null) {
            $absensiHariIni = Attendance::query()
                ->where('pegawai_id', $pegawai->id)
                ->whereDate('tanggal_kerja', $hariIni)
                ->first();

            $saldoCuti = app(LeaveBalanceService::class)->ambilAtauBuatSaldo($pegawai, now());

            $ringkasanLembur = OvertimePeriodSummary::query()
                ->where('pegawai_id', $pegawai->id)
                ->where('periode', $periode)
                ->first();
        }

        if ($pengguna?->isAdmin()) {
            $filterDepartemen = fn ($query) => $query->when(
                $this->departemenDashboard !== null,
                fn ($sub) => $sub->whereHas('employee', fn ($pegawaiQuery) => $pegawaiQuery->where('departemen_id', $this->departemenDashboard)),
            );

            $ringkasanHr = [
                'total_pegawai' => Employee::query()
                    ->aktif()
                    ->when($this->departemenDashboard !== null, fn ($query) => $query->where('departemen_id', $this->departemenDashboard))
                    ->count(),
                'hadir' => $filterDepartemen(Attendance::query())
                    ->whereDate('tanggal_kerja', $tanggalHr)
                    ->where('status', StatusAbsensi::Hadir)
                    ->count(),
                'terlambat' => $filterDepartemen(Attendance::query())
                    ->whereDate('tanggal_kerja', $tanggalHr)
                    ->where('menit_terlambat', '>', 0)
                    ->count(),
                'belum_lengkap' => $filterDepartemen(Attendance::query())
                    ->whereDate('tanggal_kerja', $tanggalHr)
                    ->where('belum_lengkap', true)
                    ->count(),
            ];

            $cutiMenunggu = LeaveRequest::query()
                ->with('employee')
                ->menunggu()
                ->orderBy('tanggal_mulai')
                ->limit(5)
                ->get();

            $lemburMenunggu = OvertimeRequest::query()
                ->with('employee')
                ->menunggu()
                ->orderBy('tanggal_kerja')
                ->limit(5)
                ->get();

            $periodePenggajian = PayrollPeriod::query()
                ->where('periode', $periode)
                ->first();
        } elseif ($pengguna?->isAtasan() && $pengguna->pegawai_id !== null) {
            $bawahanIds = Employee::query()
                ->where('atasan_id', $pengguna->pegawai_id)
                ->pluck('id');

            $cutiMenunggu = LeaveRequest::query()
                ->with('employee')
                ->whereIn('pegawai_id', $bawahanIds)
                ->menunggu()
                ->orderBy('tanggal_mulai')
                ->limit(5)
                ->get();

            $lemburMenunggu = OvertimeRequest::query()
                ->with('employee')
                ->whereIn('pegawai_id', $bawahanIds)
                ->menunggu()
                ->orderBy('tanggal_kerja')
                ->limit(5)
                ->get();
        }

        return view('livewire.dashboard', [
            'pengguna' => $pengguna,
            'pegawai' => $pegawai,
            'absensiHariIni' => $absensiHariIni,
            'saldoCuti' => $saldoCuti,
            'ringkasanLembur' => $ringkasanLembur,
            'cutiMenunggu' => $cutiMenunggu,
            'lemburMenunggu' => $lemburMenunggu,
            'ringkasanHr' => $ringkasanHr,
            'periodePenggajian' => $periodePenggajian,
            'periode' => $periode,
            'tanggalHr' => $tanggalHr,
            'departemenHr' => Department::query()->where('aktif', true)->orderBy('nama')->get(),
        ]);
    }
}
