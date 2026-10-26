<?php

namespace App\Livewire\Admin;

use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use App\Services\Audit\AuditLogger;
use App\Services\Overtime\OvertimePeriodService;
use App\Services\Period\PeriodService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Jadwal Kerja')]
class Schedules extends Component
{
    public ?int $idJadwal = null;

    public string $nama = '';

    public string $zonaWaktu = 'Asia/Jakarta';

    public bool $bawaan = false;

    /** @var array<int, array<string, mixed>> */
    public array $hari = [];

    public function mount(): void
    {
        Gate::authorize('kelola-data-master');
        $this->resetHari();
    }

    public function buatBaru(): void
    {
        Gate::authorize('kelola-data-master');

        $this->reset(['idJadwal', 'nama', 'bawaan']);
        $this->zonaWaktu = config('app.timezone', 'Asia/Jakarta');
        $this->resetHari();
    }

    public function pilihJadwal(int $id): void
    {
        Gate::authorize('kelola-data-master');

        $jadwal = WorkSchedule::query()->with('days')->findOrFail($id);

        $this->idJadwal = $jadwal->id;
        $this->nama = $jadwal->nama;
        $this->zonaWaktu = $jadwal->zona_waktu;
        $this->bawaan = $jadwal->bawaan;

        $this->resetHari();

        foreach ($jadwal->days as $hari) {
            $this->hari[$hari->hari_ke] = [
                'hari_kerja' => $hari->hari_kerja,
                'jam_mulai' => $hari->jam_mulai,
                'jam_selesai' => $hari->jam_selesai,
                'istirahat_mulai' => $hari->istirahat_mulai,
                'istirahat_selesai' => $hari->istirahat_selesai,
            ];
        }
    }

    public function simpan(): void
    {
        Gate::authorize('kelola-data-master');

        $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'zonaWaktu' => ['required', 'string', 'max:64'],
            'hari' => ['required', 'array', 'size:7'],
            'hari.*.hari_kerja' => ['required', 'boolean'],
            'hari.*.jam_mulai' => ['nullable', 'date_format:H:i'],
            'hari.*.jam_selesai' => ['nullable', 'date_format:H:i'],
            'hari.*.istirahat_mulai' => ['nullable', 'date_format:H:i'],
            'hari.*.istirahat_selesai' => ['nullable', 'date_format:H:i'],
        ]);

        try {
            app(PeriodService::class)->pastikanTerbuka(now());
        } catch (ValidationException $exception) {
            $this->addError('periode', $exception->validator->errors()->first());

            return;
        }

        $jadwal = $this->idJadwal !== null
            ? WorkSchedule::query()->findOrFail($this->idJadwal)
            : new WorkSchedule;

        $jadwal->fill([
            'nama' => $this->nama,
            'zona_waktu' => $this->zonaWaktu,
            'bawaan' => $this->bawaan,
            'aktif' => true,
        ]);
        $jadwal->save();

        foreach (range(1, 7) as $hariKe) {
            $data = $this->hari[$hariKe];

            WorkScheduleDay::query()->updateOrCreate(
                ['jadwal_kerja_id' => $jadwal->id, 'hari_ke' => $hariKe],
                [
                    'hari_kerja' => (bool) $data['hari_kerja'],
                    'jam_mulai' => $data['hari_kerja'] ? $data['jam_mulai'] : null,
                    'jam_selesai' => $data['hari_kerja'] ? $data['jam_selesai'] : null,
                    'istirahat_mulai' => $data['hari_kerja'] ? $data['istirahat_mulai'] : null,
                    'istirahat_selesai' => $data['hari_kerja'] ? $data['istirahat_selesai'] : null,
                ],
            );
        }

        app(AuditLogger::class)->catat(
            auth()->user(),
            'jadwal.simpan',
            $jadwal,
            [],
            ['nama' => $jadwal->nama, 'zona_waktu' => $jadwal->zona_waktu, 'bawaan' => $jadwal->bawaan],
        );

        app(OvertimePeriodService::class)->hitungUlangSemua(now()->format('Y-m'));

        $this->idJadwal = $jadwal->id;
        session()->flash('status', 'Jadwal kerja berhasil disimpan.');
    }

    private function resetHari(): void
    {
        $this->hari = [];

        foreach (range(1, 7) as $hariKe) {
            $kerja = $hariKe <= 5;
            $jumat = $hariKe === 5;

            $this->hari[$hariKe] = [
                'hari_kerja' => $kerja,
                'jam_mulai' => $kerja ? '09:00' : null,
                'jam_selesai' => $kerja ? ($jumat ? '18:00' : '17:30') : null,
                'istirahat_mulai' => $kerja ? '12:00' : null,
                'istirahat_selesai' => $kerja ? '13:00' : null,
            ];
        }
    }

    public function render()
    {
        return view('livewire.admin.schedules', [
            'daftarJadwal' => WorkSchedule::query()->orderByDesc('bawaan')->orderBy('nama')->get(),
        ]);
    }
}
