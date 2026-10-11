<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alat', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            // RESTRICT: kategori yang masih dipakai alat tidak bisa dihapus.
            $table->foreignId('kategori_id')->constrained('kategori')->restrictOnDelete();
            $table->text('deskripsi')->nullable();
            $table->string('gambar')->nullable();
            // UNSIGNED: database menolak nilai negatif.
            $table->unsignedInteger('stok_total')->default(0);
            $table->unsignedInteger('stok_tersedia')->default(0);
            $table->unsignedInteger('stok_dipinjam')->default(0);
            $table->unsignedInteger('stok_rusak')->default(0);
            $table->enum('kondisi', ['baik', 'rusak_ringan', 'rusak_berat'])->default('baik');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('nama');
        });

        // Jaring pengaman terakhir: ketiga angka stok harus selalu cocok dengan total.
        DB::statement(
            'ALTER TABLE alat ADD CONSTRAINT chk_alat_stok_konsisten
             CHECK (stok_total = stok_tersedia + stok_dipinjam + stok_rusak)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('alat');
    }
};