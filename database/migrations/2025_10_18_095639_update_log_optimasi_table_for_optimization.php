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
        Schema::table('log_optimasi', function (Blueprint $table) {
            // Update status enum to include new values
            $table->dropColumn('status');
        });
        
        Schema::table('log_optimasi', function (Blueprint $table) {
            $table->enum('status', ['berjalan', 'selesai', 'gagal', 'sedang_berjalan', 'dibatalkan'])
                  ->default('berjalan')->after('waktu_eksekusi');
        });
        
        Schema::table('log_optimasi', function (Blueprint $table) {
            // Add new columns for enhanced optimization tracking
            $table->string('algoritma', 100)->default('Simulated Annealing')->after('id');
            $table->json('parameter_optimasi')->nullable()->after('algoritma');
            $table->string('target_optimasi')->nullable()->after('parameter_optimasi');
            $table->integer('estimasi_waktu')->nullable()->after('target_optimasi');
            $table->timestamp('waktu_mulai')->nullable()->after('estimasi_waktu');
            $table->timestamp('waktu_selesai')->nullable()->after('waktu_mulai');
            $table->json('hasil_optimasi')->nullable()->after('detail_hasil');
            $table->json('metrik_hasil')->nullable()->after('hasil_optimasi');
            $table->text('log_error')->nullable()->after('metrik_hasil');
            $table->foreignId('dibuat_oleh')->nullable()->after('log_error');
            
            // Make some existing columns nullable
            $table->foreignId('gudang_id')->nullable()->change();
            $table->string('versi_algoritma', 20)->nullable()->change();
            $table->decimal('suhu_awal', 10, 4)->nullable()->change();
            $table->decimal('tingkat_pendinginan', 6, 4)->nullable()->change();
            $table->decimal('suhu_minimum', 10, 4)->nullable()->change();
            $table->integer('iterasi')->nullable()->change();
            $table->decimal('biaya_awal', 15, 4)->nullable()->change();
            $table->decimal('biaya_akhir', 15, 4)->nullable()->change();
            $table->decimal('persentase_perbaikan', 5, 2)->nullable()->change();
            $table->decimal('waktu_eksekusi', 8, 2)->nullable()->change();
            
            // Drop the constraint on dijalankan_oleh and add index for dibuat_oleh
            $table->dropForeign(['dijalankan_oleh']);
            $table->foreignId('dijalankan_oleh')->nullable()->change();
            $table->index('dibuat_oleh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_optimasi', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn([
                'algoritma',
                'parameter_optimasi', 
                'target_optimasi',
                'estimasi_waktu',
                'waktu_mulai',
                'waktu_selesai',
                'hasil_optimasi',
                'metrik_hasil',
                'log_error',
                'dibuat_oleh'
            ]);
            
            // Restore original constraints
            $table->foreignId('gudang_id')->change();
            $table->string('versi_algoritma', 20)->change();
            $table->decimal('suhu_awal', 10, 4)->change();
            $table->decimal('tingkat_pendinginan', 6, 4)->change();
            $table->decimal('suhu_minimum', 10, 4)->change();
            $table->integer('iterasi')->change();
            $table->decimal('biaya_awal', 15, 4)->change();
            $table->decimal('biaya_akhir', 15, 4)->change();
            $table->decimal('persentase_perbaikan', 5, 2)->change();
            $table->decimal('waktu_eksekusi', 8, 2)->change();
            $table->foreignId('dijalankan_oleh')->constrained('users')->onDelete('restrict')->change();
        });
        
        // Restore original status enum
        Schema::table('log_optimasi', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('log_optimasi', function (Blueprint $table) {
            $table->enum('status', ['berjalan', 'selesai', 'gagal'])->default('berjalan');
        });
    }
};
