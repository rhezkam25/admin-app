<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Models\OvertimePeriodSummary;
use App\Services\Attendance\AttendanceService;
use App\Services\Holiday\HolidayService;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Schedule\ScheduleService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Absensi')]
class Clock extends Component
{
    public string $catatan = '';

    public function masuk(): void
    {
        $pegawai = auth()->user()?->employee;

        if ($pegawai === null) {
            $this->addError('absensi', 'Akun ini tidak terhubung ke data pegawai.');

            return;
        }

        try {
            app(AttendanceService::class)->masuk(
                $pegawai,
                request()->ip(),
                request()->userAgent(),
            );

            session()->flash('status', 'Absensi masuk berhasil dicatat.');
        } catch (ValidationException $exception) {
            $this->addError('absensi', $exception->validator->errors()->first());
        }
    }

    public function pulang(): void
    {
        $pegawai = auth()->user()?->employee;

        if ($pegawai === null) {
            $this->addError('absensi', 'Akun ini tidak terhubung ke data pegawai.');

            return;
        }

        try {
            app(AttendanceService::class)->pulang(
                $pegawai,
                request()->ip(),
                request()->userAgent(),
            );

            session()->flash('status', 'Absensi pulang berhasil dicatat.');
        } catch (ValidationException $exception) {
            $this->addError('absensi', $exception->validator->errors()->first());
        }
    }

    public function render()
    {
        $pengguna = auth()->user();
        $pegawai = $pengguna?->employee;
        $hariIni = now();

        $absensi = null;
        $jadwal = null;
        $jenisHari = null;
        $riwayat = collect();
        $saldoCuti = null;
        $ringkasanLembur = null;

        if ($pegawai !== null) {
            $absensi = Attendance::query()
                ->where('pegawai_id', $pegawai->id)
                ->whereDate('tanggal_kerja', $hariIni->toDateString())
                ->first();

            $jadwal = app(ScheduleService::class)->hariUntuk($pegawai, $hariIni);
            $jenisHari = app(HolidayService::class)->jenisHari($hariIni);
            $riwayat = Attendance::query()
                ->where('pegawai_id', $pegawai->id)
                ->orderByDesc('tanggal_kerja')
                ->limit(30)
                ->get();
            $saldoCuti = app(LeaveBalanceService::class)->ambilAtauBuatSaldo($pegawai, $hariIni);
            $ringkasanLembur = OvertimePeriodSummary::query()
                ->where('pegawai_id', $pegawai->id)
                ->where('periode', $hariIni->format('Y-m'))
                ->first();
        }

        return view('livewire.attendance.clock', [
            'pengguna' => $pengguna,
            'pegawai' => $pegawai,
            'absensi' => $absensi,
            'jadwal' => $jadwal,
            'jenisHari' => $jenisHari,
            'riwayat' => $riwayat,
            'saldoCuti' => $saldoCuti,
            'ringkasanLembur' => $ringkasanLembur,
            'hariIni' => $hariIni,
        ]);
    }
}
