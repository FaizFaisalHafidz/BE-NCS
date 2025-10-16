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
        Schema::create('area_gudang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudang')->onDelete('cascade');
            $table->string('kode_area', 20);
            $table->string('nama_area', 100);
            $table->decimal('koordinat_x', 8, 2);
            $table->decimal('koordinat_y', 8, 2);
            $table->decimal('panjang', 8, 2); // meter
            $table->decimal('lebar', 8, 2); // meter
            $table->decimal('tinggi', 8, 2); // meter
            $table->decimal('kapasitas', 10, 2); // meter kubik
            $table->decimal('kapasitas_terpakai', 10, 2)->default(0);
            $table->boolean('tersedia')->default(true);
            $table->enum('jenis_area', ['rak', 'lantai', 'khusus'])->default('rak');
            $table->timestamps();
            
            $table->unique(['gudang_id', 'kode_area']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_gudang');
    }
};
