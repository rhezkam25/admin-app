<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departemen', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('jabatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departemen_id')->nullable()->constrained('departemen')->nullOnDelete();
            $table->string('nama');
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['departemen_id', 'nama']);
        });

        Schema::create('jadwal_kerja', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('zona_waktu')->default('Asia/Jakarta');
            $table->boolean('bawaan')->default(false);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('hari_jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_kerja_id')->constrained('jadwal_kerja')->cascadeOnDelete();
            $table->unsignedTinyInteger('hari_ke');
            $table->boolean('hari_kerja')->default(true);
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->time('istirahat_mulai')->nullable();
            $table->time('istirahat_selesai')->nullable();
            $table->timestamps();

            $table->unique(['jadwal_kerja_id', 'hari_ke']);
        });

        Schema::create('pegawai', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique();
            $table->string('nama');
            $table->foreignId('departemen_id')->nullable()->constrained('departemen')->nullOnDelete();
            $table->foreignId('jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $table->foreignId('atasan_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $table->foreignId('jadwal_kerja_id')->nullable()->constrained('jadwal_kerja')->nullOnDelete();
            $table->string('status_kepegawaian')->default('aktif');
            $table->date('tanggal_masuk');
            $table->date('tanggal_keluar')->nullable();
            $table->string('telepon')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();

            $table->index(['departemen_id', 'status_kepegawaian']);
            $table->index(['atasan_id', 'status_kepegawaian']);
        });

        Schema::create('riwayat_gaji', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_id')->constrained('pegawai')->cascadeOnDelete();
            $table->date('berlaku_mulai');
            $table->bigInteger('gaji_pokok_sen');
            $table->string('mata_uang', 3)->default('USD');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pegawai_id', 'berlaku_mulai']);
        });

        Schema::create('hari_libur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('negara', 2);
            $table->string('nama');
            $table->string('jenis')->nullable();
            $table->boolean('diamati')->default(true);
            $table->boolean('aktif')->default(true);
            $table->string('sumber')->nullable();
            $table->timestamps();

            $table->index(['tanggal', 'negara']);
            $table->index('tanggal');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('pegawai_id')->references('id')->on('pegawai')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['pegawai_id']);
        });

        Schema::dropIfExists('hari_libur');
        Schema::dropIfExists('riwayat_gaji');
        Schema::dropIfExists('pegawai');
        Schema::dropIfExists('hari_jadwal');
        Schema::dropIfExists('jadwal_kerja');
        Schema::dropIfExists('jabatan');
        Schema::dropIfExists('departemen');
    }
};
