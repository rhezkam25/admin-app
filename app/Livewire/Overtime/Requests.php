<?php

namespace App\Livewire\Overtime;

use App\Enums\JenisHari;
use App\Enums\SegmenLembur;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Services\Holiday\HolidayService;
use App\Services\Overtime\OvertimeRequestService;
use App\Services\Schedule\ScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Lembur')]
class Requests extends Component
{
    public string $tanggalLembur = '';

    public string $segmen = 'sore';

    public string $rencanaMulai = '18:00';

    public string $rencanaSelesai = '20:00';

    public int $menitDiajukan = 120;

    public string $alasanLembur = '';

    public ?int $idTinjau = null;

    public string $aksiTinjau = '';

    public string $alasanTinjau = '';

    public int $menitTinjau = 0;

    public function mount(): void
    {
        $this->tanggalLembur = now()->toDateString();
        $this->hitungMenit();
    }

    public function updatedTanggalLembur(): void
    {
        if ($this->tanggalLembur === '') {
            return;
        }

        $tanggal = CarbonImmutable::parse($this->tanggalLembur);
        $jenisHari = app(HolidayService::class)->jenisHari($tanggal);
        $this->segmen = $jenisHari === JenisHari::HariKerja ? 'sore' : 'libur';

        if ($jenisHari === JenisHari::HariKerja) {
            $pegawai = auth()->user()?->employee;
            $mulaiSore = $pegawai !== null
                ? (app(ScheduleService::class)->jamSoreMulai($pegawai, $tanggal) ?? '18:00')
                : '18:00';

            $this->rencanaMulai = $mulaiSore;
            $this->rencanaSelesai = CarbonImmutable::parse($this->tanggalLembur.' '.$mulaiSore)->addMinutes(120)->format('H:i');
        } else {
            $this->rencanaMulai = '09:00';
            $this->rencanaSelesai = '15:00';
        }

        $this->hitungMenit();
    }

    public function updatedRencanaMulai(): void
    {
        $this->hitungMenit();
    }

    public function updatedRencanaSelesai(): void
    {
        $this->hitungMenit();
    }

    public function hitungMenit(): void
    {
        if ($this->tanggalLembur === '' || $this->rencanaMulai === '' || $this->rencanaSelesai === '') {
            return;
        }

        $mulai = CarbonImmutable::parse($this->tanggalLembur.' '.$this->rencanaMulai);
        $selesai = CarbonImmutable::parse($this->tanggalLembur.' '.$this->rencanaSelesai);

        $this->menitDiajukan = max(0, (int) $mulai->diffInMinutes($selesai));
    }

    public function ajukan(): void
    {
        $pegawai = auth()->user()?->employee;

        if ($pegawai === null) {
            $this->addError('pengajuan_lembur', 'Akun ini tidak terhubung ke data pegawai.');

            return;
        }

        Gate::authorize('create', OvertimeRequest::class);

        $this->validate([
            'tanggalLembur' => ['required', 'date'],
            'segmen' => ['required', 'in:pagi,sore,libur'],
            'rencanaMulai' => ['required', 'date_format:H:i'],
            'rencanaSelesai' => ['required', 'date_format:H:i'],
            'menitDiajukan' => ['required', 'integer', 'min:1', 'max:1440'],
            'alasanLembur' => ['required', 'string', 'max:2000'],
        ]);

        try {
            app(OvertimeRequestService::class)->ajukan(
                $pegawai,
                $this->tanggalLembur,
                SegmenLembur::from($this->segmen),
                $this->rencanaMulai,
                $this->rencanaSelesai,
                $this->menitDiajukan,
                $this->alasanLembur,
                auth()->user(),
            );

            $this->alasanLembur = '';
            session()->flash('status', 'Pengajuan lembur berhasil dikirim.');
        } catch (ValidationException $exception) {
            $this->addError('pengajuan_lembur', $exception->validator->errors()->first());
        }
    }

