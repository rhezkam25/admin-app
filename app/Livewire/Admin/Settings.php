<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Services\Audit\AuditLogger;
use App\Services\Overtime\OvertimePeriodService;
use App\Services\Period\PeriodService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pengaturan')]
class Settings extends Component
{
    public string $tarifHariKerja = '6.00';

    public string $tarifLibur = '9.00';

    public float $batasPersen = 28;

    public int $tenggangMenit = 30;

    public int $istirahatLiburMenit = 60;

    public int $ambangIstirahatLiburMenit = 360;

    public int $toleransiTerlambatMenit = 0;

    public int $hakCutiHari = 12;

    public int $maksCarryOverHari = 6;

    public int $maksSaldoCutiHari = 18;

    public function mount(): void
    {
        Gate::authorize('kelola-pengaturan');

        $this->tarifHariKerja = number_format(((int) Setting::get('lembur.tarif_hari_kerja_sen', 600)) / 100, 2, '.', '');
        $this->tarifLibur = number_format(((int) Setting::get('lembur.tarif_libur_sen', 900)) / 100, 2, '.', '');
        $this->batasPersen = (float) Setting::get('lembur.batas_persen', 28);
        $this->tenggangMenit = (int) Setting::get('lembur.tenggang_menit', 30);
        $this->istirahatLiburMenit = (int) Setting::get('lembur.menit_istirahat_libur', 60);
        $this->ambangIstirahatLiburMenit = (int) Setting::get('lembur.ambang_istirahat_libur_menit', 360);
        $this->toleransiTerlambatMenit = (int) Setting::get('absensi.toleransi_terlambat_menit', 0);
        $this->hakCutiHari = (int) Setting::get('cuti.hak_hari', 12);
        $this->maksCarryOverHari = (int) Setting::get('cuti.maks_carry_over_hari', 6);
        $this->maksSaldoCutiHari = (int) Setting::get('cuti.maks_saldo_hari', 18);
    }

    public function simpan(): void
    {
        Gate::authorize('kelola-pengaturan');

        $this->validate([
            'tarifHariKerja' => ['required', 'numeric', 'min:0'],
            'tarifLibur' => ['required', 'numeric', 'min:0'],
            'batasPersen' => ['required', 'numeric', 'min:0', 'max:100'],
            'tenggangMenit' => ['required', 'integer', 'min:0', 'max:240'],
            'istirahatLiburMenit' => ['required', 'integer', 'min:0', 'max:240'],
            'ambangIstirahatLiburMenit' => ['required', 'integer', 'min:0', 'max:1440'],
            'toleransiTerlambatMenit' => ['required', 'integer', 'min:0', 'max:240'],
            'hakCutiHari' => ['required', 'integer', 'min:0', 'max:60'],
            'maksCarryOverHari' => ['required', 'integer', 'min:0', 'max:60'],
            'maksSaldoCutiHari' => ['required', 'integer', 'min:0', 'max:120'],
        ]);

        try {
            app(PeriodService::class)->pastikanTerbuka(now());
        } catch (ValidationException $exception) {
            $this->addError('periode', $exception->validator->errors()->first());

            return;
        }

        $nilai = [
            'lembur.tarif_hari_kerja_sen' => (int) round((float) $this->tarifHariKerja * 100),
            'lembur.tarif_libur_sen' => (int) round((float) $this->tarifLibur * 100),
            'lembur.batas_persen' => $this->batasPersen,
            'lembur.tenggang_menit' => $this->tenggangMenit,
            'lembur.menit_istirahat_libur' => $this->istirahatLiburMenit,
            'lembur.ambang_istirahat_libur_menit' => $this->ambangIstirahatLiburMenit,
            'absensi.toleransi_terlambat_menit' => $this->toleransiTerlambatMenit,
            'cuti.hak_hari' => $this->hakCutiHari,
            'cuti.maks_carry_over_hari' => $this->maksCarryOverHari,
            'cuti.maks_saldo_hari' => $this->maksSaldoCutiHari,
        ];

        foreach ($nilai as $kunci => $isi) {
            Setting::put($kunci, $isi, 'kebijakan', auth()->id());
        }

        app(AuditLogger::class)->catat(
            auth()->user(),
            'pengaturan.ubah',
            null,
            [],
            $nilai,
            'Perubahan kebijakan lembur/cuti/absensi.',
        );

        app(OvertimePeriodService::class)->hitungUlangSemua(now()->format('Y-m'));

        session()->flash('status', 'Pengaturan berhasil disimpan dan lembur bulan ini dihitung ulang.');
    }

    public function render()
    {
        return view('livewire.admin.settings');
    }
}
