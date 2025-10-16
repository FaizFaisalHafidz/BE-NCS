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
        Schema::create('penempatan_barang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudang')->onDelete('cascade');
            $table->foreignId('area_gudang_id')->constrained('area_gudang')->onDelete('cascade');
            $table->foreignId('barang_id')->constrained('barang')->onDelete('cascade');
            $table->integer('jumlah');
            $table->timestamp('tanggal_penempatan');
            $table->timestamp('tanggal_kadaluarsa')->nullable();
            $table->enum('status', ['ditempatkan', 'direservasi', 'diambil'])->default('ditempatkan');
            $table->text('keterangan')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penempatan_barang');
    }
};
