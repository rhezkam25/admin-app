# Aplikasi Administrasi Pegawai

Aplikasi web internal untuk absensi, cuti/izin, lembur, rekap, dan audit. Dibangun mengikuti `PRD-Aplikasi.md` dengan Laravel 13, Livewire 4, MySQL 8.4, dan Docker Compose.

## Fitur MVP

- Login lokal username/NIP, pembatasan percobaan login, dan kewajiban ganti password sementara.
- Master pegawai, departemen, jabatan, atasan langsung, jadwal kerja, dan riwayat gaji.
- Kalender hari libur Indonesia/Malaysia dengan impor CSV.
- Clock-in/clock-out berbasis waktu server, pencatatan IP/user-agent, keterlambatan, pulang cepat, dan anomali.
- Koreksi absensi oleh HR dengan alasan wajib dan audit before/after.
- Pengajuan serta persetujuan cuti/izin dengan saldo, carry-over, dan ledger transaksi.
- Penyesuaian saldo cuti oleh HR dengan alasan dan transaksi ledger.
- Pengajuan serta persetujuan lembur dengan jendela pagi/sore/libur, minimum aktual vs persetujuan, dan cap 28% gaji pokok.
- Rekap absensi, lembur, dan cuti; ekspor Excel/PDF.
- Penutupan periode penggajian dan pembukaan kembali oleh Super Admin.
- Audit log untuk perubahan sensitif dan notifikasi in-app.
- Perintah finalisasi absensi harian dan hitung ulang lembur.

## Stack

| Komponen | Versi |
|---|---|
| PHP | 8.5.12 |
| Laravel | 13.38.0 |
| Livewire | 4.4.8 |
| Database | MySQL 8.4 LTS (produksi), SQLite (lokal/testing) |
| Frontend | Tailwind CSS 4, Vite 8 |
| Autentikasi | Laravel Fortify |
| Ekspor | OpenSpout (Excel), Dompdf (PDF) |
| Pengujian | PHPUnit 12, Laravel Pint, Larastan/PHPStan |

Versi paket aktual dapat diperiksa dengan `composer show --direct` dan `package.json`.

## Konvensi Penamaan

- Tabel dan kolom domain menggunakan Bahasa Indonesia: `pegawai`, `absensi`, `pengajuan_cuti`, `catatan_lembur`, `nilai_dibayar_sen`, dan seterusnya.
- Variabel domain pada service/Livewire menggunakan Bahasa Indonesia: `$pegawai`, `$absensi`, `$pengajuan`, `$saldo`, `$menitDibayar`.
- Nama kelas tetap menggunakan istilah domain yang umum (`Employee`, `Attendance`, `LeaveRequest`, `OvertimeCalculator`) agar mudah dipetakan ke PRD dan ekosistem Laravel.
- Tabel/kolom framework (`users`, `sessions`, `jobs`, `notifications`, `created_at`) tetap mengikuti konvensi Laravel.

## Menjalankan Lokal

Prasyarat: PHP 8.5 (ekstensi `pdo_sqlite`, `mbstring`, `intl`, `zip`, `gd`, `bcmath`, `exif`), Composer, Node.js 24.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`.

### Akun Demo

| Username | Password | Peran |
|---|---|---|
| `superadmin` | `Password123!` | Super Admin |
| `hr` | `Password123!` | Admin HR |
| `atasan` | `Password123!` | Atasan |
| `pegawai` | `Password123!` | Pegawai |
| `budi`, `citra` | `Password123!` | Pegawai |

Data demo memakai identitas fiktif. Jangan memakai data pegawai nyata untuk pengembangan.

## Perintah Penting

```bash
php artisan absensi:finalisasi            # finalisasi absensi kemarin
php artisan absensi:finalisasi 2026-10-26 --paksa
php artisan lembur:hitung-ulang 2026-10
php artisan akun:buat-super-admin         # akun pertama di produksi
php artisan queue:work                    # worker notifikasi/ekspor (produksi)
php artisan schedule:work                 # scheduler lokal
```

## Pengujian dan Kualitas

```bash
php artisan test
vendor/bin/pint --dirty
vendor/bin/phpstan analyse --memory-limit=1G
npm run build
```

Status saat ini: 56 tes lulus, PHPStan level 5 tanpa error, dan Pint bersih.

## Docker Compose

Produksi menggunakan Nginx, PHP-FPM, queue worker, scheduler, dan MySQL. Port aplikasi hanya dipublikasikan ke IP VLAN.

```bash
cp .env.production.example .env
# isi APP_KEY, password database, VLAN_IP, dan nama host internal
docker compose build
docker compose up -d
docker compose exec app php artisan akun:buat-super-admin
```

- Default compose memakai HTTP pada `${VLAN_IP:-127.0.0.1}:8080`.
- Untuk TLS internal, siapkan `docker/certs/fullchain.pem` dan `privkey.pem`, lalu jalankan:

```bash
docker compose -f compose.yaml -f compose.tls.yaml up -d
```

- Database tidak membuka port ke host. Jaringan `backend` bersifat internal.
- Backup contoh tersedia di `docker/backup.sh`; enkripsi dan salinan ke NAS tetap menjadi tanggung jawab operasional.
- Jangan mengekspos port 3306 atau 9000 ke klien.
- Sesuaikan `TRUSTED_HOSTS`, subnet VLAN, dan aturan firewall `DOCKER-USER` sebelum go-live.

## Struktur Utama

```text
app/
├── Console/Commands/        # finalisasi absensi, hitung ulang lembur, buat super admin
├── Enums/                   # peran, status, jenis hari, segmen lembur
├── Http/Controllers/        # ekspor laporan
├── Livewire/                # komponen UI
├── Models/                  # model domain (tabel Indonesia)
├── Policies/                # otorisasi per record
├── Services/                # aturan bisnis absensi, cuti, lembur, periode, audit
└── Support/Overtime/        # value object kalkulasi lembur
database/
├── factories/
├── migrations/
└── seeders/
docker/
├── mysql/
├── nginx/
└── php/
tests/
├── Feature/
└── Unit/
```

## Catatan Implementasi

- Waktu absensi berasal dari server; timestamp perangkat tidak dipercaya.
- Satu pegawai memiliki satu record absensi per tanggal kerja.
- Lembur pagi/sore/libur dihitung oleh `OvertimeCalculator` yang deterministik dan diuji unit.
- Cap bulanan dihitung kronologis; detail per tanggal menyimpan nilai dibayar dan hangus.
- Perubahan absensi, persetujuan, tarif, gaji, atau hari libur memicu hitung ulang ringkasan.
- Attachment cuti disimpan pada storage privat; akses unduhan harus melewati policy.
- 2FA TOTP belum memiliki UI pada MVP ini; fitur Fortify tersedia untuk iterasi berikutnya.
- Impor CSV master pegawai belum diimplementasikan; impor CSV hari libur sudah tersedia.
- Formula lembur adalah kebijakan internal dan bukan pernyataan kepatuhan otomatis terhadap PP 35/2021 atau hukum Malaysia. Tinjauan HR/legal tetap diperlukan.

## Dokumen Acuan

- `PRD-Aplikasi.md` — kebutuhan produk, aturan bisnis, model data, keamanan, dan deployment.
