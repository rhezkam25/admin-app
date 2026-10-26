<?php

namespace App\Livewire\Audit;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Audit Log')]
class Index extends Component
{
    public string $cari = '';

    public string $dari = '';

    public string $sampai = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', AuditLog::class);
        $this->dari = now()->subMonth()->toDateString();
        $this->sampai = now()->toDateString();
    }

    public function render()
    {
        $daftarLog = AuditLog::query()
            ->with('user')
            ->when($this->cari !== '', function ($query): void {
                $query->where(function ($sub): void {
                    $sub->where('peristiwa', 'like', '%'.$this->cari.'%')
                        ->orWhere('alasan', 'like', '%'.$this->cari.'%');
                });
            })
            ->whereDate('created_at', '>=', $this->dari)
            ->whereDate('created_at', '<=', $this->sampai)
            ->orderByDesc('created_at')
            ->limit(300)
            ->get();

        return view('livewire.audit.index', [
            'daftarLog' => $daftarLog,
        ]);
    }
}
