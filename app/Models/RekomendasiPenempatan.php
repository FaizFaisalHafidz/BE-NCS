<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RekomendasiPenempatan extends Model
{
    use HasFactory;

    protected $table = 'rekomendasi_penempatan';

    protected $fillable = [
        'log_optimasi_id',
        'barang_id',
        'area_gudang_saat_ini',
        'area_gudang_rekomendasi',
        'alasan',
        'prioritas',
        'status',
        'catatan',
        'disetujui_oleh',
        'tanggal_persetujuan',
    ];

    protected $casts = [
        'tanggal_persetujuan' => 'datetime',
    ];

    // Relationships
    public function logOptimasi()
    {
        return $this->belongsTo(LogOptimasi::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function areaGudangSaatIni()
    {
        return $this->belongsTo(AreaGudang::class, 'area_gudang_saat_ini');
    }

    public function areaGudangRekomendasi()
    {
        return $this->belongsTo(AreaGudang::class, 'area_gudang_rekomendasi');
    }

    public function disetujuiOleh()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    // Scopes
    public function scopeMenunggu($query)
    {
        return $query->where('status', 'menunggu');
    }

    public function scopeDisetujui($query)
    {
        return $query->where('status', 'disetujui');
    }

    public function scopeDitolak($query)
    {
        return $query->where('status', 'ditolak');
    }

    public function scopeDiimplementasi($query)
    {
        return $query->where('status', 'diimplementasi');
    }

    public function scopeByPrioritas($query, $prioritas)
    {
        return $query->where('prioritas', $prioritas);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'menunggu' => 'warning',
            'disetujui' => 'success', 
            'ditolak' => 'danger',
            'diimplementasi' => 'info'
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    public function getPrioritasBadgeAttribute()
    {
        $badges = [
            'rendah' => 'success',
            'sedang' => 'warning',
            'tinggi' => 'danger'
        ];

        return $badges[$this->prioritas] ?? 'secondary';
    }
}
