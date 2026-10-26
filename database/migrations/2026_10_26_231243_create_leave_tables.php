<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_cuti', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->boolean('memotong_saldo')->default(false);
            $table->boolean('wajib_lampiran')->default(false);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('saldo_cuti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->date('awal_tahun_cuti');
            $table->integer('hak_hari')->default(0);
            $table->integer('carry_over_hari')->default(0);
            $table->integer('terpakai_hari')->default(0);
            $table->integer('tertunda_hari')->default(0);
            $table->integer('penyesuaian_hari')->default(0);
            $table->integer('sisa_hari')->default(0);
            $table->date('kedaluwarsa_pada')->nullable();
            $table->timestamps();

            $table->unique(['pegawai_id', 'awal_tahun_cuti']);
        });

        Schema::create('pengajuan_cuti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->foreignId('jenis_cuti_id')->constrained('jenis_cuti')->cascadeOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedInteger('jumlah_hari_kerja');
            $table->text('alasan');
            $table->string('lampiran_path')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('ditinjau_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditinjau_pada')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->text('alasan_override')->nullable();
            $table->timestamps();

            $table->index(['pegawai_id', 'tanggal_mulai']);
            $table->index(['status', 'tanggal_mulai']);
        });

        Schema::create('transaksi_saldo_cuti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saldo_cuti_id')->constrained('saldo_cuti')->cascadeOnDelete();
            $table->string('jenis');
            $table->integer('perubahan_hari');
            $table->nullableMorphs('referensi');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan')->nullable();
            $table->timestamps();

            $table->index(['saldo_cuti_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_saldo_cuti');
        Schema::dropIfExists('pengajuan_cuti');
        Schema::dropIfExists('saldo_cuti');
        Schema::dropIfExists('jenis_cuti');
    }
};
