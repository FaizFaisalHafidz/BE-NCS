<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaGudangResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gudang_id' => $this->gudang_id,
            'kode_area' => $this->kode_area,
            'nama_area' => $this->nama_area,
            'koordinat_x' => (float) $this->koordinat_x,
            'koordinat_y' => (float) $this->koordinat_y,
            'panjang' => (float) $this->panjang,
            'lebar' => (float) $this->lebar,
            'tinggi' => (float) $this->tinggi,
            'kapasitas' => (float) $this->kapasitas,
            'kapasitas_terpakai' => (float) $this->kapasitas_terpakai,
            'sisa_kapasitas' => (float) $this->sisa_kapasitas,
            'persentase_kapasitas' => (float) $this->persentase_kapasitas,
            'volume' => (float) $this->volume,
            'tersedia' => (bool) $this->tersedia,
            'jenis_area' => $this->jenis_area,
            'status_text' => $this->tersedia ? 'Tersedia' : 'Tidak Tersedia',
            'jenis_text' => $this->getJenisText(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships - only include when loaded
            'gudang' => $this->when(
                $this->relationLoaded('gudang'),
                function () {
                    return [
                        'id' => $this->gudang->id,
                        'nama_gudang' => $this->gudang->nama_gudang,
                        'alamat' => $this->gudang->alamat,
                        'aktif' => (bool) $this->gudang->aktif
                    ];
                }
            ),
            
            'penempatan_barang' => $this->when(
                $this->relationLoaded('penempatanBarang'),
                function () {
                    return $this->penempatanBarang->map(function ($penempatan) {
                        return [
                            'id' => $penempatan->id,
                            'volume_penempatan' => (float) $penempatan->volume_penempatan,
                            'posisi_x' => (float) $penempatan->posisi_x,
                            'posisi_y' => (float) $penempatan->posisi_y,
                            'posisi_z' => (float) $penempatan->posisi_z,
                            'barang' => $this->when(
                                $penempatan->relationLoaded('barang'),
                                function () use ($penempatan) {
                                    return [
                                        'id' => $penempatan->barang->id,
                                        'nama_barang' => $penempatan->barang->nama_barang,
                                        'kode_barang' => $penempatan->barang->kode_barang,
                                    ];
                                }
                            )
                        ];
                    });
                }
            ),
            
            'rekomendasi_saat_ini' => $this->when(
                $this->relationLoaded('rekomendasiSaatIni'),
                function () {
                    return $this->rekomendasiSaatIni->map(function ($rekomendasi) {
                        return [
                            'id' => $rekomendasi->id,
                            'jenis_rekomendasi' => $rekomendasi->jenis_rekomendasi,
                            'prioritas' => $rekomendasi->prioritas,
                            'status' => $rekomendasi->status,
                            'alasan' => $rekomendasi->alasan
                        ];
                    });
                }
            ),
            
            'rekomendasi_tujuan' => $this->when(
                $this->relationLoaded('rekomendasiTujuan'),
                function () {
                    return $this->rekomendasiTujuan->map(function ($rekomendasi) {
                        return [
                            'id' => $rekomendasi->id,
                            'jenis_rekomendasi' => $rekomendasi->jenis_rekomendasi,
                            'prioritas' => $rekomendasi->prioritas,
                            'status' => $rekomendasi->status,
                            'alasan' => $rekomendasi->alasan
                        ];
                    });
                }
            )
        ];
    }

    /**
     * Get jenis area text
     */
    private function getJenisText(): string
    {
        return match($this->jenis_area) {
            'rak' => 'Rak',
            'lantai' => 'Lantai',
            'khusus' => 'Khusus',
            default => ucfirst($this->jenis_area)
        };
    }
}