<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('log_optimasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudang')->onDelete('cascade');
            $table->string('versi_algoritma', 20);
            $table->decimal('suhu_awal', 10, 4);
            $table->decimal('tingkat_pendinginan', 6, 4);
            $table->decimal('suhu_minimum', 10, 4);
            $table->integer('iterasi');
            $table->decimal('biaya_awal', 15, 4);
            $table->decimal('biaya_akhir', 15, 4);
            $table->decimal('persentase_perbaikan', 5, 2);
            $table->decimal('waktu_eksekusi', 8, 2); // detik
            $table->enum('status', ['berjalan', 'selesai', 'gagal'])->default('berjalan');
            $table->text('detail_hasil')->nullable();
            $table->foreignId('dijalankan_oleh')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_optimasi');
    }
};
