<?php

namespace Tests\Feature;

use App\Livewire\Admin\AttendanceCorrections;
use App\Livewire\Admin\Employees;
use App\Livewire\Admin\Holidays;
use App\Livewire\Admin\Periods;
use App\Livewire\Admin\Schedules;
use App\Livewire\Admin\Settings;
use App\Livewire\Attendance\Clock;
use App\Livewire\Audit\Index as AuditIndex;
use App\Livewire\Dashboard;
use App\Livewire\Leave\Requests as LeaveRequests;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Overtime\Requests as OvertimeRequests;
use App\Livewire\Profile\ChangePassword;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\Leave\LeaveBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_dan_halaman_hr_dapat_dirender(): void
    {
        $penggunaHr = User::factory()->adminHr()->create();

        foreach ([
            Dashboard::class,
            Employees::class,
            AttendanceCorrections::class,
            Holidays::class,
            Schedules::class,
            Periods::class,
            Settings::class,
            ReportsIndex::class,
            AuditIndex::class,
        ] as $komponen) {
            Livewire::actingAs($penggunaHr)->test($komponen)->assertStatus(200);
        }
    }

    public function test_halaman_pegawai_dapat_dirender(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);
        $pengguna = User::factory()->create(['pegawai_id' => $pegawai->id]);

        foreach ([
            Dashboard::class,
            Clock::class,
            LeaveRequests::class,
            OvertimeRequests::class,
            NotificationsIndex::class,
            ReportsIndex::class,
        ] as $komponen) {
            Livewire::actingAs($pengguna)->test($komponen)->assertStatus(200);
        }
    }

    public function test_pegawai_dapat_clock_in_melalui_komponen(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);
        $pengguna = User::factory()->create(['pegawai_id' => $pegawai->id]);

        $this->travelTo(CarbonImmutable::parse('2026-10-26 09:00:00'));

        Livewire::actingAs($pengguna)
            ->test(Clock::class)
            ->call('masuk')
            ->assertHasNoErrors();

        $this->assertSame(1, Attendance::query()
            ->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal_kerja', '2026-10-26')
            ->count());
    }

    public function test_hr_dapat_menyesuaikan_saldo_cuti_melalui_komponen(): void
    {
        $penggunaHr = User::factory()->adminHr()->create();
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);
        $saldo = app(LeaveBalanceService::class)->ambilAtauBuatSaldo($pegawai, now());
        $this->assertNotNull($saldo);

        Livewire::actingAs($penggunaHr)
            ->test(Employees::class)
            ->call('pilihSaldo', $pegawai->id)
            ->set('perubahanSaldoHari', 2)
            ->set('alasanSaldo', 'Koreksi saldo dari HR.')
            ->call('simpanSaldo')
            ->assertHasNoErrors();

        $this->assertSame(2, $saldo->fresh()->penyesuaian_hari);
        $this->assertSame(14, $saldo->fresh()->sisa_hari);
    }

    public function test_hr_dapat_mengoreksi_absensi_melalui_komponen(): void
    {
        $penggunaHr = User::factory()->adminHr()->create();
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);
        $absensi = Attendance::factory()->create([
            'pegawai_id' => $pegawai->id,
            'tanggal_kerja' => '2026-10-26',
            'jam_masuk' => '2026-10-26 09:00:00',
            'jam_pulang' => '2026-10-26 17:30:00',
            'status' => 'hadir',
        ]);

        Livewire::actingAs($penggunaHr)
            ->test(AttendanceCorrections::class)
            ->call('pilih', $absensi->id)
            ->set('jamMasuk', '2026-10-26T09:30')
            ->set('alasan', 'Koreksi jam masuk.')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame('09:30', $absensi->fresh()->jam_masuk?->format('H:i'));
        $this->assertDatabaseCount('koreksi_absensi', 1);
    }

    public function test_password_sementara_dapat_diganti(): void
    {
        $pengguna = User::factory()->create([
            'wajib_ganti_password' => true,
            'password' => 'Password123!',
        ]);

        Livewire::actingAs($pengguna)
            ->test(ChangePassword::class)
            ->set('passwordLama', 'Password123!')
            ->set('passwordBaru', 'PasswordBaru123!')
            ->set('passwordBaruKonfirmasi', 'PasswordBaru123!')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertFalse($pengguna->fresh()->wajib_ganti_password);
        $this->assertTrue(Hash::check('PasswordBaru123!', $pengguna->fresh()->password));
    }

    public function test_pengguna_wajib_ganti_password_diarahkan_ke_halaman_password(): void
    {
        $pengguna = User::factory()->create(['wajib_ganti_password' => true]);

        $this->actingAs($pengguna)
            ->get('/dashboard')
            ->assertRedirect(route('password.change'));
    }

    public function test_pegawai_dapat_mengajukan_cuti_dan_lembur_melalui_komponen(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);
        $pengguna = User::factory()->create(['pegawai_id' => $pegawai->id]);
        $jenisCuti = LeaveType::factory()->tahunan()->create();

        Livewire::actingAs($pengguna)
            ->test(LeaveRequests::class)
            ->set('jenisCutiId', $jenisCuti->id)
            ->set('tanggalMulai', '2026-10-26')
            ->set('tanggalSelesai', '2026-10-26')
            ->set('alasanCuti', 'Keperluan keluarga.')
            ->call('ajukan')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('pengajuan_cuti', 1);

        Livewire::actingAs($pengguna)
            ->test(OvertimeRequests::class)
            ->set('tanggalLembur', '2026-10-27')
            ->set('segmen', 'sore')
            ->set('rencanaMulai', '18:00')
            ->set('rencanaSelesai', '20:00')
            ->set('menitDiajukan', 120)
            ->set('alasanLembur', 'Penyelesaian pekerjaan.')
            ->call('ajukan')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('pengajuan_lembur', 1);
    }
}
