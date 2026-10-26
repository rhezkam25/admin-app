<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi</title>
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
    <h1>Rekap Absensi</h1>
    <p>Periode {{ $dari }} sampai {{ $sampai }}</p>

    <table>
        <thead>
            <tr>
                <th>NIP</th>
                <th>Nama</th>
                <th>Hadir</th>
                <th>Alpa</th>
                <th>Cuti</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Dinas Luar</th>
                <th>Terlambat</th>
                <th>Pulang Cepat</th>
                <th>Belum Lengkap</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekap as $baris)
                <tr>
                    <td>{{ $baris['pegawai']?->nip }}</td>
                    <td>{{ $baris['pegawai']?->nama }}</td>
                    <td>{{ $baris['hadir'] }}</td>
                    <td>{{ $baris['alpa'] }}</td>
                    <td>{{ $baris['cuti'] }}</td>
                    <td>{{ $baris['sakit'] }}</td>
                    <td>{{ $baris['izin'] }}</td>
                    <td>{{ $baris['dinas_luar'] }}</td>
                    <td>{{ $baris['terlambat'] }}</td>
                    <td>{{ $baris['pulang_cepat'] }}</td>
                    <td>{{ $baris['belum_lengkap'] }}</td>
                </tr>
            @empty
                <tr><td colspan="11">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
