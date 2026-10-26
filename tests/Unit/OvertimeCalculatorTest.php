<?php

namespace Tests\Unit;

use App\Enums\JenisHari;
use App\Services\Overtime\OvertimeCalculator;
use App\Support\Overtime\InputHitungLembur;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class OvertimeCalculatorTest extends TestCase
{
    private OvertimeCalculator $kalkulator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kalkulator = new OvertimeCalculator;
    }

    public function test_hari_biasa_sore(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-10-26',
            masuk: '09:00',
            pulang: '20:00',
            soreDisetujui: 120,
        ));

        $this->assertSame(120, $hasil->menitSoreAktual);
        $this->assertSame(120, $hasil->menitDibayar);
        $this->assertSame(1200, $hasil->nilaiKotorSen);
    }

    public function test_hari_biasa_pagi(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-10-26',
            masuk: '07:00',
            pulang: '17:30',
            pagiDisetujui: 120,
        ));

        $this->assertSame(120, $hasil->menitPagiAktual);
        $this->assertSame(1200, $hasil->nilaiKotorSen);
    }

    public function test_pagi_dan_sore_dijumlahkan(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-10-26',
            masuk: '07:00',
            pulang: '20:00',
            pagiDisetujui: 120,
            soreDisetujui: 120,
        ));

        $this->assertSame(240, $hasil->menitAktual);
        $this->assertSame(240, $hasil->menitDibayar);
        $this->assertSame(2400, $hasil->nilaiKotorSen);
    }

    public function test_jumat_memakai_jendela_sore_1830(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-10-30',
            masuk: '09:00',
            pulang: '20:00',
            soreDisetujui: 120,
            jamSelesaiKerja: '18:00',
        ));

        $this->assertSame(90, $hasil->menitSoreAktual);
        $this->assertSame(900, $hasil->nilaiKotorSen);
    }

    public function test_jumat_sebelum_tenggang_tidak_dihitung(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-10-30',
            masuk: '09:00',
            pulang: '18:15',
            soreDisetujui: 120,
            jamSelesaiKerja: '18:00',
        ));

        $this->assertSame(0, $hasil->menitSoreAktual);
        $this->assertSame(0, $hasil->nilaiKotorSen);
    }

    public function test_sabtu_enam_jam_tidak_dipotong_istirahat(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-10-31',
            masuk: '09:00',
            pulang: '15:00',
            liburDisetujui: 400,
            jenisHari: JenisHari::AkhirPekan,
        ));

        $this->assertSame(360, $hasil->menitLiburAktual);
        $this->assertSame(360, $hasil->menitDibayar);
        $this->assertSame(5400, $hasil->nilaiKotorSen);
    }

    public function test_hari_libur_tarif_sembilan_dolar(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-12-25',
            masuk: '09:00',
            pulang: '14:00',
            liburDisetujui: 300,
            jenisHari: JenisHari::HariLibur,
        ));

        $this->assertSame(300, $hasil->menitLiburAktual);
        $this->assertSame(4500, $hasil->nilaiKotorSen);
    }

    public function test_aktual_melebihi_persetujuan_dibatasi_persetujuan(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-10-26',
            masuk: '09:00',
            pulang: '21:00',
            soreDisetujui: 120,
        ));

        $this->assertSame(180, $hasil->menitSoreAktual);
        $this->assertSame(120, $hasil->menitDibayar);
        $this->assertSame(1200, $hasil->nilaiKotorSen);
    }

    public function test_aktual_kurang_dari_persetujuan_dibatasi_aktual(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-10-26',
            masuk: '09:00',
            pulang: '18:30',
            soreDisetujui: 120,
        ));

        $this->assertSame(30, $hasil->menitSoreAktual);
        $this->assertSame(30, $hasil->menitDibayar);
        $this->assertSame(300, $hasil->nilaiKotorSen);
    }

    public function test_tanpa_persetujuan_tidak_dibayar(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-10-26',
            masuk: '09:00',
            pulang: '20:00',
        ));

        $this->assertSame(120, $hasil->menitSoreAktual);
        $this->assertSame(0, $hasil->menitDibayar);
        $this->assertSame(0, $hasil->nilaiKotorSen);
    }

    public function test_clock_out_lintas_tengah_malam_dipotong_di_2359(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-10-26',
            masuk: '09:00',
            pulang: '2026-10-27 00:30',
            soreDisetujui: 600,
        ));

        $this->assertSame(359, $hasil->menitSoreAktual);
        $this->assertSame(359, $hasil->menitDibayar);
        $this->assertSame(3590, $hasil->nilaiKotorSen);
    }

    public function test_libur_lebih_dari_enam_jam_dipotong_enam_puluh_menit(): void
    {
        $hasil = $this->kalkulator->hitung($this->masukan(
            tanggal: '2026-10-31',
            masuk: '09:00',
            pulang: '16:00',
            liburDisetujui: 400,
            jenisHari: JenisHari::AkhirPekan,
        ));

        $this->assertSame(360, $hasil->menitLiburAktual);
        $this->assertSame(360, $hasil->menitDibayar);
        $this->assertSame(5400, $hasil->nilaiKotorSen);
    }

    private function masukan(
        string $tanggal,
        string $masuk,
        string $pulang,
        int $pagiDisetujui = 0,
        int $soreDisetujui = 0,
        int $liburDisetujui = 0,
        JenisHari $jenisHari = JenisHari::HariKerja,
        string $jamSelesaiKerja = '17:30',
    ): InputHitungLembur {
        $jamPulang = str_contains($pulang, ' ')
            ? CarbonImmutable::parse($pulang)
            : CarbonImmutable::parse($tanggal.' '.$pulang);

        return new InputHitungLembur(
            tanggal: CarbonImmutable::parse($tanggal)->startOfDay(),
            jenisHari: $jenisHari,
            jamMasuk: CarbonImmutable::parse($tanggal.' '.$masuk),
            jamPulang: $jamPulang,
            menitPagiDisetujui: $pagiDisetujui,
            menitSoreDisetujui: $soreDisetujui,
            menitLiburDisetujui: $liburDisetujui,
            jamSelesaiKerja: $jamSelesaiKerja,
        );
    }
}
