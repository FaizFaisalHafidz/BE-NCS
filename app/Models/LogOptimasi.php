<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogOptimasi extends Model
{
    use HasFactory;

    protected $table = 'log_optimasi';

    protected $fillable = [
        'gudang_id',
        'versi_algoritma',
        'suhu_awal',
        'tingkat_pendinginan',
        'suhu_minimum',
        'iterasi',
        'biaya_awal',
        'biaya_akhir',
        'persentase_perbaikan',
        'waktu_eksekusi',
        'status',
        'detail_hasil',
        'dijalankan_oleh',
    ];

    protected $casts = [
        'suhu_awal' => 'decimal:4',
        'tingkat_pendinginan' => 'decimal:4',
        'suhu_minimum' => 'decimal:4',
        'biaya_awal' => 'decimal:4',
        'biaya_akhir' => 'decimal:4',
        'persentase_perbaikan' => 'decimal:2',
        'waktu_eksekusi' => 'decimal:2',
        'detail_hasil' => 'array',
    ];

    // Relationships
    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function dijalankanOleh()
    {
        return $this->belongsTo(User::class, 'dijalankan_oleh');
    }

    public function rekomendasiPenempatan()
    {
        return $this->hasMany(RekomendasiPenempatan::class);
    }

    // Scopes
    public function scopeSelesai($query)
    {
        return $query->where('status', 'selesai');
    }

    public function scopeBerjalan($query)
    {
        return $query->where('status', 'berjalan');
    }

    public function scopeGagal($query)
    {
        return $query->where('status', 'gagal');
    }

    // Accessors
    public function getPenghematanBiayaAttribute()
    {
        return $this->biaya_awal - $this->biaya_akhir;
    }

    public function getWaktuEksekusiFormattedAttribute()
    {
        $detik = $this->waktu_eksekusi;
        if ($detik < 60) {
            return round($detik, 2) . ' detik';
        } elseif ($detik < 3600) {
            $menit = floor($detik / 60);
            $sisa = $detik % 60;
            return $menit . ' menit ' . round($sisa, 0) . ' detik';
        } else {
            $jam = floor($detik / 3600);
            $menit = floor(($detik % 3600) / 60);
            return $jam . ' jam ' . $menit . ' menit';
        }
    }
}
