<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('peminjaman_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman')->restrictOnDelete();
            $table->foreignId('alat_id')->constrained('alat')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            // Rincian kondisi saat dikembalikan (terisi saat proses pengembalian).
            $table->unsignedInteger('jumlah_baik')->default(0);
            $table->unsignedInteger('jumlah_rusak_ringan')->default(0);
            $table->unsignedInteger('jumlah_rusak_berat')->default(0);
            $table->decimal('denda_kerusakan', 12, 2)->default(0);
            $table->timestamps();

            // Satu alat hanya boleh muncul sekali per transaksi.
            $table->unique(['peminjaman_id', 'alat_id']);
        });

        DB::statement('ALTER TABLE peminjaman_detail ADD CONSTRAINT chk_detail_jumlah CHECK (jumlah > 0)');
        // Sebelum dikembalikan = 0; sesudah dikembalikan, totalnya harus sama dengan jumlah dipinjam.
        DB::statement(
            'ALTER TABLE peminjaman_detail ADD CONSTRAINT chk_detail_kondisi
             CHECK ((jumlah_baik + jumlah_rusak_ringan + jumlah_rusak_berat) IN (0, jumlah))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_detail');
    }
};