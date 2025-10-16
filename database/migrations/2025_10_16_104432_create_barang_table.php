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
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 50)->unique();
            $table->string('nama_barang', 150);
            $table->foreignId('kategori_barang_id')->constrained('kategori_barang')->onDelete('restrict');
            $table->decimal('panjang', 8, 2); // cm
            $table->decimal('lebar', 8, 2); // cm
            $table->decimal('tinggi', 8, 2); // cm
            $table->decimal('berat', 8, 2); // kg
            $table->boolean('mudah_pecah')->default(false);
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $table->text('deskripsi')->nullable();
            $table->string('barcode', 100)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
