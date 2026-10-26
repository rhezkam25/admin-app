<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\Leave\LeaveBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveBalanceService $layanan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->layanan = app(LeaveBalanceService::class);
    }

    public function test_pegawai_tahun_pertama_belum_mendapat_cuti_tahunan(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2026-01-01']);

        $this->assertNull($this->layanan->tahunCutiMulai($pegawai, '2026-06-01'));
        $this->assertNull($this->layanan->ambilAtauBuatSaldo($pegawai, '2026-06-01'));
    }

    public function test_saldo_awal_dua_belas_hari(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);

        $saldo = $this->layanan->ambilAtauBuatSaldo($pegawai, '2026-10-27');

        $this->assertNotNull($saldo);
        $this->assertSame(12, $saldo->hak_hari);
        $this->assertSame(0, $saldo->carry_over_hari);
        $this->assertSame(12, $saldo->sisa_hari);
    }

    public function test_carry_over_dibatasi_enam_hari_dan_saldo_maksimal_delapan_belas(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);

        LeaveBalance::factory()->create([
            'pegawai_id' => $pegawai->id,
            'awal_tahun_cuti' => '2025-01-01',
            'hak_hari' => 12,
            'carry_over_hari' => 0,
            'terpakai_hari' => 2,
            'tertunda_hari' => 0,
            'penyesuaian_hari' => 0,
            'sisa_hari' => 10,
            'kedaluwarsa_pada' => '2026-01-01',
        ]);

        $saldo = $this->layanan->ambilAtauBuatSaldo($pegawai, '2026-01-02');

        $this->assertNotNull($saldo);
        $this->assertSame(6, $saldo->carry_over_hari);
        $this->assertSame(18, $saldo->sisa_hari);
    }

    public function test_hari_kerja_mengecualikan_akhir_pekan_dan_hari_libur(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);
        $jenisCuti = LeaveType::factory()->tahunan()->create();

        Holiday::factory()->create([
            'tanggal' => '2026-11-02',
            'negara' => 'ID',
            'aktif' => true,
            'diamati' => true,
        ]);

        $pratinjau = $this->layanan->pratinjau(
            $pegawai,
            $jenisCuti,
            CarbonImmutable::parse('2026-10-30'),
            CarbonImmutable::parse('2026-11-02'),
        );

        $this->assertSame(1, $pratinjau['hari_kerja']);
        $this->assertTrue($pratinjau['cukup']);
    }

    public function test_pengajuan_dan_persetujuan_mengurangi_saldo(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);
        $atasan = Employee::factory()->create();
        $pegawai->update(['atasan_id' => $atasan->id]);
        $penggunaAtasan = User::factory()->create(['pegawai_id' => $atasan->id]);
        $jenisCuti = LeaveType::factory()->tahunan()->create();

        $pengajuan = $this->layanan->ajukan(
            $pegawai,
            $jenisCuti,
            CarbonImmutable::parse('2026-10-26'),
            CarbonImmutable::parse('2026-10-26'),
            'Keperluan keluarga.',
        );

        $saldo = $this->layanan->ambilAtauBuatSaldo($pegawai, '2026-10-26');
        $this->assertNotNull($saldo);
        $this->assertSame(1, $saldo->tertunda_hari);

        $this->layanan->setujui($pengajuan, $penggunaAtasan);

        $saldo = $this->layanan->ambilAtauBuatSaldo($pegawai, '2026-10-26');
        $this->assertNotNull($saldo);
        $this->assertSame(0, $saldo->tertunda_hari);
        $this->assertSame(1, $saldo->terpakai_hari);
        $this->assertSame(11, $saldo->sisa_hari);
        $this->assertSame('disetujui', $pengajuan->fresh()->status->value);
    }

    public function test_pemohon_tidak_dapat_menyetujui_sendiri(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);
        $pengguna = User::factory()->create(['pegawai_id' => $pegawai->id]);
        $jenisCuti = LeaveType::factory()->tahunan()->create();

        $pengajuan = $this->layanan->ajukan(
            $pegawai,
            $jenisCuti,
            CarbonImmutable::parse('2026-10-26'),
            CarbonImmutable::parse('2026-10-26'),
            'Keperluan keluarga.',
            null,
            $pengguna,
        );

        $this->expectException(ValidationException::class);

        $this->layanan->setujui($pengajuan, $pengguna);
    }

    public function test_saldo_tidak_cukup_ditolak(): void
    {
        $pegawai = Employee::factory()->create(['tanggal_masuk' => '2020-01-01']);

        LeaveBalance::factory()->create([
            'pegawai_id' => $pegawai->id,
            'awal_tahun_cuti' => '2026-01-01',
            'hak_hari' => 1,
            'carry_over_hari' => 0,
            'terpakai_hari' => 0,
            'tertunda_hari' => 0,
            'penyesuaian_hari' => 0,
            'sisa_hari' => 1,
            'kedaluwarsa_pada' => '2027-01-01',
        ]);

        $jenisCuti = LeaveType::factory()->tahunan()->create();

        $this->expectException(ValidationException::class);

        $this->layanan->ajukan(
            $pegawai,
            $jenisCuti,
            CarbonImmutable::parse('2026-10-26'),
            CarbonImmutable::parse('2026-10-27'),
            'Keperluan keluarga.',
        );
    }
}
