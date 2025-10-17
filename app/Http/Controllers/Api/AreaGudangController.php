<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAreaGudangRequest;
use App\Http\Requests\UpdateAreaGudangRequest;
use App\Http\Resources\AreaGudangResource;
use App\Services\AreaGudangService;
use App\Models\AreaGudang;
use App\Models\Gudang;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Area Gudang",
 *     description="API Endpoints untuk mengelola area dalam gudang"
 * )
 */
class AreaGudangController extends Controller
{
    protected $areaGudangService;

    public function __construct(AreaGudangService $areaGudangService)
    {
        $this->areaGudangService = $areaGudangService;
    }

    /**
     * @OA\Get(
     *     path="/api/area-gudang",
     *     summary="Mendapatkan daftar area gudang",
     *     description="Mengambil daftar semua area gudang dengan pagination dan filter",
     *     operationId="getAreaGudangList",
     *     tags={"Area Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="gudang_id",
     *         in="query",
     *         description="Filter berdasarkan ID gudang",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="jenis_area",
     *         in="query",
     *         description="Filter berdasarkan jenis area (rak, lantai, khusus)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"rak","lantai","khusus"})
     *     ),
     *     @OA\Parameter(
     *         name="tersedia",
     *         in="query",
     *         description="Filter berdasarkan ketersediaan (1=tersedia, 0=tidak tersedia)",
     *         required=false,
     *         @OA\Schema(type="integer", enum={0,1})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Pencarian berdasarkan nama area atau kode area",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *         description="Daftar area gudang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar area gudang berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AreaGudang")),
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
        $this->authorize('area-gudang.view');

        $areaGudang = $this->areaGudangService->getAreaGudang($request->all());

        return $this->successResponse(
            'Daftar area gudang berhasil diambil',
            $areaGudang
        );
    }

    /**
     * @OA\Post(
     *     path="/api/area-gudang",
     *     summary="Membuat area gudang baru",
     *     description="Membuat data area gudang baru dengan validasi",
     *     operationId="createAreaGudang",
     *     tags={"Area Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"gudang_id","kode_area","nama_area","koordinat_x","koordinat_y","panjang","lebar","tinggi","kapasitas","jenis_area"},
     *             @OA\Property(property="gudang_id", type="integer", example=1),
     *             @OA\Property(property="kode_area", type="string", example="A1-03"),
     *             @OA\Property(property="nama_area", type="string", example="Area A1 - Rak Rendah"),
     *             @OA\Property(property="koordinat_x", type="number", format="float", example=25.00),
     *             @OA\Property(property="koordinat_y", type="number", format="float", example=5.00),
     *             @OA\Property(property="panjang", type="number", format="float", example=10.00),
     *             @OA\Property(property="lebar", type="number", format="float", example=8.00),
     *             @OA\Property(property="tinggi", type="number", format="float", example=3.00),
     *             @OA\Property(property="kapasitas", type="number", format="float", example=240.00),
     *             @OA\Property(property="jenis_area", type="string", enum={"rak","lantai","khusus"}, example="rak"),
     *             @OA\Property(property="tersedia", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Area gudang berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Area gudang berhasil dibuat"),
     *             @OA\Property(property="data", ref="#/components/schemas/AreaGudang")
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
    public function store(StoreAreaGudangRequest $request)
    {
        $this->authorize('area-gudang.create');

        $areaGudang = $this->areaGudangService->createAreaGudang($request->validated());

        return $this->successResponse(
            'Area gudang berhasil dibuat',
            new AreaGudangResource($areaGudang),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/area-gudang/{id}",
     *     summary="Mendapatkan detail area gudang",
     *     description="Mengambil detail area gudang berdasarkan ID",
     *     operationId="getAreaGudangDetail",
     *     tags={"Area Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID Area Gudang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail area gudang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detail area gudang berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/AreaGudang")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area gudang tidak ditemukan"
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
    public function show(AreaGudang $areaGudang)
    {
        $this->authorize('area-gudang.view');

        $detailAreaGudang = $this->areaGudangService->getAreaGudangDetail($areaGudang);

        return $this->successResponse(
            'Detail area gudang berhasil diambil',
            new AreaGudangResource($detailAreaGudang)
        );
    }

    /**
     * @OA\Put(
     *     path="/api/area-gudang/{id}",
     *     summary="Update area gudang",
     *     description="Mengupdate data area gudang",
     *     operationId="updateAreaGudang",
     *     tags={"Area Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID Area Gudang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="kode_area", type="string", example="A1-03-UPD"),
     *             @OA\Property(property="nama_area", type="string", example="Area A1 - Rak Rendah Updated"),
     *             @OA\Property(property="koordinat_x", type="number", format="float", example=26.00),
     *             @OA\Property(property="koordinat_y", type="number", format="float", example=6.00),
     *             @OA\Property(property="panjang", type="number", format="float", example=12.00),
     *             @OA\Property(property="lebar", type="number", format="float", example=9.00),
     *             @OA\Property(property="tinggi", type="number", format="float", example=4.00),
     *             @OA\Property(property="kapasitas", type="number", format="float", example=432.00),
     *             @OA\Property(property="jenis_area", type="string", enum={"rak","lantai","khusus"}, example="rak"),
     *             @OA\Property(property="tersedia", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Area gudang berhasil diupdate",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Area gudang berhasil diupdate"),
     *             @OA\Property(property="data", ref="#/components/schemas/AreaGudang")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area gudang tidak ditemukan"
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
    public function update(UpdateAreaGudangRequest $request, AreaGudang $areaGudang)
    {
        $this->authorize('area-gudang.update');

        $updatedAreaGudang = $this->areaGudangService->updateAreaGudang($areaGudang, $request->validated());

        return $this->successResponse(
            'Area gudang berhasil diupdate',
            new AreaGudangResource($updatedAreaGudang)
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/area-gudang/{id}",
     *     summary="Hapus area gudang",
     *     description="Menghapus area gudang (soft delete jika memiliki relasi)",
     *     operationId="deleteAreaGudang",
     *     tags={"Area Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID Area Gudang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Area gudang berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Area gudang berhasil dihapus")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Tidak dapat menghapus area gudang yang memiliki data terkait"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area gudang tidak ditemukan"
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
    public function destroy(AreaGudang $areaGudang)
    {
        $this->authorize('area-gudang.delete');

        $this->areaGudangService->deleteAreaGudang($areaGudang);

        return $this->successResponse('Area gudang berhasil dihapus');
    }

    /**
     * @OA\Get(
     *     path="/api/gudang/{gudang_id}/area-gudang",
     *     summary="Mendapatkan area gudang berdasarkan gudang",
     *     description="Mengambil semua area gudang dalam gudang tertentu",
     *     operationId="getAreaGudangByGudang",
     *     tags={"Area Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="gudang_id",
     *         in="path",
     *         description="ID Gudang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="jenis_area",
     *         in="query",
     *         description="Filter berdasarkan jenis area",
     *         required=false,
     *         @OA\Schema(type="string", enum={"rak","lantai","khusus"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Area gudang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Area gudang berhasil diambil"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AreaGudang"))
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
    public function byGudang(Gudang $gudang, Request $request)
    {
        $this->authorize('area-gudang.view');

        $areas = $this->areaGudangService->getAreaByGudang($gudang, $request->all());

        return $this->successResponse(
            'Area gudang berhasil diambil',
            AreaGudangResource::collection($areas)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/area-gudang/stats",
     *     summary="Mendapatkan statistik area gudang",
     *     description="Mengambil statistik dan ringkasan data area gudang",
     *     operationId="getAreaGudangStats",
     *     tags={"Area Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="gudang_id",
     *         in="query",
     *         description="Filter statistik berdasarkan gudang tertentu",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statistik area gudang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistik area gudang berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_area", type="integer", example=25),
     *                 @OA\Property(property="area_tersedia", type="integer", example=20),
     *                 @OA\Property(property="area_tidak_tersedia", type="integer", example=5),
     *                 @OA\Property(property="total_kapasitas", type="number", format="float", example=2500.00),
     *                 @OA\Property(property="kapasitas_terpakai", type="number", format="float", example=1800.50),
     *                 @OA\Property(property="persentase_penggunaan", type="number", format="float", example=72.02),
     *                 @OA\Property(
     *                     property="by_jenis",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="jenis", type="string"),
     *                         @OA\Property(property="count", type="integer")
     *                     )
     *                 )
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
    public function stats(Request $request)
    {
        $this->authorize('area-gudang.view');

        $stats = $this->areaGudangService->getAreaGudangStats($request->all());

        return $this->successResponse(
            'Statistik area gudang berhasil diambil',
            $stats
        );
    }

    /**
     * @OA\Patch(
     *     path="/api/area-gudang/{id}/toggle-status",
     *     summary="Toggle status tersedia area gudang",
     *     description="Mengubah status tersedia/tidak tersedia area gudang",
     *     operationId="toggleAreaGudangStatus",
     *     tags={"Area Gudang"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID Area Gudang",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status area gudang berhasil diubah",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Status area gudang berhasil diubah"),
     *             @OA\Property(property="data", ref="#/components/schemas/AreaGudang")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area gudang tidak ditemukan"
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
    public function toggleStatus(AreaGudang $areaGudang)
    {
        $this->authorize('area-gudang.update');

        $updatedAreaGudang = $this->areaGudangService->toggleAreaGudangStatus($areaGudang);

        return $this->successResponse(
            'Status area gudang berhasil diubah',
            new AreaGudangResource($updatedAreaGudang)
        );
    }
}