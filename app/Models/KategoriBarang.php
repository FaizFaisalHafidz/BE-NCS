<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KategoriBarang extends Model
{
    use HasFactory;

    protected $table = 'kategori_barang';

    protected $fillable = [
        'nama_kategori',
        'deskripsi',
        'kode_kategori',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    // Relationships
    public function barang()
    {
        return $this->hasMany(Barang::class);
    }

    // Scopes
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    // Accessors
    public function getJumlahBarangAttribute()
    {
        return $this->barang()->count();
    }
}
