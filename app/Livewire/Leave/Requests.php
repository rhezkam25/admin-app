<?php

namespace App\Livewire\Leave;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Leave\LeaveBalanceService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Cuti & Izin')]
class Requests extends Component
{
    use WithFileUploads;

    public ?int $jenisCutiId = null;

    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public string $alasanCuti = '';

    public $lampiran = null;

    public ?int $idTinjau = null;

    public string $aksiTinjau = '';

    public string $alasanTinjau = '';

    public function mount(): void
    {
        $this->tanggalMulai = now()->addDay()->toDateString();
        $this->tanggalSelesai = now()->addDay()->toDateString();
    }

    public function ajukan(): void
    {
        $pegawai = auth()->user()?->employee;

        if ($pegawai === null) {
            $this->addError('pengajuan_cuti', 'Akun ini tidak terhubung ke data pegawai.');

            return;
        }

        Gate::authorize('create', LeaveRequest::class);

        $this->validate([
            'jenisCutiId' => ['required', 'exists:jenis_cuti,id'],
            'tanggalMulai' => ['required', 'date'],
            'tanggalSelesai' => ['required', 'date', 'after_or_equal:tanggalMulai'],
            'alasanCuti' => ['required', 'string', 'max:2000'],
            'lampiran' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $jenisCuti = LeaveType::query()->where('aktif', true)->findOrFail($this->jenisCutiId);

        if ($jenisCuti->wajib_lampiran && $this->lampiran === null) {
            $this->addError('lampiran', 'Jenis cuti/izin ini memerlukan lampiran.');

            return;
        }

        $lampiranPath = $this->lampiran?->store('lampiran-cuti', 'local');

        try {
            app(LeaveBalanceService::class)->ajukan(
                $pegawai,
                $jenisCuti,
                $this->tanggalMulai,
                $this->tanggalSelesai,
                $this->alasanCuti,
                $lampiranPath,
                auth()->user(),
            );

            $this->reset(['jenisCutiId', 'alasanCuti', 'lampiran']);
            $this->tanggalMulai = now()->addDay()->toDateString();
            $this->tanggalSelesai = now()->addDay()->toDateString();
            session()->flash('status', 'Pengajuan cuti/izin berhasil dikirim.');
        } catch (ValidationException $exception) {
            $this->addError('pengajuan_cuti', $exception->validator->errors()->first());
        }
    }

    public function bukaTinjau(int $id, string $aksi): void
    {
        $pengajuan = LeaveRequest::query()->findOrFail($id);

        Gate::authorize('approve', $pengajuan);

        $this->idTinjau = $pengajuan->id;
        $this->aksiTinjau = $aksi;
        $this->alasanTinjau = '';
    }

    public function prosesTinjau(): void
    {
        if ($this->idTinjau === null) {
            return;
        }

        $pengajuan = LeaveRequest::query()->findOrFail($this->idTinjau);
        Gate::authorize('approve', $pengajuan);

        $this->validate([
            'alasanTinjau' => $this->aksiTinjau === 'tolak' ? ['required', 'string', 'max:2000'] : ['nullable', 'string', 'max:2000'],
        ]);

        try {
            if ($this->aksiTinjau === 'setujui') {
                app(LeaveBalanceService::class)->setujui(
                    $pengajuan,
                    auth()->user(),
                    $this->alasanTinjau !== '' ? $this->alasanTinjau : null,
                );
            } else {
                app(LeaveBalanceService::class)->tolak($pengajuan, auth()->user(), $this->alasanTinjau);
            }

            $this->reset(['idTinjau', 'aksiTinjau', 'alasanTinjau']);
            session()->flash('status', 'Pengajuan cuti/izin berhasil diproses.');
        } catch (ValidationException $exception) {
            $this->addError('pengajuan_cuti', $exception->validator->errors()->first());
        }
    }

    public function batalkan(int $id): void
    {
        $pengajuan = LeaveRequest::query()->findOrFail($id);
        Gate::authorize('cancel', $pengajuan);

        try {
            app(LeaveBalanceService::class)->batalkan($pengajuan, auth()->user(), 'Dibatalkan dari daftar pengajuan.');
            session()->flash('status', 'Pengajuan cuti/izin dibatalkan.');
        } catch (ValidationException $exception) {
            $this->addError('pengajuan_cuti', $exception->validator->errors()->first());
        }
    }

    public function render()
    {
        $pengguna = auth()->user();
        $pegawai = $pengguna?->employee;
        $daftarCuti = collect();
        $jenisCuti = LeaveType::query()->where('aktif', true)->orderBy('nama')->get();
        $saldo = null;
        $pratinjau = null;

        if ($pengguna !== null) {
            $daftarCuti = LeaveRequest::query()
                ->with(['employee', 'leaveType', 'reviewer'])
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
                ->orderByDesc('tanggal_mulai')
                ->limit(100)
                ->get();
        }

        if ($pegawai !== null) {
            $saldo = app(LeaveBalanceService::class)->ambilAtauBuatSaldo($pegawai, now());

            if ($this->jenisCutiId !== null) {
                $jenis = $jenisCuti->firstWhere('id', $this->jenisCutiId);

                if ($jenis !== null) {
                    $pratinjau = app(LeaveBalanceService::class)->pratinjau(
                        $pegawai,
                        $jenis,
                        $this->tanggalMulai,
                        $this->tanggalSelesai,
                    );
                }
            }
        }

        return view('livewire.leave.requests', [
            'pengguna' => $pengguna,
            'pegawai' => $pegawai,
            'daftarCuti' => $daftarCuti,
            'jenisCuti' => $jenisCuti,
            'saldo' => $saldo,
            'pratinjau' => $pratinjau,
        ]);
    }
}
