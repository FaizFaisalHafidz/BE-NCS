<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PenempatanBarang extends Model
{
    use HasFactory;

    protected $table = 'penempatan_barang';

    protected $fillable = [
        'gudang_id',
        'area_gudang_id',
        'barang_id',
        'jumlah',
        'tanggal_penempatan',
        'tanggal_kadaluarsa',
        'status',
        'keterangan',
        'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal_penempatan' => 'datetime',
        'tanggal_kadaluarsa' => 'datetime',
    ];

    // Relationships
    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function areaGudang()
    {
        return $this->belongsTo(AreaGudang::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    // Scopes
    public function scopeDitempatkan($query)
    {
        return $query->where('status', 'ditempatkan');
    }

    public function scopeDireservasi($query)
    {
        return $query->where('status', 'direservasi');
    }

    public function scopeDiambil($query)
    {
        return $query->where('status', 'diambil');
    }

    public function scopeAkanKadaluarsa($query, $hari = 7)
    {
        return $query->whereNotNull('tanggal_kadaluarsa')
            ->where('tanggal_kadaluarsa', '<=', now()->addDays($hari));
    }

    // Accessors
    public function getTotalVolumeAttribute()
    {
        return $this->barang->volume * $this->jumlah;
    }

    public function getIsKadaluarsaAttribute()
    {
        return $this->tanggal_kadaluarsa && $this->tanggal_kadaluarsa < now();
    }
}
