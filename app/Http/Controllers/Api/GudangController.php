<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGudangRequest;
use App\Http\Requests\UpdateGudangRequest;
use App\Http\Resources\GudangResource;
use App\Services\GudangService;
use App\Models\Gudang;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Gudang",
 *     description="API Endpoints untuk mengelola gudang"
 * )
 */
class GudangController extends Controller
{
    protected $gudangService;

    public function __construct(GudangService $gudangService)
    {
        $this->gudangService = $gudangService;
    }

    /**
     * @OA\Get(
     *     path="/api/gudang",
     *     summary="Mendapatkan daftar gudang",
     *     description="Mengambil daftar semua gudang dengan pagination dan filter",
     *     operationId="getGudangList",
     *     tags={"Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Pencarian berdasarkan nama gudang atau alamat",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter berdasarkan status aktif (1=aktif, 0=tidak aktif)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0,1})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Jumlah data per halaman (default: 10)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar gudang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar gudang berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Gudang")),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $this->authorize('gudang.view');

        $gudang = $this->gudangService->getGudang($request->all());

        return $this->successResponse(
            'Daftar gudang berhasil diambil',
            $gudang
        );
    }

    /**
     * @OA\Post(
     *     path="/api/gudang",
     *     summary="Membuat gudang baru",
     *     description="Membuat data gudang baru dengan validasi",
     *     operationId="createGudang",
     *     tags={"Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama_gudang","alamat","total_kapasitas","panjang","lebar","tinggi"},
     *             @OA\Property(property="nama_gudang", type="string", example="Gudang Utama"),
     *             @OA\Property(property="alamat", type="string", example="Jl. Industri No. 123, Jakarta"),
     *             @OA\Property(property="total_kapasitas", type="number", format="float", example=1000.50),
     *             @OA\Property(property="panjang", type="number", format="float", example=50.00),
     *             @OA\Property(property="lebar", type="number", format="float", example=30.00),
     *             @OA\Property(property="tinggi", type="number", format="float", example=8.00),
     *             @OA\Property(property="aktif", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Gudang berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Gudang berhasil dibuat"),
     *             @OA\Property(property="data", ref="#/components/schemas/Gudang")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function store(StoreGudangRequest $request)
    {
        $this->authorize('gudang.create');

        $gudang = $this->gudangService->createGudang($request->validated());

        return $this->successResponse(
            'Gudang berhasil dibuat',
            new GudangResource($gudang),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/gudang/{id}",
     *     summary="Mendapatkan detail gudang",
     *     description="Mengambil detail gudang berdasarkan ID",
     *     operationId="getGudangDetail",
     *     tags={"Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID Gudang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail gudang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detail gudang berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/Gudang")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Gudang tidak ditemukan"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function show(Gudang $gudang)
    {
        $this->authorize('gudang.view');

        $detailGudang = $this->gudangService->getGudangDetail($gudang);

        return $this->successResponse(
            'Detail gudang berhasil diambil',
            new GudangResource($detailGudang)
        );
    }

    /**
     * @OA\Put(
     *     path="/api/gudang/{id}",
     *     summary="Update gudang",
     *     description="Mengupdate data gudang",
     *     operationId="updateGudang",
     *     tags={"Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID Gudang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nama_gudang", type="string", example="Gudang Utama Updated"),
     *             @OA\Property(property="alamat", type="string", example="Jl. Industri No. 456, Jakarta"),
     *             @OA\Property(property="total_kapasitas", type="number", format="float", example=1500.00),
     *             @OA\Property(property="panjang", type="number", format="float", example=60.00),
     *             @OA\Property(property="lebar", type="number", format="float", example=35.00),
     *             @OA\Property(property="tinggi", type="number", format="float", example=10.00),
     *             @OA\Property(property="aktif", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Gudang berhasil diupdate",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Gudang berhasil diupdate"),
     *             @OA\Property(property="data", ref="#/components/schemas/Gudang")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Gudang tidak ditemukan"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function update(UpdateGudangRequest $request, Gudang $gudang)
    {
        $this->authorize('gudang.update');

        $updatedGudang = $this->gudangService->updateGudang($gudang, $request->validated());

        return $this->successResponse(
            'Gudang berhasil diupdate',
            new GudangResource($updatedGudang)
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/gudang/{id}",
     *     summary="Hapus gudang",
     *     description="Menghapus gudang (soft delete jika memiliki relasi)",
     *     operationId="deleteGudang",
     *     tags={"Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID Gudang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Gudang berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Gudang berhasil dihapus")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Tidak dapat menghapus gudang yang memiliki data terkait"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Gudang tidak ditemukan"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function destroy(Gudang $gudang)
    {
        $this->authorize('gudang.delete');

        $this->gudangService->deleteGudang($gudang);

        return $this->successResponse('Gudang berhasil dihapus');
    }

    /**
     * @OA\Get(
     *     path="/api/gudang/stats",
     *     summary="Mendapatkan statistik gudang",
     *     description="Mengambil statistik dan ringkasan data gudang",
     *     operationId="getGudangStats",
     *     tags={"Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistik gudang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistik gudang berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_gudang", type="integer", example=5),
     *                 @OA\Property(property="gudang_aktif", type="integer", example=4),
     *                 @OA\Property(property="gudang_tidak_aktif", type="integer", example=1),
     *                 @OA\Property(property="total_kapasitas", type="number", format="float", example=5000.00),
     *                 @OA\Property(property="kapasitas_terpakai", type="number", format="float", example=3200.50),
     *                 @OA\Property(property="persentase_penggunaan", type="number", format="float", example=64.01)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function stats()
    {
        $this->authorize('gudang.view');

        $stats = $this->gudangService->getGudangStats();

        return $this->successResponse(
            'Statistik gudang berhasil diambil',
            $stats
        );
    }

    /**
     * @OA\Patch(
     *     path="/api/gudang/{id}/toggle-status",
     *     summary="Toggle status aktif gudang",
     *     description="Mengubah status aktif/tidak aktif gudang",
     *     operationId="toggleGudangStatus",
     *     tags={"Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID Gudang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status gudang berhasil diubah",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Status gudang berhasil diubah"),
     *             @OA\Property(property="data", ref="#/components/schemas/Gudang")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Gudang tidak ditemukan"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function toggleStatus(Gudang $gudang)
    {
        $this->authorize('gudang.update');

        $updatedGudang = $this->gudangService->toggleGudangStatus($gudang);

        return $this->successResponse(
            'Status gudang berhasil diubah',
            new GudangResource($updatedGudang)
        );
    }
}