<?php

namespace App\Http\Controllers;

use App\Services\Audit\AuditLogger;
use App\Services\Report\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function attendanceExcel(Request $request, ReportService $reportService): BinaryFileResponse
    {
        Gate::authorize('lihat-laporan');

        $dari = $request->date('dari')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $sampai = $request->date('sampai')?->toDateString() ?? now()->toDateString();
        $departemenId = $request->integer('departemen_id') ?: null;
        $rekap = $reportService->rekapAbsensi($request->user(), $dari, $sampai, $departemenId);

        $path = $this->jalurEkspor('absensi-'.$dari.'-'.$sampai.'.xlsx');
        $options = new Options;
        $options->setTempFolder($this->jalurTemp());
        $writer = new Writer($options);
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues([
            'NIP', 'Nama', 'Hadir', 'Alpa', 'Cuti', 'Sakit', 'Izin', 'Dinas Luar', 'Terlambat (menit)', 'Pulang Cepat (menit)', 'Belum Lengkap',
        ]));

        foreach ($rekap as $baris) {
            $writer->addRow(Row::fromValues([
                $baris['pegawai']?->nip,
                $baris['pegawai']?->nama,
                $baris['hadir'],
                $baris['alpa'],
                $baris['cuti'],
                $baris['sakit'],
                $baris['izin'],
                $baris['dinas_luar'],
                $baris['terlambat'],
                $baris['pulang_cepat'],
                $baris['belum_lengkap'],
            ]));
        }

        $writer->close();

        $this->catatEkspor($request, 'absensi_excel', ['dari' => $dari, 'sampai' => $sampai, 'departemen_id' => $departemenId]);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function attendancePdf(Request $request, ReportService $reportService): Response
    {
        Gate::authorize('lihat-laporan');

        $dari = $request->date('dari')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $sampai = $request->date('sampai')?->toDateString() ?? now()->toDateString();
        $departemenId = $request->integer('departemen_id') ?: null;
        $rekap = $reportService->rekapAbsensi($request->user(), $dari, $sampai, $departemenId);

        $this->catatEkspor($request, 'absensi_pdf', ['dari' => $dari, 'sampai' => $sampai, 'departemen_id' => $departemenId]);

        return Pdf::loadView('reports.pdf.attendance', [
            'rekap' => $rekap,
            'dari' => $dari,
            'sampai' => $sampai,
        ])->download('absensi-'.$dari.'-'.$sampai.'.pdf');
    }

    public function overtimeExcel(Request $request, ReportService $reportService): BinaryFileResponse
    {
        Gate::authorize('lihat-laporan');

        $periode = $request->string('periode')->toString() ?: now()->format('Y-m');
        $departemenId = $request->integer('departemen_id') ?: null;
        $rekap = $reportService->rekapLembur($request->user(), $periode, $departemenId);
        $bolehLihatNominal = $reportService->bolehLihatNominal($request->user());

        $path = $this->jalurEkspor('lembur-'.$periode.'.xlsx');
        $options = new Options;
        $options->setTempFolder($this->jalurTemp());
        $writer = new Writer($options);
        $writer->openToFile($path);
        $header = ['NIP', 'Nama', 'Total Menit'];

        if ($bolehLihatNominal) {
            $header = array_merge($header, ['Kotor (USD)', 'Batas (USD)', 'Dibayar (USD)', 'Hangus (USD)']);
        }

        $writer->addRow(Row::fromValues($header));

        foreach ($rekap as $baris) {
            $values = [
                $baris->employee?->nip,
                $baris->employee?->nama,
                $baris->total_menit,
            ];

            if ($bolehLihatNominal) {
                $values = array_merge($values, [
                    $baris->nilai_kotor_sen / 100,
                    $baris->batas_sen / 100,
                    $baris->nilai_dibayar_sen / 100,
                    $baris->nilai_hangus_sen / 100,
                ]);
            }

            $writer->addRow(Row::fromValues($values));
        }

        $writer->close();

        $this->catatEkspor($request, 'lembur_excel', ['periode' => $periode, 'departemen_id' => $departemenId]);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    public function overtimePdf(Request $request, ReportService $reportService): Response
    {
        Gate::authorize('lihat-laporan');

        $periode = $request->string('periode')->toString() ?: now()->format('Y-m');
        $departemenId = $request->integer('departemen_id') ?: null;
        $rekap = $reportService->rekapLembur($request->user(), $periode, $departemenId);
        $bolehLihatNominal = $reportService->bolehLihatNominal($request->user());

        $this->catatEkspor($request, 'lembur_pdf', ['periode' => $periode, 'departemen_id' => $departemenId]);

        return Pdf::loadView('reports.pdf.overtime', [
            'rekap' => $rekap,
            'periode' => $periode,
            'bolehLihatNominal' => $bolehLihatNominal,
        ])->download('lembur-'.$periode.'.pdf');
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function catatEkspor(Request $request, string $jenis, array $filter): void
    {
        app(AuditLogger::class)->catat(
            $request->user(),
            'laporan.ekspor',
            null,
            [],
            ['jenis' => $jenis] + $filter,
            null,
            $request->ip(),
            $request->userAgent(),
        );
    }

    private function jalurEkspor(string $namaBerkas): string
    {
        $direktori = storage_path('app/private/ekspor');
        File::ensureDirectoryExists($direktori);

        return $direktori.DIRECTORY_SEPARATOR.$namaBerkas;
    }

    private function jalurTemp(): string
    {
        $direktori = storage_path('app/private/tmp');
        File::ensureDirectoryExists($direktori);

        return $direktori;
    }
}
