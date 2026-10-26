<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_penggajian', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7)->unique();
            $table->string('status')->default('terbuka');
            $table->foreignId('ditutup_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditutup_pada')->nullable();
            $table->foreignId('dibuka_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibuka_pada')->nullable();
            $table->text('alasan_buka')->nullable();
            $table->timestamps();
        });

        Schema::create('pengaturan', function (Blueprint $table) {
            $table->id();
            $table->string('kunci')->unique();
            $table->string('grup')->nullable();
            $table->string('tipe')->default('string');
            $table->text('nilai')->nullable();
            $table->foreignId('diperbarui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('log_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('peristiwa');
            $table->nullableMorphs('objek');
            $table->json('nilai_lama')->nullable();
            $table->json('nilai_baru')->nullable();
            $table->text('alasan')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['pengguna_id', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('log_audit');
        Schema::dropIfExists('pengaturan');
        Schema::dropIfExists('periode_penggajian');
    }
};
