<?php

namespace App\Services;

use App\Models\PenempatanBarang;
use App\Models\AreaGudang;
use App\Models\Barang;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PenempatanBarangService
{
    /**
     * Mengambil semua data penempatan barang dengan filter dan pagination
     */
    public function getAllPenempatan(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = PenempatanBarang::query()
            ->with(['gudang', 'areaGudang', 'barang.kategoriBarang', 'dibuatOleh'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['gudang_id'])) {
            $query->where('gudang_id', $filters['gudang_id']);
        }

        if (!empty($filters['area_gudang_id'])) {
            $query->where('area_gudang_id', $filters['area_gudang_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('barang', function (Builder $q) use ($search) {
                $q->where('kode_barang', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Membuat penempatan barang baru
     */
    public function createPenempatan(array $data): PenempatanBarang
    {
        DB::beginTransaction();
        
        try {
            // Validasi kapasitas area
            $this->validateKapasitasArea($data['area_gudang_id'], $data['barang_id'], $data['jumlah']);

            // Set user yang membuat
            $data['dibuat_oleh'] = Auth::id();

            // Buat penempatan baru
            $penempatan = PenempatanBarang::create($data);

            // Load relationships
            $penempatan->load(['gudang', 'areaGudang', 'barang.kategoriBarang', 'dibuatOleh']);

            DB::commit();
            
            return $penempatan;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mengambil detail penempatan barang
     */
    public function getPenempatanById(int $id): PenempatanBarang
    {
        return PenempatanBarang::with(['gudang', 'areaGudang', 'barang.kategoriBarang', 'dibuatOleh'])
            ->findOrFail($id);
    }

    /**
     * Update penempatan barang
     */
    public function updatePenempatan(int $id, array $data): PenempatanBarang
    {
        DB::beginTransaction();
        
        try {
            $penempatan = PenempatanBarang::findOrFail($id);

            // Jika ada perubahan jumlah, validasi kapasitas
            if (isset($data['jumlah']) && $data['jumlah'] != $penempatan->jumlah) {
                $selisihJumlah = $data['jumlah'] - $penempatan->jumlah;
                if ($selisihJumlah > 0) {
                    $this->validateKapasitasArea(
                        $penempatan->area_gudang_id, 
                        $penempatan->barang_id, 
                        $selisihJumlah,
                        $penempatan->id
                    );
                }
            }

            $penempatan->update($data);
            $penempatan->load(['gudang', 'areaGudang', 'barang.kategoriBarang', 'dibuatOleh']);

            DB::commit();
            
            return $penempatan;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Hapus penempatan barang
     */
    public function deletePenempatan(int $id): bool
    {
        $penempatan = PenempatanBarang::findOrFail($id);
        return $penempatan->delete();
    }

    /**
     * Validasi kapasitas area gudang
     */
    private function validateKapasitasArea(int $areaGudangId, int $barangId, int $jumlah, ?int $excludePenempatanId = null): void
    {
        $areaGudang = AreaGudang::findOrFail($areaGudangId);
        $barang = Barang::findOrFail($barangId);

        // Hitung volume yang akan ditambahkan
        $volumeTambahan = $barang->volume * $jumlah;

        // Hitung volume yang sudah terpakai di area ini (exclude penempatan yang sedang diupdate)
        $volumeTerpakai = PenempatanBarang::where('area_gudang_id', $areaGudangId)
            ->where('status', '!=', 'diambil')
            ->when($excludePenempatanId, function ($query, $excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->with('barang')
            ->get()
            ->sum(function ($penempatan) {
                return $penempatan->barang->volume * $penempatan->jumlah;
            });

        $volumeSetelahPenempatan = $volumeTerpakai + $volumeTambahan;

        if ($volumeSetelahPenempatan > $areaGudang->kapasitas) {
            $volumeTersisa = $areaGudang->kapasitas - $volumeTerpakai;
            throw new Exception(
                "Kapasitas area gudang tidak mencukupi. " .
                "Volume tersisa: {$volumeTersisa} m³, " .
                "volume yang dibutuhkan: {$volumeTambahan} m³"
            );
        }
    }

    /**
     * Mengambil informasi kapasitas area gudang
     */
    public function getKapasitasArea(int $areaGudangId): array
    {
        $areaGudang = AreaGudang::with('gudang')->findOrFail($areaGudangId);

        // Hitung volume terpakai
        $volumeTerpakai = PenempatanBarang::where('area_gudang_id', $areaGudangId)
            ->where('status', '!=', 'diambil')
            ->with('barang')
            ->get()
            ->sum(function ($penempatan) {
                return $penempatan->barang->volume * $penempatan->jumlah;
            });

        $volumeTersisa = $areaGudang->kapasitas - $volumeTerpakai;
        $persentasePenggunaan = ($volumeTerpakai / $areaGudang->kapasitas) * 100;

        // Hitung total barang
        $totalBarang = PenempatanBarang::where('area_gudang_id', $areaGudangId)
            ->where('status', '!=', 'diambil')
            ->sum('jumlah');

        return [
            'area_gudang' => $areaGudang,
            'kapasitas_total' => $areaGudang->kapasitas,
            'volume_terpakai' => round($volumeTerpakai, 4),
            'volume_tersisa' => round($volumeTersisa, 4),
            'persentase_penggunaan' => round($persentasePenggunaan, 2),
            'total_barang' => $totalBarang,
        ];
    }

    /**
     * Mengambil histori penempatan untuk barang tertentu
     */
    public function getHistoriPenempatan(int $barangId): array
    {
        $histori = PenempatanBarang::where('barang_id', $barangId)
            ->with(['gudang', 'areaGudang', 'dibuatOleh'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $histori->toArray();
    }

    /**
     * Mengambil barang yang mendekati kadaluarsa
     */
    public function getBarangKadaluarsa(int $hari = 7): array
    {
        $barangKadaluarsa = PenempatanBarang::akanKadaluarsa($hari)
            ->where('status', '!=', 'diambil')
            ->with(['gudang', 'areaGudang', 'barang.kategoriBarang', 'dibuatOleh'])
            ->orderBy('tanggal_kadaluarsa', 'asc')
            ->get();

        return $barangKadaluarsa->map(function ($penempatan) {
            $data = $penempatan->toArray();
            $data['hari_tersisa'] = now()->diffInDays($penempatan->tanggal_kadaluarsa, false);
            return $data;
        })->toArray();
    }

    /**
     * Mengambil penempatan berdasarkan status
     */
    public function getPenempatanByStatus(string $status): array
    {
        $penempatan = PenempatanBarang::where('status', $status)
            ->with(['gudang', 'areaGudang', 'barang.kategoriBarang', 'dibuatOleh'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $penempatan->toArray();
    }

    /**
     * Mengambil statistik penempatan
     */
    public function getStatistikPenempatan(): array
    {
        $totalPenempatan = PenempatanBarang::count();
        $totalDitempatkan = PenempatanBarang::ditempatkan()->count();
        $totalDireservasi = PenempatanBarang::direservasi()->count();
        $totalDiambil = PenempatanBarang::diambil()->count();

        $barangKadaluarsa = PenempatanBarang::whereNotNull('tanggal_kadaluarsa')
            ->where('tanggal_kadaluarsa', '<', now())
            ->where('status', '!=', 'diambil')
            ->count();

        $barangAkanKadaluarsa = PenempatanBarang::akanKadaluarsa(7)
            ->where('status', '!=', 'diambil')
            ->count();

        return [
            'total_penempatan' => $totalPenempatan,
            'total_ditempatkan' => $totalDitempatkan,
            'total_direservasi' => $totalDireservasi,
            'total_diambil' => $totalDiambil,
            'barang_kadaluarsa' => $barangKadaluarsa,
            'barang_akan_kadaluarsa' => $barangAkanKadaluarsa,
        ];
    }
}