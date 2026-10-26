<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_lembur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->date('tanggal_kerja');
            $table->foreignId('absensi_id')->nullable()->constrained('absensi')->nullOnDelete();
            $table->unsignedInteger('menit_pagi_aktual')->default(0);
            $table->unsignedInteger('menit_sore_aktual')->default(0);
            $table->unsignedInteger('menit_libur_aktual')->default(0);
            $table->unsignedInteger('menit_aktual')->default(0);
            $table->unsignedInteger('menit_disetujui')->default(0);
            $table->unsignedInteger('menit_dibayar')->default(0);
            $table->unsignedInteger('tarif_sen')->default(600);
            $table->bigInteger('nilai_kotor_sen')->default(0);
            $table->bigInteger('nilai_dibayar_sen')->default(0);
            $table->bigInteger('nilai_hangus_sen')->default(0);
            $table->json('snapshot_aturan')->nullable();
            $table->timestamp('dihitung_pada')->nullable();
            $table->timestamps();

            $table->unique(['pegawai_id', 'tanggal_kerja']);
        });

        Schema::create('pengajuan_lembur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->foreignId('catatan_lembur_id')->nullable()->constrained('catatan_lembur')->nullOnDelete();
            $table->date('tanggal_kerja');
            $table->string('segmen');
            $table->time('rencana_mulai')->nullable();
            $table->time('rencana_selesai')->nullable();
            $table->unsignedInteger('menit_diajukan');
            $table->unsignedInteger('menit_disetujui')->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('ditinjau_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditinjau_pada')->nullable();
            $table->text('alasan');
            $table->text('alasan_penolakan')->nullable();
            $table->text('alasan_override')->nullable();
            $table->timestamps();

            $table->index(['pegawai_id', 'tanggal_kerja']);
            $table->index(['status', 'tanggal_kerja']);
        });

        Schema::create('ringkasan_lembur_bulanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->string('periode', 7);
            $table->bigInteger('gaji_pokok_sen')->default(0);
            $table->bigInteger('batas_sen')->default(0);
            $table->bigInteger('nilai_kotor_sen')->default(0);
            $table->bigInteger('nilai_dibayar_sen')->default(0);
            $table->bigInteger('nilai_hangus_sen')->default(0);
            $table->unsignedInteger('total_menit')->default(0);
            $table->json('snapshot_aturan')->nullable();
            $table->timestamps();

            $table->unique(['pegawai_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ringkasan_lembur_bulanan');
        Schema::dropIfExists('pengajuan_lembur');
        Schema::dropIfExists('catatan_lembur');
    }
};
