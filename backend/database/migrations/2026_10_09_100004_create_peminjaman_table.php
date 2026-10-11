<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi', 30)->unique(); // contoh: PJM-20261009-0001
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('tanggal_pinjam');
            $table->date('tanggal_jatuh_tempo');
            $table->text('alasan');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak', 'selesai'])->default('menunggu');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('diproses_pada')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->timestamps(); // created_at = tanggal pengajuan

            $table->index(['user_id', 'status']);
            $table->index(['status', 'tanggal_jatuh_tempo']);
            $table->index('tanggal_pinjam');
        });

        DB::statement(
            'ALTER TABLE peminjaman ADD CONSTRAINT chk_peminjaman_tanggal
             CHECK (tanggal_jatuh_tempo >= tanggal_pinjam)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};