<?php

namespace Database\Seeders;

use App\Enums\NegaraLibur;
use App\Enums\Peran;
use App\Enums\SegmenLembur;
use App\Enums\StatusAbsensi;
use App\Enums\StatusKepegawaian;
use App\Enums\StatusPermintaan;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\OvertimeRequest;
use App\Models\Position;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Overtime\OvertimePeriodService;
use App\Services\Overtime\OvertimeRequestService;
use App\Services\Period\PeriodService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPengaturan();
        $jadwal = $this->seedJadwal();
        [$departemen, $jabatan] = $this->seedOrganisasi();
        $jenisCuti = $this->seedJenisCuti();
        $this->seedHariLibur();
        $pegawai = $this->seedPegawai($jadwal, $departemen, $jabatan);
        $this->seedAkun($pegawai);
        $this->seedSaldoCuti($pegawai);
        $this->seedAbsensiDanLembur($pegawai, $jenisCuti);
        $this->seedPeriode();
    }

    private function seedPengaturan(): void
    {
        $nilai = [
            'lembur.tarif_hari_kerja_sen' => 600,
            'lembur.tarif_libur_sen' => 900,
            'lembur.batas_persen' => 28,
            'lembur.tenggang_menit' => 30,
            'lembur.menit_istirahat_libur' => 60,
            'lembur.ambang_istirahat_libur_menit' => 360,
            'absensi.toleransi_terlambat_menit' => 0,
            'cuti.hak_hari' => 12,
            'cuti.maks_carry_over_hari' => 6,
            'cuti.maks_saldo_hari' => 18,
        ];

        foreach ($nilai as $kunci => $isi) {
            Setting::put($kunci, $isi, 'kebijakan');
        }
    }

    private function seedJadwal(): WorkSchedule
    {
        $jadwal = WorkSchedule::query()->create([
            'nama' => 'Jadwal Tetap',
            'zona_waktu' => 'Asia/Jakarta',
            'bawaan' => true,
            'aktif' => true,
        ]);

        foreach (range(1, 7) as $hariKe) {
            $kerja = $hariKe <= 5;
            $jumat = $hariKe === 5;

            WorkScheduleDay::query()->create([
                'jadwal_kerja_id' => $jadwal->id,
                'hari_ke' => $hariKe,
                'hari_kerja' => $kerja,
                'jam_mulai' => $kerja ? '09:00' : null,
                'jam_selesai' => $kerja ? ($jumat ? '18:00' : '17:30') : null,
                'istirahat_mulai' => $kerja ? '12:00' : null,
                'istirahat_selesai' => $kerja ? '13:00' : null,
            ]);
        }

        return $jadwal;
    }

    /**
     * @return array{0: array<string, Department>, 1: array<string, Position>}
     */
    private function seedOrganisasi(): array
    {
        $departemen = [
            'hr' => Department::query()->create(['kode' => 'HR', 'nama' => 'Sumber Daya Manusia', 'aktif' => true]),
            'it' => Department::query()->create(['kode' => 'IT', 'nama' => 'Teknologi Informasi', 'aktif' => true]),
            'keuangan' => Department::query()->create(['kode' => 'FIN', 'nama' => 'Keuangan', 'aktif' => true]),
            'operasional' => Department::query()->create(['kode' => 'OPS', 'nama' => 'Operasional', 'aktif' => true]),
        ];

        $jabatan = [
            'hr_manager' => Position::query()->create(['departemen_id' => $departemen['hr']->id, 'nama' => 'HR Manager', 'aktif' => true]),
            'hr_staff' => Position::query()->create(['departemen_id' => $departemen['hr']->id, 'nama' => 'HR Staff', 'aktif' => true]),
            'it_manager' => Position::query()->create(['departemen_id' => $departemen['it']->id, 'nama' => 'IT Manager', 'aktif' => true]),
            'software_engineer' => Position::query()->create(['departemen_id' => $departemen['it']->id, 'nama' => 'Software Engineer', 'aktif' => true]),
            'finance_staff' => Position::query()->create(['departemen_id' => $departemen['keuangan']->id, 'nama' => 'Finance Staff', 'aktif' => true]),
            'operations_staff' => Position::query()->create(['departemen_id' => $departemen['operasional']->id, 'nama' => 'Operations Staff', 'aktif' => true]),
        ];

        return [$departemen, $jabatan];
    }

    /**
     * @return array<string, LeaveType>
     */
    private function seedJenisCuti(): array
    {
        return [
            'tahunan' => LeaveType::query()->create([
                'kode' => 'cuti_tahunan',
                'nama' => 'Cuti Tahunan',
                'memotong_saldo' => true,
                'wajib_lampiran' => false,
                'aktif' => true,
            ]),
            'sakit' => LeaveType::query()->create([
                'kode' => 'sakit',
                'nama' => 'Sakit',
                'memotong_saldo' => false,
                'wajib_lampiran' => true,
                'aktif' => true,
            ]),
            'izin' => LeaveType::query()->create([
                'kode' => 'izin',
                'nama' => 'Izin',
                'memotong_saldo' => false,
                'wajib_lampiran' => false,
                'aktif' => true,
            ]),
            'dinas_luar' => LeaveType::query()->create([
                'kode' => 'dinas_luar',
                'nama' => 'Dinas Luar',
                'memotong_saldo' => false,
                'wajib_lampiran' => false,
                'aktif' => true,
            ]),
        ];
    }

    private function seedHariLibur(): void
    {
        $daftar = [
            ['2026-01-01', NegaraLibur::Indonesia, 'Tahun Baru', 'nasional'],
            ['2026-01-01', NegaraLibur::Malaysia, 'New Year', 'nasional'],
            ['2026-05-01', NegaraLibur::Indonesia, 'Hari Buruh', 'nasional'],
            ['2026-05-01', NegaraLibur::Malaysia, 'Labour Day', 'nasional'],
            ['2026-08-17', NegaraLibur::Indonesia, 'Hari Kemerdekaan', 'nasional'],
            ['2026-08-31', NegaraLibur::Malaysia, 'Hari Nasional', 'nasional'],
            ['2026-12-25', NegaraLibur::Indonesia, 'Hari Natal', 'nasional'],
            ['2026-12-25', NegaraLibur::Malaysia, 'Christmas Day', 'nasional'],
        ];

        foreach ($daftar as [$tanggal, $negara, $nama, $jenis]) {
            Holiday::query()->create([
                'tanggal' => $tanggal,
                'negara' => $negara,
                'nama' => $nama,
                'jenis' => $jenis,
                'diamati' => true,
                'aktif' => true,
                'sumber' => 'demo',
            ]);
        }
    }

    /**
     * @param  array<string, Department>  $departemen
     * @param  array<string, Position>  $jabatan
     * @return array<string, Employee>
     */
    private function seedPegawai(WorkSchedule $jadwal, array $departemen, array $jabatan): array
    {
        $siti = Employee::query()->create([
            'nip' => 'EMP001',
            'nama' => 'Siti Rahmawati',
            'departemen_id' => $departemen['hr']->id,
            'jabatan_id' => $jabatan['hr_manager']->id,
            'jadwal_kerja_id' => $jadwal->id,
            'status_kepegawaian' => StatusKepegawaian::Aktif,
            'tanggal_masuk' => '2020-01-06',
            'telepon' => '081200000001',
            'alamat' => 'Jakarta',
        ]);

        $budi = Employee::query()->create([
            'nip' => 'EMP002',
            'nama' => 'Budi Santoso',
            'departemen_id' => $departemen['hr']->id,
            'jabatan_id' => $jabatan['hr_staff']->id,
            'atasan_id' => $siti->id,
            'jadwal_kerja_id' => $jadwal->id,
            'status_kepegawaian' => StatusKepegawaian::Aktif,
            'tanggal_masuk' => '2022-03-01',
            'telepon' => '081200000002',
            'alamat' => 'Bekasi',
        ]);

        $dewi = Employee::query()->create([
            'nip' => 'EMP003',
            'nama' => 'Dewi Lestari',
            'departemen_id' => $departemen['it']->id,
            'jabatan_id' => $jabatan['it_manager']->id,
            'atasan_id' => $siti->id,
            'jadwal_kerja_id' => $jadwal->id,
            'status_kepegawaian' => StatusKepegawaian::Aktif,
            'tanggal_masuk' => '2021-02-01',
            'telepon' => '081200000003',
            'alamat' => 'Depok',
        ]);

        $andi = Employee::query()->create([
            'nip' => 'EMP004',
            'nama' => 'Andi Pratama',
            'departemen_id' => $departemen['it']->id,
            'jabatan_id' => $jabatan['software_engineer']->id,
            'atasan_id' => $dewi->id,
            'jadwal_kerja_id' => $jadwal->id,
            'status_kepegawaian' => StatusKepegawaian::Aktif,
            'tanggal_masuk' => '2023-07-10',
            'telepon' => '081200000004',
            'alamat' => 'Tangerang',
        ]);

        $citra = Employee::query()->create([
            'nip' => 'EMP005',
            'nama' => 'Citra Anindya',
            'departemen_id' => $departemen['keuangan']->id,
            'jabatan_id' => $jabatan['finance_staff']->id,
            'atasan_id' => $siti->id,
            'jadwal_kerja_id' => $jadwal->id,
            'status_kepegawaian' => StatusKepegawaian::Aktif,
            'tanggal_masuk' => '2024-01-15',
            'telepon' => '081200000005',
            'alamat' => 'Jakarta',
        ]);

        $eko = Employee::query()->create([
            'nip' => 'EMP006',
            'nama' => 'Eko Nugroho',
            'departemen_id' => $departemen['operasional']->id,
            'jabatan_id' => $jabatan['operations_staff']->id,
            'atasan_id' => $siti->id,
            'jadwal_kerja_id' => $jadwal->id,
            'status_kepegawaian' => StatusKepegawaian::Aktif,
            'tanggal_masuk' => '2026-06-01',
            'telepon' => '081200000006',
            'alamat' => 'Bogor',
        ]);

        $gaji = [
            [$siti, 300_000],
            [$budi, 200_000],
            [$dewi, 400_000],
            [$andi, 250_000],
            [$citra, 220_000],
            [$eko, 180_000],
        ];

        foreach ($gaji as [$pegawai, $gajiPokokSen]) {
            $pegawai->salaries()->create([
                'berlaku_mulai' => $pegawai->tanggal_masuk,
                'gaji_pokok_sen' => $gajiPokokSen,
                'mata_uang' => 'USD',
            ]);
        }

        return compact('siti', 'budi', 'dewi', 'andi', 'citra', 'eko');
    }

    /**
     * @param  array<string, Employee>  $pegawai
     */
    private function seedAkun(array $pegawai): void
    {
        User::query()->create([
            'username' => 'superadmin',
            'nama' => 'Super Admin',
            'password' => Hash::make('Password123!'),
            'peran' => Peran::SuperAdmin,
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);

        User::query()->create([
            'username' => 'hr',
            'nama' => $pegawai['siti']->nama,
            'password' => Hash::make('Password123!'),
            'peran' => Peran::AdminHr,
            'pegawai_id' => $pegawai['siti']->id,
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);

        User::query()->create([
            'username' => 'atasan',
            'nama' => $pegawai['dewi']->nama,
            'password' => Hash::make('Password123!'),
            'peran' => Peran::Atasan,
            'pegawai_id' => $pegawai['dewi']->id,
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);

        User::query()->create([
            'username' => 'pegawai',
            'nama' => $pegawai['andi']->nama,
            'password' => Hash::make('Password123!'),
            'peran' => Peran::Pegawai,
            'pegawai_id' => $pegawai['andi']->id,
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);

        User::query()->create([
            'username' => 'budi',
            'nama' => $pegawai['budi']->nama,
            'password' => Hash::make('Password123!'),
            'peran' => Peran::Pegawai,
            'pegawai_id' => $pegawai['budi']->id,
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);

        User::query()->create([
            'username' => 'citra',
            'nama' => $pegawai['citra']->nama,
            'password' => Hash::make('Password123!'),
            'peran' => Peran::Pegawai,
            'pegawai_id' => $pegawai['citra']->id,
            'aktif' => true,
            'wajib_ganti_password' => false,
        ]);
    }

    /**
     * @param  array<string, Employee>  $pegawai
     */
    private function seedSaldoCuti(array $pegawai): void
    {
        $layanan = app(LeaveBalanceService::class);

        foreach ($pegawai as $orang) {
            $layanan->ambilAtauBuatSaldo($orang, now());
        }
    }

    /**
     * @param  array<string, Employee>  $pegawai
     * @param  array<string, LeaveType>  $jenisCuti
     */
    private function seedAbsensiDanLembur(array $pegawai, array $jenisCuti): void
    {
        $hariKerja = collect();

        for ($i = 1; $i <= 21; $i++) {
            $tanggal = CarbonImmutable::parse(now())->subDays($i);

            if ($tanggal->isWeekday()) {
                $hariKerja->push($tanggal);
            }
        }

        foreach ($pegawai as $orang) {
            foreach ($hariKerja->take(5) as $tanggal) {
                $masuk = $tanggal->setTime(9, 0);
                $pulang = $tanggal->setTime(17, 30);

                if ($orang->nip === 'EMP004' && $tanggal->equalTo($hariKerja->first())) {
                    $pulang = $tanggal->setTime(20, 0);
                }

                Attendance::query()->updateOrCreate(
                    ['pegawai_id' => $orang->id, 'tanggal_kerja' => $tanggal->toDateString()],
                    [
                        'jenis_hari' => 'hari_kerja',
                        'status' => StatusAbsensi::Hadir,
                        'jam_masuk' => $masuk,
                        'jam_pulang' => $pulang,
                        'ip_masuk' => '10.0.0.10',
                        'ip_pulang' => '10.0.0.10',
                        'user_agent_masuk' => 'seeder',
                        'user_agent_pulang' => 'seeder',
                        'menit_terlambat' => 0,
                        'menit_pulang_cepat' => 0,
                        'belum_lengkap' => false,
                    ],
                );
            }
        }

        $andi = $pegawai['andi'];
        $tanggalLembur = $hariKerja->first();

        OvertimeRequest::query()->updateOrCreate(
            [
                'pegawai_id' => $andi->id,
                'tanggal_kerja' => $tanggalLembur->toDateString(),
                'segmen' => SegmenLembur::Sore,
            ],
            [
                'rencana_mulai' => '18:00',
                'rencana_selesai' => '20:00',
                'menit_diajukan' => 120,
                'menit_disetujui' => 120,
                'status' => StatusPermintaan::Disetujui,
                'ditinjau_oleh' => User::query()->where('username', 'atasan')->value('id'),
                'ditinjau_pada' => now(),
                'alasan' => 'Penyelesaian rilis aplikasi.',
            ],
        );

        app(OvertimePeriodService::class)->hitungUlangPeriode($andi, now()->format('Y-m'));

        app(LeaveBalanceService::class)->ajukan(
            $pegawai['budi'],
            $jenisCuti['tahunan'],
            CarbonImmutable::parse(now())->addWeek()->startOfWeek()->addDay(),
            CarbonImmutable::parse(now())->addWeek()->startOfWeek()->addDays(2),
            'Keperluan keluarga.',
            null,
            $pegawai['budi']->user,
        );

        app(OvertimeRequestService::class)->ajukan(
            $pegawai['andi'],
            CarbonImmutable::parse(now())->addWeek()->startOfWeek()->addDay(),
            SegmenLembur::Sore,
            '18:00',
            '20:00',
            120,
            'Persiapan deploy.',
            $pegawai['andi']->user,
        );
    }

    private function seedPeriode(): void
    {
        app(PeriodService::class)->ambilAtauBuat(now()->format('Y-m'));
    }
}
