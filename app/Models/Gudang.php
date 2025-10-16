<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gudang extends Model
{
    use HasFactory;

    protected $table = 'gudang';

    protected $fillable = [
        'nama_gudang',
        'alamat',
        'total_kapasitas',
        'kapasitas_terpakai',
        'panjang',
        'lebar',
        'tinggi',
        'aktif',
    ];

    protected $casts = [
        'total_kapasitas' => 'decimal:2',
        'kapasitas_terpakai' => 'decimal:2',
        'panjang' => 'decimal:2',
        'lebar' => 'decimal:2',
        'tinggi' => 'decimal:2',
        'aktif' => 'boolean',
    ];

    // Relationships
    public function areaGudang()
    {
        return $this->hasMany(AreaGudang::class);
    }

    public function penempatanBarang()
    {
        return $this->hasMany(PenempatanBarang::class);
    }

    public function logOptimasi()
    {
        return $this->hasMany(LogOptimasi::class);
    }

    // Scopes
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    // Accessors
    public function getPersentaseKapasitasAttribute()
    {
        if ($this->total_kapasitas == 0) return 0;
        return round(($this->kapasitas_terpakai / $this->total_kapasitas) * 100, 2);
    }

    public function getSisaKapasitasAttribute()
    {
        return $this->total_kapasitas - $this->kapasitas_terpakai;
    }
}
