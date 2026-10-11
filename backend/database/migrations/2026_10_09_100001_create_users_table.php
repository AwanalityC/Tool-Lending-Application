<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'petugas', 'peminjam'])->default('peminjam');
            // Jenis peminjam hanya terisi untuk role "peminjam".
            $table->enum('jenis_peminjam', ['siswa', 'guru'])->nullable();
            $table->string('nomor_induk', 30)->nullable(); // NIS / NIP
            $table->string('no_hp', 20)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};