<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\AreaGudang;
use App\Models\PenempatanBarang;
use App\Models\LogOptimasi;
use App\Models\LogAktivitas;
use OpenApi\Annotations as OA;

class AnalyticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/analytics/dashboard",
     *     summary="Dashboard Analytics",
     *     description="Mengambil data analytics dashboard dengan KPI utama sistem warehouse",
     *     tags={"Analytics"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Periode data (today, week, month, year)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"today", "week", "month", "year"}, example="month")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data dashboard berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data dashboard berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_gudang", type="integer", example=5),
     *                 @OA\Property(property="total_area", type="integer", example=25),
     *                 @OA\Property(property="total_barang", type="integer", example=150),
     *                 @OA\Property(property="total_penempatan", type="integer", example=120),
     *                 @OA\Property(property="kapasitas_terpakai", type="number", format="float", example=75.5),
     *                 @OA\Property(property="total_optimasi", type="integer", example=10),
     *                 @OA\Property(property="optimasi_sukses", type="integer", example=8),
     *                 @OA\Property(property="tingkat_keberhasilan", type="number", format="float", example=80.0),
     *                 @OA\Property(
     *                     property="aktivitas_terakhir",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="aksi", type="string", example="create"),
     *                         @OA\Property(property="deskripsi", type="string", example="Menambahkan barang baru"),
     *                         @OA\Property(property="user", type="string", example="Admin User"),
     *                         @OA\Property(property="waktu", type="string", example="2 jam yang lalu")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="trend_bulanan",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="bulan", type="string", example="2024-01"),
     *                         @OA\Property(property="total_barang", type="integer", example=145),
     *                         @OA\Property(property="total_optimasi", type="integer", example=3)
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
     *         response=500,
     *         description="Server Error"
     *     )
     * )
     * 
     * Dashboard summary: general KPIs
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $totalBarang = Barang::count();
            $totalGudang = Gudang::count();
            $totalArea = AreaGudang::count();
            $totalPenempatan = PenempatanBarang::count();

            $placementsToday = PenempatanBarang::whereDate('created_at', Carbon::today())->count();

            $expiredCount = PenempatanBarang::whereNotNull('tanggal_kadaluarsa')
                ->where('tanggal_kadaluarsa', '<', now())
                ->count();

            // Average utilization across warehouses (guard against zero total)
            $kapTerpakai = Gudang::sum('kapasitas_terpakai');
            $kapTotal = Gudang::sum('total_kapasitas');
            $avgUtilization = $kapTotal > 0 ? round(($kapTerpakai / $kapTotal) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'message' => 'Dashboard analytics summary',
                'data' => [
                    'total_barang' => $totalBarang,
                    'total_gudang' => $totalGudang,
                    'total_area' => $totalArea,
                    'total_penempatan' => $totalPenempatan,
                    'placements_today' => $placementsToday,
                    'expired_placements' => $expiredCount,
                    'average_utilization_percent' => $avgUtilization,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dashboard',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/analytics/utilization",
     *     summary="Utilization Analytics",
     *     description="Mengambil data utilisasi gudang dan area dengan persentase kapasitas terpakai",
     *     tags={"Analytics"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="gudang_id",
     *         in="query",
     *         description="ID gudang untuk filter data",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per",
     *         in="query",
     *         description="Grup data berdasarkan gudang atau area",
     *         required=false,
     *         @OA\Schema(type="string", enum={"gudang", "area"}, example="gudang")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data utilisasi berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data utilisasi berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="rata_rata_utilisasi", type="number", format="float", example=75.5),
     *                 @OA\Property(property="total_kapasitas", type="number", format="float", example=1000.0),
     *                 @OA\Property(property="kapasitas_terpakai", type="number", format="float", example=755.0),
     *                 @OA\Property(
     *                     property="detail_utilisasi",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="nama", type="string", example="Gudang Utama"),
     *                         @OA\Property(property="kapasitas_maksimal", type="number", format="float", example=500.0),
     *                         @OA\Property(property="kapasitas_terpakai", type="number", format="float", example=375.0),
     *                         @OA\Property(property="persentase_utilisasi", type="number", format="float", example=75.0),
     *                         @OA\Property(property="jumlah_barang", type="integer", example=45),
     *                         @OA\Property(property="status", type="string", example="Normal")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="statistik",
     *                     type="object",
     *                     @OA\Property(property="utilisasi_tertinggi", type="number", format="float", example=95.0),
     *                     @OA\Property(property="utilisasi_terendah", type="number", format="float", example=45.0),
     *                     @OA\Property(property="total_area_penuh", type="integer", example=3),
     *                     @OA\Property(property="total_area_kosong", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error"
     *     )
     * )
     * 
     * Utilization per warehouse or per area
     */
    public function utilization(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'gudang_id' => 'nullable|integer|exists:gudang,id',
                'per' => 'nullable|string|in:gudang,area',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $gudangId = $request->input('gudang_id');
            $per = $request->input('per', 'gudang');

            if ($per === 'area' || $gudangId) {
                // Return per-area utilization, optionally filtered by gudang
                $areas = AreaGudang::when($gudangId, function ($q) use ($gudangId) {
                    $q->where('gudang_id', $gudangId);
                })->get();

                $result = $areas->map(function ($area) {
                    return [
                        'id' => $area->id,
                        'kode_area' => $area->kode_area,
                        'nama_area' => $area->nama_area,
                        'kapasitas' => (float) $area->kapasitas,
                        'kapasitas_terpakai' => (float) $area->kapasitas_terpakai,
                        'utilization_percent' => (float) $area->persentase_kapasitas,
                        'tersedia' => (bool) $area->tersedia,
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Utilization per area',
                    'data' => $result
                ]);
            }

            // Default: per-gudang utilization
            $gudangs = Gudang::all();
            $result = $gudangs->map(function ($g) {
                return [
                    'id' => $g->id,
                    'nama_gudang' => $g->nama_gudang,
                    'total_kapasitas' => (float) $g->total_kapasitas,
                    'kapasitas_terpakai' => (float) $g->kapasitas_terpakai,
                    'utilization_percent' => (float) $g->persentase_kapasitas,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Utilization per gudang',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data utilization',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/analytics/performance",
     *     summary="Performance Analytics", 
     *     description="Mengambil data performa sistem termasuk throughput, rata-rata item per penempatan, dan statistik optimasi",
     *     tags={"Analytics"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Tanggal mulai filter data (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Tanggal akhir filter data (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="group",
     *         in="query",
     *         description="Grup data berdasarkan periode",
     *         required=false,
     *         @OA\Schema(type="string", enum={"day", "week", "month"}, example="month")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data performa berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data performa berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="rata_rata_penempatan_per_hari", type="number", format="float", example=12.5),
     *                 @OA\Property(property="rata_rata_item_per_penempatan", type="number", format="float", example=8.3),
     *                 @OA\Property(property="total_optimasi", type="integer", example=25),
     *                 @OA\Property(property="optimasi_berhasil", type="integer", example=22),
     *                 @OA\Property(property="tingkat_keberhasilan_optimasi", type="number", format="float", example=88.0),
     *                 @OA\Property(property="rata_rata_waktu_optimasi", type="number", format="float", example=45.5),
     *                 @OA\Property(
     *                     property="trend_performa",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="periode", type="string", example="2024-01"),
     *                         @OA\Property(property="total_penempatan", type="integer", example=120),
     *                         @OA\Property(property="total_optimasi", type="integer", example=5),
     *                         @OA\Property(property="tingkat_keberhasilan", type="number", format="float", example=80.0)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="top_pengguna",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="nama", type="string", example="Admin User"),
     *                         @OA\Property(property="total_aktivitas", type="integer", example=150),
     *                         @OA\Property(property="penempatan_dibuat", type="integer", example=50)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="efisiensi_optimasi",
     *                     type="object",
     *                     @OA\Property(property="waktu_tercepat", type="number", format="float", example=15.2),
     *                     @OA\Property(property="waktu_terlama", type="number", format="float", example=120.5),
     *                     @OA\Property(property="total_penghematan_ruang", type="number", format="float", example=2500.0)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error"
     *     )
     * )
     * 
     * Performance metrics: throughput, average items per placement, optimization stats
     */
    public function performance(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'group' => 'nullable|string|in:day,week,month',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $start = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
            $end = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();
            $group = $request->input('group', 'day');

            // Throughput: placements count grouped
            $placementsQuery = PenempatanBarang::whereBetween('created_at', [$start, $end]);

            $placements = $placementsQuery->select(DB::raw($this->groupByDateSql('created_at', $group) . ' as period'), DB::raw('COUNT(*) as count'), DB::raw('AVG(jumlah) as avg_items'))
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            // Optimization stats
            $optQuery = LogOptimasi::whereBetween('created_at', [$start, $end]);
            $totalOptim = $optQuery->count();
            $suksesOptim = $optQuery->where('status', 'selesai')->count();
            $avgExec = $optQuery->whereNotNull('waktu_eksekusi')->avg('waktu_eksekusi') ?: 0;
            $avgImprovement = $optQuery->avg('persentase_perbaikan') ?: 0;

            // Top users by activity
            $topUsers = LogAktivitas::whereBetween('created_at', [$start, $end])
                ->select('user_id', DB::raw('COUNT(*) as jumlah'))
                ->groupBy('user_id')
                ->orderByDesc('jumlah')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Performance metrics',
                'data' => [
                    'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'group' => $group],
                    'placements_over_time' => $placements,
                    'optimization' => [
                        'total_runs' => $totalOptim,
                        'succeeded' => $suksesOptim,
                        'avg_execution_seconds' => (float) $avgExec,
                        'avg_percent_improvement' => (float) $avgImprovement,
                    ],
                    'top_users' => $topUsers,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data performance',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Helper to return correct SQL grouping expression for date grouping
     */
    private function groupByDateSql(string $column, string $group): string
    {
        switch ($group) {
            case 'week':
                return "DATE_FORMAT($column, '%x-%v')"; // year-week
            case 'month':
                return "DATE_FORMAT($column, '%Y-%m')";
            case 'day':
            default:
                return "DATE_FORMAT($column, '%Y-%m-%d')";
        }
    }
}
