<?php

namespace App\Livewire\Admin;

use App\Enums\StatusAbsensi;
use App\Models\Attendance;
use App\Services\Attendance\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Koreksi Absensi')]
class AttendanceCorrections extends Component
{
    public string $tanggal = '';

    public string $cari = '';

    public ?int $idAbsensi = null;

    public string $jamMasuk = '';

    public string $jamPulang = '';

    public string $status = 'hadir';

    public string $catatan = '';

    public string $alasan = '';

    public function mount(): void
    {
        Gate::authorize('kelola-data-master');
        $this->tanggal = now()->toDateString();
    }

    public function pilih(int $id): void
    {
        Gate::authorize('kelola-data-master');

        $absensi = Attendance::query()->with('employee')->findOrFail($id);
        Gate::authorize('correct', $absensi);

        $this->idAbsensi = $absensi->id;
        $this->jamMasuk = $absensi->jam_masuk?->format('Y-m-d\TH:i') ?? '';
        $this->jamPulang = $absensi->jam_pulang?->format('Y-m-d\TH:i') ?? '';
        $this->status = $absensi->status->value;
        $this->catatan = $absensi->catatan ?? '';
        $this->alasan = '';
    }

    public function simpan(): void
    {
        Gate::authorize('kelola-data-master');

        $this->validate([
            'idAbsensi' => ['required', 'exists:absensi,id'],
            'jamMasuk' => ['nullable', 'date'],
            'jamPulang' => ['nullable', 'date', 'after_or_equal:jamMasuk'],
            'status' => ['required', 'in:hadir,alpa,cuti,sakit,izin,dinas_luar,libur,hari_libur,belum_lengkap'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'alasan' => ['required', 'string', 'max:2000'],
        ]);

        $absensi = Attendance::query()->findOrFail($this->idAbsensi);
        Gate::authorize('correct', $absensi);

        try {
            app(AttendanceService::class)->koreksi(
                $absensi,
                [
                    'jam_masuk' => $this->jamMasuk !== '' ? $this->jamMasuk : null,
                    'jam_pulang' => $this->jamPulang !== '' ? $this->jamPulang : null,
                    'status' => $this->status,
                    'catatan' => $this->catatan !== '' ? $this->catatan : null,
                ],
                auth()->user(),
                $this->alasan,
                request()->ip(),
                request()->userAgent(),
            );

            $this->alasan = '';
            session()->flash('status', 'Koreksi absensi berhasil disimpan.');
        } catch (ValidationException $exception) {
            $this->addError('absensi', $exception->validator->errors()->first());
        }
    }

    public function render()
    {
        $daftarAbsensi = Attendance::query()
            ->with('employee')
            ->whereDate('tanggal_kerja', $this->tanggal)
            ->when($this->cari !== '', function ($query): void {
                $query->whereHas('employee', function ($sub): void {
                    $sub->where('nama', 'like', '%'.$this->cari.'%')
                        ->orWhere('nip', 'like', '%'.$this->cari.'%');
                });
            })
            ->orderBy('pegawai_id')
            ->get();

        return view('livewire.admin.attendance-corrections', [
            'daftarAbsensi' => $daftarAbsensi,
            'pilihanStatus' => StatusAbsensi::cases(),
            'absensiTerpilih' => $this->idAbsensi !== null
                ? Attendance::query()->with('employee')->find($this->idAbsensi)
                : null,
            'tanggalTerpilih' => $this->tanggal !== ''
                ? CarbonImmutable::parse($this->tanggal)->translatedFormat('l, d F Y')
                : null,
        ]);
    }
}
