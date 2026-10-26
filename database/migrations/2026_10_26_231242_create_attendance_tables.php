<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->date('tanggal_kerja');
            $table->string('jenis_hari')->default('hari_kerja');
            $table->string('status')->default('hadir');
            $table->timestamp('jam_masuk')->nullable();
            $table->timestamp('jam_pulang')->nullable();
            $table->string('ip_masuk', 45)->nullable();
            $table->string('ip_pulang', 45)->nullable();
            $table->text('user_agent_masuk')->nullable();
            $table->text('user_agent_pulang')->nullable();
            $table->unsignedInteger('menit_terlambat')->default(0);
            $table->unsignedInteger('menit_pulang_cepat')->default(0);
            $table->boolean('belum_lengkap')->default(false);
            $table->json('tanda_anomali')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['pegawai_id', 'tanggal_kerja']);
            $table->index(['tanggal_kerja', 'status']);
        });

        Schema::create('koreksi_absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absensi_id')->constrained('absensi')->cascadeOnDelete();
            $table->foreignId('dikoreksi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->json('nilai_lama');
            $table->json('nilai_baru');
            $table->text('alasan');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['absensi_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('koreksi_absensi');
        Schema::dropIfExists('absensi');
    }
};
