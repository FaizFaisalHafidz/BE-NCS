<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PenempatanBarangRequest;
use App\Services\PenempatanBarangService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Penempatan Barang",
 *     description="API endpoints untuk manajemen penempatan barang"
 * )
 */
class PenempatanBarangController extends Controller
{
    protected $penempatanBarangService;

    public function __construct(PenempatanBarangService $penempatanBarangService)
    {
        $this->penempatanBarangService = $penempatanBarangService;
    }

    /**
     * @OA\Get(
     *     path="/api/penempatan-barang",
     *     summary="Daftar penempatan barang",
     *     description="Mengambil daftar penempatan barang dengan pagination dan filter",
     *     tags={"Penempatan Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Nomor halaman",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Jumlah data per halaman",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="gudang_id",
     *         in="query",
     *         description="Filter berdasarkan gudang",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="area_gudang_id",
     *         in="query",
     *         description="Filter berdasarkan area gudang",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter berdasarkan status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"ditempatkan", "direservasi", "diambil"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Pencarian berdasarkan kode/nama barang",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar penempatan barang berhasil diambil",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar penempatan barang berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="gudang_id", type="integer"),
     *                         @OA\Property(property="area_gudang_id", type="integer"),
     *                         @OA\Property(property="barang_id", type="integer"),
     *                         @OA\Property(property="jumlah", type="integer"),
     *                         @OA\Property(property="tanggal_penempatan", type="string", format="datetime"),
     *                         @OA\Property(property="tanggal_kadaluarsa", type="string", format="datetime"),
     *                         @OA\Property(property="status", type="string"),
     *                         @OA\Property(property="keterangan", type="string"),
     *                         @OA\Property(property="total_volume", type="number"),
     *                         @OA\Property(property="is_kadaluarsa", type="boolean"),
     *                         @OA\Property(property="gudang", type="object"),
     *                         @OA\Property(property="area_gudang", type="object"),
     *                         @OA\Property(property="barang", type="object"),
     *                         @OA\Property(property="dibuat_oleh", type="object")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['gudang_id', 'area_gudang_id', 'status', 'search']);
        $perPage = $request->get('per_page', 10);

        $penempatan = $this->penempatanBarangService->getAllPenempatan($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar penempatan barang berhasil diambil',
            'data' => $penempatan
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/penempatan-barang",
     *     summary="Buat penempatan barang baru",
     *     description="Membuat penempatan barang baru dengan validasi kapasitas area",
     *     tags={"Penempatan Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"gudang_id", "area_gudang_id", "barang_id", "jumlah", "tanggal_penempatan"},
     *             @OA\Property(property="gudang_id", type="integer", example=1),
     *             @OA\Property(property="area_gudang_id", type="integer", example=1),
     *             @OA\Property(property="barang_id", type="integer", example=1),
     *             @OA\Property(property="jumlah", type="integer", example=5),
     *             @OA\Property(property="tanggal_penempatan", type="string", format="date", example="2024-10-18"),
     *             @OA\Property(property="tanggal_kadaluarsa", type="string", format="date", example="2024-12-31"),
     *             @OA\Property(property="status", type="string", enum={"ditempatkan", "direservasi"}, example="ditempatkan"),
     *             @OA\Property(property="keterangan", type="string", example="Penempatan barang elektronik")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Penempatan barang berhasil dibuat",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Penempatan barang berhasil dibuat"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(PenempatanBarangRequest $request): JsonResponse
    {
        $penempatan = $this->penempatanBarangService->createPenempatan($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Penempatan barang berhasil dibuat',
            'data' => $penempatan
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/penempatan-barang/{id}",
     *     summary="Detail penempatan barang",
     *     description="Mengambil detail penempatan barang berdasarkan ID",
     *     tags={"Penempatan Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID penempatan barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail penempatan barang berhasil diambil",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detail penempatan barang berhasil diambil"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Penempatan barang tidak ditemukan"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $penempatan = $this->penempatanBarangService->getPenempatanById($id);

        return response()->json([
            'success' => true,
            'message' => 'Detail penempatan barang berhasil diambil',
            'data' => $penempatan
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/penempatan-barang/{id}",
     *     summary="Update penempatan barang",
     *     description="Mengupdate data penempatan barang",
     *     tags={"Penempatan Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID penempatan barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="jumlah", type="integer", example=10),
     *             @OA\Property(property="tanggal_kadaluarsa", type="string", format="date", example="2024-12-31"),
     *             @OA\Property(property="status", type="string", enum={"ditempatkan", "direservasi", "diambil"}, example="diambil"),
     *             @OA\Property(property="keterangan", type="string", example="Update keterangan penempatan")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Penempatan barang berhasil diupdate",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Penempatan barang berhasil diupdate"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(PenempatanBarangRequest $request, int $id): JsonResponse
    {
        $penempatan = $this->penempatanBarangService->updatePenempatan($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Penempatan barang berhasil diupdate',
            'data' => $penempatan
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/penempatan-barang/{id}",
     *     summary="Hapus penempatan barang",
     *     description="Menghapus data penempatan barang",
     *     tags={"Penempatan Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID penempatan barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Penempatan barang berhasil dihapus",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Penempatan barang berhasil dihapus")
     *         )
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->penempatanBarangService->deletePenempatan($id);

        return response()->json([
            'success' => true,
            'message' => 'Penempatan barang berhasil dihapus'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/penempatan-barang/area/{areaId}/kapasitas",
     *     summary="Cek kapasitas area gudang",
     *     description="Mengecek kapasitas tersisa di area gudang tertentu",
     *     tags={"Penempatan Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="areaId",
     *         in="path",
     *         description="ID area gudang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Informasi kapasitas area berhasil diambil",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Informasi kapasitas area berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="area_gudang", type="object"),
     *                 @OA\Property(property="kapasitas_total", type="number"),
     *                 @OA\Property(property="volume_terpakai", type="number"),
     *                 @OA\Property(property="volume_tersisa", type="number"),
     *                 @OA\Property(property="persentase_penggunaan", type="number"),
     *                 @OA\Property(property="total_barang", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function checkKapasitasArea(int $areaId): JsonResponse
    {
        $kapasitas = $this->penempatanBarangService->getKapasitasArea($areaId);

        return response()->json([
            'success' => true,
            'message' => 'Informasi kapasitas area berhasil diambil',
            'data' => $kapasitas
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/penempatan-barang/barang/{barangId}/histori",
     *     summary="Histori penempatan barang",
     *     description="Mengambil histori penempatan untuk barang tertentu",
     *     tags={"Penempatan Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="barangId",
     *         in="path",
     *         description="ID barang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Histori penempatan barang berhasil diambil",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Histori penempatan barang berhasil diambil"),
     *             @OA\Property(
     *                 property="data", 
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="gudang", type="object"),
     *                     @OA\Property(property="area_gudang", type="object"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="tanggal_penempatan", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function historiPenempatan(int $barangId): JsonResponse
    {
        $histori = $this->penempatanBarangService->getHistoriPenempatan($barangId);

        return response()->json([
            'success' => true,
            'message' => 'Histori penempatan barang berhasil diambil',
            'data' => $histori
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/penempatan-barang/kadaluarsa",
     *     summary="Barang mendekati kadaluarsa",
     *     description="Mengambil daftar penempatan barang yang mendekati tanggal kadaluarsa",
     *     tags={"Penempatan Barang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="hari",
     *         in="query",
     *         description="Jumlah hari sebelum kadaluarsa (default: 7)",
     *         required=false,
     *         @OA\Schema(type="integer", example=7)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar barang mendekati kadaluarsa berhasil diambil",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar barang mendekati kadaluarsa berhasil diambil"),
     *             @OA\Property(
     *                 property="data", 
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="barang", type="object"),
     *                     @OA\Property(property="tanggal_kadaluarsa", type="string"),
     *                     @OA\Property(property="hari_tersisa", type="integer")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function barangKadaluarsa(Request $request): JsonResponse
    {
        $hari = $request->get('hari', 7);
        $barangKadaluarsa = $this->penempatanBarangService->getBarangKadaluarsa($hari);

        return response()->json([
            'success' => true,
            'message' => 'Daftar barang mendekati kadaluarsa berhasil diambil',
            'data' => $barangKadaluarsa
        ]);
    }
}