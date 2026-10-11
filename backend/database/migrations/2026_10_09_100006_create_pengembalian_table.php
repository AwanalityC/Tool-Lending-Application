<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pengembalian', function (Blueprint $table) {
            $table->id();
            // UNIQUE: satu peminjaman hanya bisa dikembalikan satu kali.
            $table->foreignId('peminjaman_id')->unique()->constrained('peminjaman')->restrictOnDelete();
            $table->dateTime('tanggal_kembali');
            $table->foreignId('petugas_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('hari_terlambat')->default(0);
            // Salinan tarif saat itu, supaya laporan lama tidak berubah ketika tarif diganti.
            $table->decimal('tarif_per_hari_saat_itu', 12, 2)->default(0);
            $table->decimal('denda_terlambat', 12, 2)->default(0);
            $table->decimal('denda_kerusakan', 12, 2)->default(0);
            $table->decimal('total_denda', 12, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        DB::statement(
            'ALTER TABLE pengembalian ADD CONSTRAINT chk_pengembalian_total
             CHECK (total_denda = denda_terlambat + denda_kerusakan)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('pengembalian');
    }
};