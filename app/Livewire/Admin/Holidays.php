<?php

namespace App\Livewire\Admin;

use App\Models\Holiday;
use App\Services\Attendance\DailyAttendanceService;
use App\Services\Audit\AuditLogger;
use App\Services\Overtime\OvertimePeriodService;
use App\Services\Period\PeriodService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Hari Libur')]
class Holidays extends Component
{
    use WithFileUploads;

    public ?int $idEdit = null;

    public string $tanggal = '';

    public string $negara = 'ID';

    public string $nama = '';

    public string $jenis = 'nasional';

    public bool $diamati = true;

    public bool $aktif = true;

    public $berkasCsv = null;

    public function mount(): void
    {
        Gate::authorize('kelola-data-master');
    }

    public function simpan(): void
    {
        Gate::authorize('kelola-data-master');

        $this->validate([
            'tanggal' => ['required', 'date'],
            'negara' => ['required', 'in:ID,MY'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            app(PeriodService::class)->pastikanTerbuka($this->tanggal);
        } catch (ValidationException $exception) {
            $this->addError('periode', $exception->validator->errors()->first());

            return;
        }

        $data = [
            'tanggal' => $this->tanggal,
            'negara' => $this->negara,
            'nama' => $this->nama,
            'jenis' => $this->jenis,
            'diamati' => $this->diamati,
            'aktif' => $this->aktif,
            'sumber' => 'manual',
        ];

        if ($this->idEdit !== null) {
            $hariLibur = Holiday::query()->findOrFail($this->idEdit);
            $nilaiLama = $hariLibur->only(array_keys($data));
            $hariLibur->update($data);
            app(AuditLogger::class)->catat(auth()->user(), 'hari_libur.ubah', $hariLibur, $nilaiLama, $data);
        } else {
            $hariLibur = Holiday::query()->create($data);
            app(AuditLogger::class)->catat(auth()->user(), 'hari_libur.tambah', $hariLibur, [], $data);
        }

        app(DailyAttendanceService::class)->finalisasi($this->tanggal, true);
        app(OvertimePeriodService::class)->hitungUlangSemua(CarbonImmutable::parse($this->tanggal)->format('Y-m'));

        $this->reset(['idEdit', 'tanggal', 'nama', 'jenis']);
        $this->negara = 'ID';
        $this->diamati = true;
        $this->aktif = true;
        session()->flash('status', 'Hari libur berhasil disimpan.');
    }

    public function edit(int $id): void
    {
        Gate::authorize('kelola-data-master');

        $hariLibur = Holiday::query()->findOrFail($id);

        $this->idEdit = $hariLibur->id;
        $this->tanggal = $hariLibur->tanggal->toDateString();
        $this->negara = $hariLibur->negara->value;
        $this->nama = $hariLibur->nama;
        $this->jenis = $hariLibur->jenis ?? '';
        $this->diamati = $hariLibur->diamati;
        $this->aktif = $hariLibur->aktif;
    }

    public function nonaktifkan(int $id): void
    {
        Gate::authorize('kelola-data-master');

        $hariLibur = Holiday::query()->findOrFail($id);

        try {
            app(PeriodService::class)->pastikanTerbuka($hariLibur->tanggal);
        } catch (ValidationException $exception) {
            $this->addError('periode', $exception->validator->errors()->first());

            return;
        }

        $hariLibur->update(['aktif' => false]);

        app(AuditLogger::class)->catat(
            auth()->user(),
            'hari_libur.nonaktifkan',
            $hariLibur,
            ['aktif' => true],
            ['aktif' => false],
        );

        app(DailyAttendanceService::class)->finalisasi($hariLibur->tanggal, true);
        app(OvertimePeriodService::class)->hitungUlangSemua($hariLibur->tanggal->format('Y-m'));
        session()->flash('status', 'Hari libur dinonaktifkan.');
    }

    public function impor(): void
    {
        Gate::authorize('kelola-data-master');

        $this->validate([
            'berkasCsv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $berkas = fopen($this->berkasCsv->getRealPath(), 'r');
        $jumlah = 0;
        $gagal = 0;
        $barisPertama = true;
        $bulanTerdampak = [];
        $tanggalTerdampak = [];

        if ($berkas === false) {
            $this->addError('berkasCsv', 'Berkas tidak dapat dibaca.');

            return;
        }

        while (($baris = fgetcsv($berkas)) !== false) {
            if ($barisPertama) {
                $barisPertama = false;

                if (isset($baris[0]) && strtolower(trim($baris[0])) === 'tanggal') {
                    continue;
                }
            }

            if (count($baris) < 3) {
                $gagal++;

                continue;
            }

            [$tanggal, $negara, $nama] = array_map('trim', array_slice($baris, 0, 3));
            $jenis = trim($baris[3] ?? 'nasional');

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) !== 1 || ! in_array($negara, ['ID', 'MY'], true)) {
                $gagal++;

                continue;
            }

            try {
                app(PeriodService::class)->pastikanTerbuka($tanggal);
            } catch (ValidationException) {
                $gagal++;

                continue;
            }

            $hariLibur = Holiday::query()
                ->whereDate('tanggal', $tanggal)
                ->where('negara', $negara)
                ->where('nama', $nama)
                ->first();

            if ($hariLibur === null) {
                $hariLibur = new Holiday([
                    'tanggal' => $tanggal,
                    'negara' => $negara,
                    'nama' => $nama,
                ]);
            }

            $hariLibur->fill([
                'jenis' => $jenis !== '' ? $jenis : 'nasional',
                'diamati' => true,
                'aktif' => true,
                'sumber' => 'impor',
            ])->save();

            $bulanTerdampak[CarbonImmutable::parse($tanggal)->format('Y-m')] = true;
            $tanggalTerdampak[$tanggal] = true;
            $jumlah++;
        }

        fclose($berkas);

        foreach (array_keys($tanggalTerdampak) as $tanggalImpor) {
            app(DailyAttendanceService::class)->finalisasi($tanggalImpor, true);
        }

        foreach (array_keys($bulanTerdampak) as $bulan) {
            app(OvertimePeriodService::class)->hitungUlangSemua($bulan);
        }

        app(AuditLogger::class)->catat(
            auth()->user(),
            'hari_libur.impor',
            null,
            [],
            ['jumlah' => $jumlah, 'dilewati' => $gagal],
            'Impor kalender hari libur.',
        );

        $this->reset('berkasCsv');
        session()->flash('status', "Impor selesai: {$jumlah} baris diproses, {$gagal} baris dilewati.");
    }

    public function render()
    {
        $daftarHariLibur = Holiday::query()
            ->orderByDesc('tanggal')
            ->limit(200)
            ->get();

        return view('livewire.admin.holidays', [
            'daftarHariLibur' => $daftarHariLibur,
        ]);
    }
}
