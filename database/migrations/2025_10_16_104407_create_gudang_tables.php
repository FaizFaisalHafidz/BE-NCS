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
        Schema::create('gudang', function (Blueprint $table) {
            $table->id();
            $table->string('nama_gudang', 100);
            $table->text('alamat');
            $table->decimal('total_kapasitas', 12, 2); // dalam meter kubik
            $table->decimal('kapasitas_terpakai', 12, 2)->default(0);
            $table->decimal('panjang', 8, 2); // meter
            $table->decimal('lebar', 8, 2); // meter
            $table->decimal('tinggi', 8, 2); // meter
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gudang');
    }
};
