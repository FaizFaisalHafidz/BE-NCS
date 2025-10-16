<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kategori_barang_id',
        'panjang',
        'lebar',
        'tinggi',
        'berat',
        'mudah_pecah',
        'prioritas',
        'deskripsi',
        'barcode',
        'aktif',
    ];

    protected $casts = [
        'panjang' => 'decimal:2',
        'lebar' => 'decimal:2',
        'tinggi' => 'decimal:2',
        'berat' => 'decimal:2',
        'mudah_pecah' => 'boolean',
        'aktif' => 'boolean',
    ];

    // Relationships
    public function kategoriBarang()
    {
        return $this->belongsTo(KategoriBarang::class);
    }

    public function penempatanBarang()
    {
        return $this->hasMany(PenempatanBarang::class);
    }

    public function rekomendasiPenempatan()
    {
        return $this->hasMany(RekomendasiPenempatan::class);
    }

    // Scopes
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public function scopeByPrioritas($query, $prioritas)
    {
        return $query->where('prioritas', $prioritas);
    }

    public function scopeMudahPecah($query)
    {
        return $query->where('mudah_pecah', true);
    }

    // Accessors
    public function getVolumeAttribute()
    {
        return ($this->panjang / 100) * ($this->lebar / 100) * ($this->tinggi / 100); // convert cm to m3
    }

    public function getTotalStokAttribute()
    {
        return $this->penempatanBarang()
            ->where('status', 'ditempatkan')
            ->sum('jumlah');
    }

    public function getDimensiAttribute()
    {
        return "{$this->panjang} x {$this->lebar} x {$this->tinggi} cm";
    }
}
