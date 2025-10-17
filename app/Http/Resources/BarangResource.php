<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangResource extends JsonResource
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
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'kategori_barang_id' => $this->kategori_barang_id,
            'panjang' => $this->panjang,
            'lebar' => $this->lebar,
            'tinggi' => $this->tinggi,
            'berat' => $this->berat,
            'mudah_pecah' => $this->mudah_pecah,
            'prioritas' => $this->prioritas,
            'deskripsi' => $this->deskripsi,
            'barcode' => $this->barcode,
            'aktif' => $this->aktif,
            
            // Computed attributes
            'volume' => $this->volume,
            'total_stok' => $this->total_stok,
            'dimensi' => $this->dimensi,
            
            // Text representations
            'prioritas_text' => $this->getPrioritasText(),
            'status_text' => $this->aktif ? 'Aktif' : 'Nonaktif',
            'mudah_pecah_text' => $this->mudah_pecah ? 'Ya' : 'Tidak',
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Load relationships when requested
            'kategori_barang' => new KategoriBarangResource($this->whenLoaded('kategoriBarang')),
            'penempatan_barang' => $this->whenLoaded('penempatanBarang'),
        ];
    }

    /**
     * Get prioritas text representation
     */
    private function getPrioritasText(): string
    {
        $prioritasMap = [
            'rendah' => 'Rendah',
            'sedang' => 'Sedang',
            'tinggi' => 'Tinggi'
        ];

        return $prioritasMap[$this->prioritas] ?? 'Unknown';
    }
}