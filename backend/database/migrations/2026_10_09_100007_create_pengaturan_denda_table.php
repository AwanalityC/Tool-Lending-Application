<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pengaturan_denda', function (Blueprint $table) {
            $table->id();
            $table->decimal('tarif_terlambat_per_hari', 12, 2)->default(0);
            $table->decimal('denda_rusak_ringan', 12, 2)->default(0); // per unit
            $table->decimal('denda_rusak_berat', 12, 2)->default(0);  // per unit
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_denda');
    }
};