<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KategoriBarangResource extends JsonResource
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
            'nama_kategori' => $this->nama_kategori,
            'kode_kategori' => $this->kode_kategori,
            'deskripsi' => $this->deskripsi,
            'aktif' => $this->aktif,
            'jumlah_barang' => $this->barang_count ?? $this->jumlah_barang ?? 0,
            'status_text' => $this->aktif ? 'Aktif' : 'Nonaktif',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Load relationships when requested
            'barang' => $this->whenLoaded('barang'),
        ];
    }
}