    public function bukaTinjau(int $id, string $aksi): void
    {
        $pengajuan = OvertimeRequest::query()->findOrFail($id);
        Gate::authorize('approve', $pengajuan);

        $this->idTinjau = $pengajuan->id;
        $this->aksiTinjau = $aksi;
        $this->alasanTinjau = '';
        $this->menitTinjau = $pengajuan->menit_diajukan;
    }

    public function prosesTinjau(): void
    {
        if ($this->idTinjau === null) {
            return;
        }

        $pengajuan = OvertimeRequest::query()->findOrFail($this->idTinjau);
        Gate::authorize('approve', $pengajuan);

        $this->validate([
            'alasanTinjau' => $this->aksiTinjau === 'tolak' ? ['required', 'string', 'max:2000'] : ['nullable', 'string', 'max:2000'],
            'menitTinjau' => $this->aksiTinjau === 'setujui' ? ['required', 'integer', 'min:0'] : ['nullable', 'integer', 'min:0'],
        ]);

        try {
            if ($this->aksiTinjau === 'setujui') {
                app(OvertimeRequestService::class)->setujui(
                    $pengajuan,
                    auth()->user(),
                    $this->menitTinjau,
                    $this->alasanTinjau !== '' ? $this->alasanTinjau : null,
                );
            } else {
                app(OvertimeRequestService::class)->tolak($pengajuan, auth()->user(), $this->alasanTinjau);
            }

            $this->reset(['idTinjau', 'aksiTinjau', 'alasanTinjau', 'menitTinjau']);
            session()->flash('status', 'Pengajuan lembur berhasil diproses.');
        } catch (ValidationException $exception) {
            $this->addError('pengajuan_lembur', $exception->validator->errors()->first());
        }
    }

    public function batalkan(int $id): void
    {
        $pengajuan = OvertimeRequest::query()->findOrFail($id);
        Gate::authorize('cancel', $pengajuan);

        try {
            app(OvertimeRequestService::class)->batalkan($pengajuan, auth()->user(), 'Dibatalkan dari daftar pengajuan.');
            session()->flash('status', 'Pengajuan lembur dibatalkan.');
        } catch (ValidationException $exception) {
            $this->addError('pengajuan_lembur', $exception->validator->errors()->first());
        }
    }

    public function render()
    {
        $pengguna = auth()->user();
        $pegawai = $pengguna?->employee;
        $daftarLembur = collect();
        $jadwal = null;
        $jenisHari = null;
        $jamSoreMulai = null;

        if ($pengguna !== null) {
            $daftarLembur = OvertimeRequest::query()
                ->with(['employee', 'reviewer', 'record'])
                ->when(! $pengguna->isAdmin(), function ($query) use ($pengguna): void {
                    if ($pengguna->isAtasan() && $pengguna->pegawai_id !== null) {
                        $bawahanIds = Employee::query()
                            ->where('atasan_id', $pengguna->pegawai_id)
                            ->pluck('id')
                            ->push($pengguna->pegawai_id);

                        $query->whereIn('pegawai_id', $bawahanIds);
                    } else {
                        $query->where('pegawai_id', $pengguna->pegawai_id);
                    }
                })
                ->orderByDesc('tanggal_kerja')
                ->limit(100)
                ->get();
        }

        if ($pegawai !== null && $this->tanggalLembur !== '') {
            $tanggal = CarbonImmutable::parse($this->tanggalLembur);
            $jadwal = app(ScheduleService::class)->hariUntuk($pegawai, $tanggal);
            $jenisHari = app(HolidayService::class)->jenisHari($tanggal);
            $jamSoreMulai = app(ScheduleService::class)->jamSoreMulai($pegawai, $tanggal);
        }

        return view('livewire.overtime.requests', [
            'pengguna' => $pengguna,
            'pegawai' => $pegawai,
            'daftarLembur' => $daftarLembur,
            'jadwal' => $jadwal,
            'jenisHari' => $jenisHari,
            'jamSoreMulai' => $jamSoreMulai,
        ]);
    }
}
