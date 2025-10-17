<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GudangResource extends JsonResource
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
            'nama_gudang' => $this->nama_gudang,
            'alamat' => $this->alamat,
            'total_kapasitas' => (float) $this->total_kapasitas,
            'kapasitas_terpakai' => (float) $this->kapasitas_terpakai,
            'sisa_kapasitas' => (float) $this->sisa_kapasitas,
            'persentase_kapasitas' => (float) $this->persentase_kapasitas,
            'panjang' => (float) $this->panjang,
            'lebar' => (float) $this->lebar,
            'tinggi' => (float) $this->tinggi,
            'aktif' => (bool) $this->aktif,
            'status_text' => $this->aktif ? 'Aktif' : 'Tidak Aktif',
            'jumlah_area' => $this->when(
                $this->relationLoaded('areaGudang'),
                function () {
                    return $this->areaGudang->count();
                },
                0
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships - only include when loaded
            'area_gudang' => $this->when(
                $this->relationLoaded('areaGudang'),
                function () {
                    return $this->areaGudang->map(function ($area) {
                        return [
                            'id' => $area->id,
                            'nama_area' => $area->nama_area,
                            'kode_area' => $area->kode_area,
                            'kapasitas_area' => (float) $area->kapasitas_area,
                            'aktif' => (bool) $area->aktif
                        ];
                    });
                }
            ),
            
            'penempatan_barang' => $this->when(
                $this->relationLoaded('penempatanBarang'),
                function () {
                    return $this->penempatanBarang->map(function ($penempatan) {
                        return [
                            'id' => $penempatan->id,
                            'barang' => [
                                'id' => $penempatan->barang->id,
                                'nama_barang' => $penempatan->barang->nama_barang,
                                'kode_barang' => $penempatan->barang->kode_barang,
                            ],
                            'volume_penempatan' => (float) $penempatan->volume_penempatan,
                            'posisi_x' => (float) $penempatan->posisi_x,
                            'posisi_y' => (float) $penempatan->posisi_y,
                            'posisi_z' => (float) $penempatan->posisi_z,
                        ];
                    });
                }
            ),
            
            'log_optimasi_terbaru' => $this->when(
                $this->relationLoaded('logOptimasi'),
                function () {
                    return $this->logOptimasi->take(3)->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'jenis_optimasi' => $log->jenis_optimasi,
                            'status' => $log->status,
                            'hasil_optimasi' => $log->hasil_optimasi,
                            'created_at' => $log->created_at
                        ];
                    });
                }
            )
        ];
    }
}