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
        Schema::create('rekomendasi_penempatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_optimasi_id')->constrained('log_optimasi')->onDelete('cascade');
            $table->foreignId('barang_id')->constrained('barang')->onDelete('cascade');
            $table->foreignId('area_gudang_saat_ini')->nullable()->constrained('area_gudang')->onDelete('set null');
            $table->foreignId('area_gudang_rekomendasi')->constrained('area_gudang')->onDelete('cascade');
            $table->text('alasan');
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak', 'diimplementasi'])->default('menunggu');
            $table->text('catatan')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tanggal_persetujuan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_penempatan');
    }
};
