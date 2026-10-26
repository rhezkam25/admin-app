<?php

namespace App\Livewire\Admin;

use App\Models\PayrollPeriod;
use App\Services\Period\PeriodService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Periode Penggajian')]
class Periods extends Component
{
    public string $periode = '';

    public string $alasanBuka = '';

    public ?int $idBuka = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', PayrollPeriod::class);
        $this->periode = now()->format('Y-m');
    }

    public function buat(): void
    {
        Gate::authorize('kelola-data-master');

        $this->validate([
            'periode' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        app(PeriodService::class)->ambilAtauBuat($this->periode);
        session()->flash('status', 'Periode berhasil dibuat/dipastikan ada.');
    }

    public function tutup(int $id): void
    {
        $periode = PayrollPeriod::query()->findOrFail($id);
        Gate::authorize('close', $periode);

        try {
            app(PeriodService::class)->tutup($periode->periode, auth()->user());
            session()->flash('status', 'Periode berhasil ditutup.');
        } catch (ValidationException $exception) {
            $this->addError('periode', $exception->validator->errors()->first());
        }
    }

    public function buka(int $id): void
    {
        $periode = PayrollPeriod::query()->findOrFail($id);
        Gate::authorize('reopen', $periode);

        $this->validate([
            'alasanBuka' => ['required', 'string', 'max:2000'],
        ]);

        try {
            app(PeriodService::class)->bukaKembali($periode->periode, auth()->user(), $this->alasanBuka);
            $this->reset(['alasanBuka', 'idBuka']);
            session()->flash('status', 'Periode berhasil dibuka kembali.');
        } catch (ValidationException $exception) {
            $this->addError('periode', $exception->validator->errors()->first());
        }
    }

    public function render()
    {
        return view('livewire.admin.periods', [
            'daftarPeriode' => PayrollPeriod::query()->orderByDesc('periode')->get(),
        ]);
    }
}
