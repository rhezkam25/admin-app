<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Lembur</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p { margin: 0 0 12px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px; text-align: left; }
        th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; }
    </style>
</head>
<body>
    <h1>Rekap Lembur</h1>
    <p>Periode {{ $periode }}</p>

    <table>
        <thead>
            <tr>
                <th>NIP</th>
                <th>Nama</th>
                <th>Total Menit</th>
                @if ($bolehLihatNominal)
                    <th>Kotor (USD)</th>
                    <th>Batas (USD)</th>
                    <th>Dibayar (USD)</th>
                    <th>Hangus (USD)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($rekap as $baris)
                <tr>
                    <td>{{ $baris->employee?->nip }}</td>
                    <td>{{ $baris->employee?->nama }}</td>
                    <td>{{ $baris->total_menit }}</td>
                    @if ($bolehLihatNominal)
                        <td>{{ number_format($baris->nilai_kotor_sen / 100, 2, ',', '.') }}</td>
                        <td>{{ number_format($baris->batas_sen / 100, 2, ',', '.') }}</td>
                        <td>{{ number_format($baris->nilai_dibayar_sen / 100, 2, ',', '.') }}</td>
                        <td>{{ number_format($baris->nilai_hangus_sen / 100, 2, ',', '.') }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $bolehLihatNominal ? 7 : 3 }}">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
