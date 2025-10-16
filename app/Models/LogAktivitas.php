<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogAktivitas extends Model
{
    use HasFactory;

    protected $table = 'log_aktivitas';

    protected $fillable = [
        'user_id',
        'aksi',
        'deskripsi',
        'model_type',
        'model_id',
        'ip_address',
        'user_agent',
        'data_lama',
        'data_baru',
    ];

    protected $casts = [
        'data_lama' => 'array',
        'data_baru' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAksi($query, $aksi)
    {
        return $query->where('aksi', $aksi);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    // Accessors
    public function getWaktuAttribute()
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }

    public function getAksiFormattedAttribute()
    {
        $aksi_map = [
            'create' => 'Menambah',
            'update' => 'Mengubah',
            'delete' => 'Menghapus',
            'login' => 'Masuk',
            'logout' => 'Keluar',
            'optimize' => 'Optimasi',
            'approve' => 'Menyetujui',
            'reject' => 'Menolak',
        ];

        return $aksi_map[$this->aksi] ?? $this->aksi;
    }

    // Static methods
    public static function log($aksi, $deskripsi, $model = null, $dataLama = null, $dataBaru = null)
    {
        return self::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data_lama' => $dataLama,
            'data_baru' => $dataBaru,
        ]);
    }
}
