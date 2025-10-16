<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AreaGudang extends Model
{
    use HasFactory;

    protected $table = 'area_gudang';

    protected $fillable = [
        'gudang_id',
        'kode_area',
        'nama_area',
        'koordinat_x',
        'koordinat_y',
        'panjang',
        'lebar',
        'tinggi',
        'kapasitas',
        'kapasitas_terpakai',
        'tersedia',
        'jenis_area',
    ];

    protected $casts = [
        'koordinat_x' => 'decimal:2',
        'koordinat_y' => 'decimal:2',
        'panjang' => 'decimal:2',
        'lebar' => 'decimal:2',
        'tinggi' => 'decimal:2',
        'kapasitas' => 'decimal:2',
        'kapasitas_terpakai' => 'decimal:2',
        'tersedia' => 'boolean',
    ];

    // Relationships
    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function penempatanBarang()
    {
        return $this->hasMany(PenempatanBarang::class);
    }

    public function rekomendasiSaatIni()
    {
        return $this->hasMany(RekomendasiPenempatan::class, 'area_gudang_saat_ini');
    }

    public function rekomendasiTujuan()
    {
        return $this->hasMany(RekomendasiPenempatan::class, 'area_gudang_rekomendasi');
    }

    // Scopes
    public function scopeTersedia($query)
    {
        return $query->where('tersedia', true);
    }

    public function scopeByJenis($query, $jenis)
    {
        return $query->where('jenis_area', $jenis);
    }

    // Accessors
    public function getPersentaseKapasitasAttribute()
    {
        if ($this->kapasitas == 0) return 0;
        return round(($this->kapasitas_terpakai / $this->kapasitas) * 100, 2);
    }

    public function getSisaKapasitasAttribute()
    {
        return $this->kapasitas - $this->kapasitas_terpakai;
    }

    public function getVolumeAttribute()
    {
        return $this->panjang * $this->lebar * $this->tinggi;
    }
}
