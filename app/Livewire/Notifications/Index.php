<?php

namespace App\Livewire\Notifications;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Notifikasi')]
class Index extends Component
{
    public function tandaiDibaca(string $id): void
    {
        $notifikasi = auth()->user()
            ?->notifications()
            ->whereKey($id)
            ->first();

        $notifikasi?->markAsRead();
    }

    public function tandaiSemuaDibaca(): void
    {
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        $daftarNotifikasi = auth()->user()
            ?->notifications()
            ->latest()
            ->limit(50)
            ->get() ?? collect();

        return view('livewire.notifications.index', [
            'daftarNotifikasi' => $daftarNotifikasi,
            'jumlahBelumDibaca' => auth()->user()?->unreadNotifications()->count() ?? 0,
        ]);
    }
}
