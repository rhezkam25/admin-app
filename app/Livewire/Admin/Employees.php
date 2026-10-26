<?php

namespace App\Livewire\Admin;

use App\Enums\Peran;
use App\Enums\StatusKepegawaian;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\Audit\AuditLogger;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Overtime\OvertimePeriodService;
use App\Services\Period\PeriodService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Data Pegawai')]
class Employees extends Component
{
    public string $cari = '';

    public ?int $idEdit = null;

    public string $nip = '';

    public string $nama = '';

    public ?int $departemenId = null;

    public ?int $jabatanId = null;

    public ?int $atasanId = null;

    public ?int $jadwalKerjaId = null;

    public string $statusKepegawaian = 'aktif';

    public string $tanggalMasuk = '';

    public string $tanggalKeluar = '';

    public string $telepon = '';

    public string $alamat = '';

    public ?int $idGajiPegawai = null;

    public string $gajiBerlakuMulai = '';

    public string $gajiPokok = '';

    public ?string $passwordSementara = null;

    public ?int $idSaldoPegawai = null;

    public int $perubahanSaldoHari = 0;

    public string $alasanSaldo = '';

    public function mount(): void
    {
        Gate::authorize('kelola-data-master');
    }

    public function simpan(): void
    {
        Gate::authorize('kelola-data-master');

        $this->validate([
            'nip' => ['required', 'string', 'max:50', Rule::unique('pegawai', 'nip')->ignore($this->idEdit)],
            'nama' => ['required', 'string', 'max:255'],
            'departemenId' => ['nullable', 'exists:departemen,id'],
            'jabatanId' => ['nullable', 'exists:jabatan,id'],
            'atasanId' => ['nullable', 'exists:pegawai,id'],
            'jadwalKerjaId' => ['nullable', 'exists:jadwal_kerja,id'],
            'statusKepegawaian' => ['required', 'in:aktif,nonaktif,resign'],
            'tanggalMasuk' => ['required', 'date'],
            'tanggalKeluar' => ['nullable', 'date', 'after_or_equal:tanggalMasuk'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:2000'],
        ]);

        $data = [
            'nip' => $this->nip,
            'nama' => $this->nama,
            'departemen_id' => $this->departemenId,
            'jabatan_id' => $this->jabatanId,
            'atasan_id' => $this->atasanId,
            'jadwal_kerja_id' => $this->jadwalKerjaId,
            'status_kepegawaian' => $this->statusKepegawaian,
            'tanggal_masuk' => $this->tanggalMasuk,
            'tanggal_keluar' => $this->tanggalKeluar !== '' ? $this->tanggalKeluar : null,
            'telepon' => $this->telepon,
            'alamat' => $this->alamat,
        ];

        if ($this->idEdit !== null) {
            $pegawai = Employee::query()->findOrFail($this->idEdit);
            Gate::authorize('update', $pegawai);
            $nilaiLama = $pegawai->only(array_keys($data));
            $pegawai->update($data);

            app(AuditLogger::class)->catat(auth()->user(), 'pegawai.ubah', $pegawai, $nilaiLama, $data);
            session()->flash('status', 'Data pegawai berhasil diperbarui.');
        } else {
            Gate::authorize('create', Employee::class);
            $pegawai = Employee::query()->create($data);

            app(AuditLogger::class)->catat(auth()->user(), 'pegawai.tambah', $pegawai, [], $data);
            session()->flash('status', 'Pegawai baru berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        Gate::authorize('kelola-data-master');

        $pegawai = Employee::query()->findOrFail($id);

        $this->idEdit = $pegawai->id;
        $this->nip = $pegawai->nip;
        $this->nama = $pegawai->nama;
        $this->departemenId = $pegawai->departemen_id;
        $this->jabatanId = $pegawai->jabatan_id;
        $this->atasanId = $pegawai->atasan_id;
        $this->jadwalKerjaId = $pegawai->jadwal_kerja_id;
        $this->statusKepegawaian = $pegawai->status_kepegawaian->value;
        $this->tanggalMasuk = $pegawai->tanggal_masuk->toDateString();
        $this->tanggalKeluar = $pegawai->tanggal_keluar?->toDateString() ?? '';
        $this->telepon = $pegawai->telepon ?? '';
        $this->alamat = $pegawai->alamat ?? '';
    }

    public function nonaktifkan(int $id): void
    {
        Gate::authorize('kelola-data-master');

        $pegawai = Employee::query()->findOrFail($id);
        Gate::authorize('update', $pegawai);

        $pegawai->update([
            'status_kepegawaian' => StatusKepegawaian::Nonaktif,
            'tanggal_keluar' => now()->toDateString(),
        ]);

        $pegawai->user?->update(['aktif' => false]);

        app(AuditLogger::class)->catat(
            auth()->user(),
            'pegawai.nonaktifkan',
            $pegawai,
            ['status_kepegawaian' => StatusKepegawaian::Aktif->value],
            ['status_kepegawaian' => StatusKepegawaian::Nonaktif->value],
            'Dinonaktifkan dari daftar pegawai.',
        );

        session()->flash('status', 'Pegawai dinonaktifkan.');
    }

    public function buatAkun(int $id): void
    {
        Gate::authorize('kelola-data-master');

        $pegawai = Employee::query()->with('user')->findOrFail($id);

        if ($pegawai->user !== null) {
            $this->addError('akun', 'Pegawai ini sudah memiliki akun.');

            return;
        }

        $username = $pegawai->nip;
        $urutan = 1;

        while (User::query()->where('username', $username)->exists()) {
            $urutan++;
            $username = $pegawai->nip.'-'.$urutan;
        }

        $passwordSementara = Str::password(16);

        $akun = User::query()->create([
            'username' => $username,
            'nama' => $pegawai->nama,
            'password' => $passwordSementara,
            'peran' => Peran::Pegawai,
            'pegawai_id' => $pegawai->id,
            'aktif' => true,
            'wajib_ganti_password' => true,
        ]);

        app(AuditLogger::class)->catat(auth()->user(), 'akun.buat', $akun, [], ['username' => $username]);

        $this->passwordSementara = $passwordSementara;
        session()->flash('status', 'Akun berhasil dibuat. Password sementara hanya ditampilkan sekali.');
    }

    public function resetPassword(int $id): void
    {
        Gate::authorize('kelola-data-master');

        $pegawai = Employee::query()->with('user')->findOrFail($id);

        if ($pegawai->user === null) {
            $this->addError('akun', 'Pegawai ini belum memiliki akun.');

            return;
        }

        $passwordSementara = Str::password(16);

        $pegawai->user->update([
            'password' => $passwordSementara,
            'aktif' => true,
            'wajib_ganti_password' => true,
        ]);

        app(AuditLogger::class)->catat(
            auth()->user(),
            'akun.reset_password',
            $pegawai->user,
            [],
            ['username' => $pegawai->user->username],
            'Reset password oleh admin.',
        );

        $this->passwordSementara = $passwordSementara;
        session()->flash('status', 'Password sementara berhasil direset. Tampilkan sekali ini.');
    }

    public function pilihGaji(int $id): void
    {
        Gate::authorize('kelola-data-master');

        $this->idGajiPegawai = $id;
        $this->gajiBerlakuMulai = now()->toDateString();
        $this->gajiPokok = '';

        $gajiTerakhir = EmployeeSalary::query()
            ->where('pegawai_id', $id)
            ->orderByDesc('berlaku_mulai')
            ->first();

        if ($gajiTerakhir !== null) {
            $this->gajiPokok = number_format($gajiTerakhir->gaji_pokok_sen / 100, 2, '.', '');
        }
    }

    public function simpanGaji(): void
    {
        Gate::authorize('kelola-data-master');

        if ($this->idGajiPegawai === null) {
            return;
        }

        $this->validate([
            'gajiBerlakuMulai' => ['required', 'date'],
            'gajiPokok' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            app(PeriodService::class)->pastikanTerbuka(now());
        } catch (ValidationException $exception) {
            $this->addError('gajiPokok', $exception->validator->errors()->first());

            return;
        }

        $pegawai = Employee::query()->findOrFail($this->idGajiPegawai);

        $gaji = EmployeeSalary::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('berlaku_mulai', $this->gajiBerlakuMulai)
            ->first();

        if ($gaji === null) {
            $gaji = new EmployeeSalary([
                'pegawai_id' => $pegawai->id,
                'berlaku_mulai' => $this->gajiBerlakuMulai,
            ]);
        }

        $gaji->fill([
            'gaji_pokok_sen' => (int) round((float) $this->gajiPokok * 100),
            'mata_uang' => 'USD',
            'dibuat_oleh' => auth()->id(),
        ])->save();

        app(AuditLogger::class)->catat(
            auth()->user(),
            'gaji.ubah',
            $gaji,
            [],
            ['pegawai_id' => $pegawai->id, 'berlaku_mulai' => $this->gajiBerlakuMulai, 'gaji_pokok_sen' => $gaji->gaji_pokok_sen],
            'Perubahan riwayat gaji pokok.',
        );

        app(OvertimePeriodService::class)->hitungUlangPeriode($pegawai, now()->format('Y-m'));

        session()->flash('status', 'Riwayat gaji berhasil disimpan.');
        $this->gajiPokok = '';
    }

    public function pilihSaldo(int $id): void
    {
        Gate::authorize('kelola-data-master');

        $this->idSaldoPegawai = $id;
        $this->perubahanSaldoHari = 0;
        $this->alasanSaldo = '';
    }

    public function simpanSaldo(): void
    {
        Gate::authorize('kelola-data-master');

        if ($this->idSaldoPegawai === null) {
            return;
        }

        $this->validate([
            'perubahanSaldoHari' => ['required', 'integer', 'not_in:0'],
            'alasanSaldo' => ['required', 'string', 'max:2000'],
        ]);

        $pegawai = Employee::query()->findOrFail($this->idSaldoPegawai);
        $saldo = app(LeaveBalanceService::class)->ambilAtauBuatSaldo($pegawai, now());

        if ($saldo === null) {
            $this->addError('perubahanSaldoHari', 'Saldo cuti belum tersedia pada tahun pertama kerja.');

            return;
        }

        app(LeaveBalanceService::class)->sesuaikan(
            $saldo,
            $this->perubahanSaldoHari,
            auth()->user(),
            $this->alasanSaldo,
        );

        $this->reset(['perubahanSaldoHari', 'alasanSaldo']);
        session()->flash('status', 'Saldo cuti berhasil disesuaikan.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'idEdit', 'nip', 'nama', 'departemenId', 'jabatanId', 'atasanId', 'jadwalKerjaId',
            'statusKepegawaian', 'tanggalMasuk', 'tanggalKeluar', 'telepon', 'alamat',
        ]);
        $this->statusKepegawaian = 'aktif';
    }

    public function render()
    {
        $daftarPegawai = Employee::query()
            ->with(['department', 'position', 'supervisor', 'workSchedule', 'user'])
            ->when($this->cari !== '', function ($query): void {
                $query->where(function ($sub): void {
                    $sub->where('nama', 'like', '%'.$this->cari.'%')
                        ->orWhere('nip', 'like', '%'.$this->cari.'%');
                });
            })
            ->orderBy('nama')
            ->limit(100)
            ->get();

        $riwayatGaji = collect();
        $pegawaiGaji = null;
        $pegawaiSaldo = null;
        $saldoTerpilih = null;

        if ($this->idGajiPegawai !== null) {
            $pegawaiGaji = Employee::query()->find($this->idGajiPegawai);
            $riwayatGaji = EmployeeSalary::query()
                ->where('pegawai_id', $this->idGajiPegawai)
                ->orderByDesc('berlaku_mulai')
                ->get();
        }

        if ($this->idSaldoPegawai !== null) {
            $pegawaiSaldo = Employee::query()->find($this->idSaldoPegawai);

            if ($pegawaiSaldo !== null) {
                $saldoTerpilih = app(LeaveBalanceService::class)->ambilAtauBuatSaldo($pegawaiSaldo, now());
            }
        }

        return view('livewire.admin.employees', [
            'daftarPegawai' => $daftarPegawai,
            'departemen' => Department::query()->where('aktif', true)->orderBy('nama')->get(),
            'jabatan' => Position::query()->where('aktif', true)->orderBy('nama')->get(),
            'jadwal' => WorkSchedule::query()->where('aktif', true)->orderBy('nama')->get(),
            'calonAtasan' => Employee::query()->aktif()->orderBy('nama')->get(),
            'riwayatGaji' => $riwayatGaji,
            'pegawaiGaji' => $pegawaiGaji,
            'pegawaiSaldo' => $pegawaiSaldo,
            'saldoTerpilih' => $saldoTerpilih,
        ]);
    }
}
