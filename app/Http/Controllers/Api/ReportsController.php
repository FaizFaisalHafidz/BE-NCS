<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\LogOptimasi;
use App\Models\Gudang;
use App\Models\AreaGudang;
use App\Models\PenempatanBarang;
use App\Models\Barang;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="DailyReport",
 *     type="object",
 *     title="Daily Report",
 *     description="Laporan harian sistem",
 *     @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(property="total_activities", type="integer", example=125),
 *     @OA\Property(property="total_optimizations", type="integer", example=3),
 *     @OA\Property(property="total_items_moved", type="integer", example=45),
 *     @OA\Property(property="warehouse_utilization", type="number", format="float", example=85.5),
 *     @OA\Property(property="top_users", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="most_active_warehouse", type="object")
 * )
 * 
 * @OA\Schema(
 *     schema="WeeklyReport",
 *     type="object",
 *     title="Weekly Report",
 *     description="Laporan mingguan sistem",
 *     @OA\Property(property="week_start", type="string", format="date"),
 *     @OA\Property(property="week_end", type="string", format="date"),
 *     @OA\Property(property="total_activities", type="integer"),
 *     @OA\Property(property="activities_by_day", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="optimization_performance", type="object"),
 *     @OA\Property(property="team_performance", type="array", @OA\Items(type="object"))
 * )
 * 
 * @OA\Schema(
 *     schema="InventoryReport",
 *     type="object",
 *     title="Inventory Report",
 *     description="Laporan inventory gudang",
 *     @OA\Property(property="total_items", type="integer", example=1250),
 *     @OA\Property(property="items_by_category", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="items_by_warehouse", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="low_stock_items", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="expired_items", type="array", @OA\Items(type="object"))
 * )
 */

class ReportsController extends Controller
{
    use LogsActivity;

    /**
     * @OA\Get(
     *     path="/reports/daily",
     *     summary="Daily Report",
     *     description="Mengambil laporan harian aktivitas sistem warehouse",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Tanggal laporan (format: Y-m-d, default: hari ini)",
     *         @OA\Schema(type="string", format="date", example="2024-01-15")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laporan harian berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Laporan harian berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/DailyReport")
     *         )
     *     )
     * )
     * 
     * Get daily report
     */
    public function dailyReport(Request $request): JsonResponse
    {
        try {
            $date = $request->input('date', Carbon::today()->format('Y-m-d'));
            $targetDate = Carbon::parse($date);

            // Total activities for the day
            $totalActivities = LogAktivitas::whereDate('created_at', $targetDate)->count();

            // Total optimizations for the day
            $totalOptimizations = LogOptimasi::whereDate('created_at', $targetDate)->count();

            // Total items moved (based on penempatan barang)
            $totalItemsMoved = PenempatanBarang::whereDate('created_at', $targetDate)->count();

            // Warehouse utilization
            $warehouseUtilization = $this->calculateWarehouseUtilization();

            // Top users activity for the day
            $topUsers = LogAktivitas::whereDate('created_at', $targetDate)
                ->select('user_id', DB::raw('count(*) as total'))
                ->with('user:id,nama,email')
                ->groupBy('user_id')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->user_id,
                        'name' => $item->user ? $item->user->nama : 'Unknown',
                        'email' => $item->user ? $item->user->email : '',
                        'total_activities' => $item->total
                    ];
                });

            // Most active warehouse
            $mostActiveWarehouse = PenempatanBarang::whereDate('created_at', $targetDate)
                ->join('area_gudang', 'penempatan_barang.area_gudang_id', '=', 'area_gudang.id')
                ->join('gudang', 'area_gudang.gudang_id', '=', 'gudang.id')
                ->select('gudang.id', 'gudang.nama_gudang', DB::raw('count(*) as total_placements'))
                ->groupBy('gudang.id', 'gudang.nama_gudang')
                ->orderBy('total_placements', 'desc')
                ->first();

            // Log report access
            $this->logCustom(
                'view_report',
                "Mengakses laporan harian untuk tanggal {$date}",
                null,
                ['report_type' => 'daily', 'date' => $date]
            );

            return response()->json([
                'success' => true,
                'message' => 'Laporan harian berhasil diambil',
                'data' => [
                    'date' => $date,
                    'total_activities' => $totalActivities,
                    'total_optimizations' => $totalOptimizations,
                    'total_items_moved' => $totalItemsMoved,
                    'warehouse_utilization' => round($warehouseUtilization, 2),
                    'top_users' => $topUsers,
                    'most_active_warehouse' => $mostActiveWarehouse ? [
                        'id' => $mostActiveWarehouse->id,
                        'name' => $mostActiveWarehouse->nama_gudang,
                        'total_placements' => $mostActiveWarehouse->total_placements
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil laporan harian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/reports/weekly",
     *     summary="Weekly Report",
     *     description="Mengambil laporan mingguan aktivitas sistem warehouse",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="week_start",
     *         in="query",
     *         description="Tanggal awal minggu (format: Y-m-d, default: awal minggu ini)",
     *         @OA\Schema(type="string", format="date", example="2024-01-08")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laporan mingguan berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Laporan mingguan berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/WeeklyReport")
     *         )
     *     )
     * )
     * 
     * Get weekly report
     */
    public function weeklyReport(Request $request): JsonResponse
    {
        try {
            $weekStart = $request->input('week_start', Carbon::now()->startOfWeek()->format('Y-m-d'));
            $startDate = Carbon::parse($weekStart);
            $endDate = $startDate->copy()->endOfWeek();

            // Total activities for the week
            $totalActivities = LogAktivitas::whereBetween('created_at', [$startDate, $endDate])->count();

            // Activities by day
            $activitiesByDay = LogAktivitas::whereBetween('created_at', [$startDate, $endDate])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'day_name' => Carbon::parse($item->date)->format('l'),
                        'total_activities' => $item->total
                    ];
                });

            // Optimization performance
            $optimizations = LogOptimasi::whereBetween('created_at', [$startDate, $endDate])
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();

            $optimizationPerformance = [
                'total' => $optimizations->sum('total'),
                'by_status' => $optimizations->map(function ($item) {
                    return [
                        'status' => $item->status,
                        'total' => $item->total
                    ];
                })
            ];

            // Team performance
            $teamPerformance = LogAktivitas::whereBetween('created_at', [$startDate, $endDate])
                ->select('user_id', DB::raw('count(*) as total_activities'))
                ->with('user:id,nama,email')
                ->groupBy('user_id')
                ->orderBy('total_activities', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->user_id,
                        'name' => $item->user ? $item->user->nama : 'Unknown',
                        'email' => $item->user ? $item->user->email : '',
                        'total_activities' => $item->total_activities,
                        'avg_per_day' => round($item->total_activities / 7, 1)
                    ];
                });

            // Log report access
            $this->logCustom(
                'view_report',
                "Mengakses laporan mingguan untuk periode {$weekStart} - {$endDate->format('Y-m-d')}",
                null,
                ['report_type' => 'weekly', 'week_start' => $weekStart, 'week_end' => $endDate->format('Y-m-d')]
            );

            return response()->json([
                'success' => true,
                'message' => 'Laporan mingguan berhasil diambil',
                'data' => [
                    'week_start' => $weekStart,
                    'week_end' => $endDate->format('Y-m-d'),
                    'total_activities' => $totalActivities,
                    'activities_by_day' => $activitiesByDay,
                    'optimization_performance' => $optimizationPerformance,
                    'team_performance' => $teamPerformance
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil laporan mingguan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/reports/inventory",
     *     summary="Inventory Report",
     *     description="Mengambil laporan inventory gudang dengan detail stock dan kategori",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="query",
     *         description="Filter berdasarkan ID gudang tertentu",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laporan inventory berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Laporan inventory berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/InventoryReport")
     *         )
     *     )
     * )
     * 
     * Get inventory report
     */
    public function inventoryReport(Request $request): JsonResponse
    {
        try {
            $warehouseId = $request->input('warehouse_id');

            // Base query for penempatan barang
            $query = PenempatanBarang::with(['barang.kategori', 'areaGudang.gudang']);

            if ($warehouseId) {
                $query->whereHas('areaGudang', function ($q) use ($warehouseId) {
                    $q->where('gudang_id', $warehouseId);
                });
            }

            $placements = $query->get();

            // Total items
            $totalItems = $placements->sum('jumlah');

            // Items by category
            $itemsByCategory = $placements->groupBy('barang.kategori.nama_kategori')
                ->map(function ($items, $category) {
                    return [
                        'category' => $category ?: 'Tidak Berkategori',
                        'total_items' => $items->sum('jumlah'),
                        'unique_products' => $items->count()
                    ];
                })->values();

            // Items by warehouse
            $itemsByWarehouse = $placements->groupBy('areaGudang.gudang.nama_gudang')
                ->map(function ($items, $warehouse) {
                    return [
                        'warehouse' => $warehouse,
                        'total_items' => $items->sum('jumlah'),
                        'utilization' => $this->calculateWarehouseUtilizationById($items->first()->areaGudang->gudang_id ?? null)
                    ];
                })->values();

            // Low stock items (jumlah < 10)
            $lowStockItems = $placements->filter(function ($item) {
                return $item->jumlah < 10;
            })->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_code' => $item->barang->kode_barang ?? '',
                    'product_name' => $item->barang->nama_barang ?? '',
                    'current_stock' => $item->jumlah,
                    'warehouse' => $item->areaGudang->gudang->nama_gudang ?? '',
                    'area' => $item->areaGudang->nama_area ?? ''
                ];
            })->values();

            // Expired items (if tanggal_kadaluarsa exists and has passed)
            $expiredItems = $placements->filter(function ($item) {
                return $item->tanggal_kadaluarsa && Carbon::parse($item->tanggal_kadaluarsa)->isPast();
            })->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_code' => $item->barang->kode_barang ?? '',
                    'product_name' => $item->barang->nama_barang ?? '',
                    'jumlah' => $item->jumlah,
                    'expired_date' => $item->tanggal_kadaluarsa,
                    'days_expired' => Carbon::parse($item->tanggal_kadaluarsa)->diffInDays(Carbon::now()),
                    'warehouse' => $item->areaGudang->gudang->nama_gudang ?? '',
                    'area' => $item->areaGudang->nama_area ?? ''
                ];
            })->values();

            // Log report access
            $this->logCustom(
                'view_report',
                'Mengakses laporan inventory',
                null,
                ['report_type' => 'inventory', 'warehouse_id' => $warehouseId]
            );

            return response()->json([
                'success' => true,
                'message' => 'Laporan inventory berhasil diambil',
                'data' => [
                    'total_items' => $totalItems,
                    'total_products' => $placements->count(),
                    'items_by_category' => $itemsByCategory,
                    'items_by_warehouse' => $itemsByWarehouse,
                    'low_stock_items' => $lowStockItems,
                    'expired_items' => $expiredItems,
                    'generated_at' => Carbon::now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil laporan inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/reports/team-performance",
     *     summary="Team Performance Report",
     *     description="Mengambil laporan performa tim berdasarkan aktivitas dan produktivitas",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Periode laporan (week, month, quarter, year)",
     *         @OA\Schema(type="string", enum={"week", "month", "quarter", "year"}, example="month")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Tanggal mulai custom period (format: Y-m-d)",
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Tanggal akhir custom period (format: Y-m-d)",
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laporan performa tim berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Laporan performa tim berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="period", type="string"),
     *                 @OA\Property(property="team_members", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="top_performers", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="activity_trends", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     * 
     * Get team performance report
     */
    public function teamPerformance(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'month');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Determine date range based on period
            if ($startDate && $endDate) {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);
                $periodLabel = "Custom ({$startDate} - {$endDate})";
            } else {
                switch ($period) {
                    case 'week':
                        $start = Carbon::now()->startOfWeek();
                        $end = Carbon::now()->endOfWeek();
                        $periodLabel = 'This Week';
                        break;
                    case 'quarter':
                        $start = Carbon::now()->startOfQuarter();
                        $end = Carbon::now()->endOfQuarter();
                        $periodLabel = 'This Quarter';
                        break;
                    case 'year':
                        $start = Carbon::now()->startOfYear();
                        $end = Carbon::now()->endOfYear();
                        $periodLabel = 'This Year';
                        break;
                    default: // month
                        $start = Carbon::now()->startOfMonth();
                        $end = Carbon::now()->endOfMonth();
                        $periodLabel = 'This Month';
                        break;
                }
            }

            // Team members performance
            $teamMembers = User::with(['roles'])
                ->whereHas('logAktivitas', function ($q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start, $end]);
                })
                ->get()
                ->map(function ($user) use ($start, $end) {
                    $activities = LogAktivitas::where('user_id', $user->id)
                        ->whereBetween('created_at', [$start, $end])
                        ->get();

                    return [
                        'user_id' => $user->id,
                        'name' => $user->nama,
                        'email' => $user->email,
                        'role' => $user->roles->first()->name ?? 'No Role',
                        'total_activities' => $activities->count(),
                        'activities_by_type' => $activities->groupBy('aksi')->map->count(),
                        'last_activity' => $activities->max('created_at'),
                        'productivity_score' => $this->calculateProductivityScore($activities)
                    ];
                });

            // Top performers
            $topPerformers = $teamMembers->sortByDesc('productivity_score')->take(5)->values();

            // Activity trends (daily breakdown)
            $activityTrends = LogAktivitas::whereBetween('created_at', [$start, $end])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'total_activities' => $item->total
                    ];
                });

            // Log report access
            $this->logCustom(
                'view_report',
                "Mengakses laporan performa tim untuk periode {$periodLabel}",
                null,
                ['report_type' => 'team_performance', 'period' => $period]
            );

            return response()->json([
                'success' => true,
                'message' => 'Laporan performa tim berhasil diambil',
                'data' => [
                    'period' => $periodLabel,
                    'date_range' => [
                        'start' => $start->format('Y-m-d'),
                        'end' => $end->format('Y-m-d')
                    ],
                    'team_members' => $teamMembers,
                    'top_performers' => $topPerformers,
                    'activity_trends' => $activityTrends,
                    'summary' => [
                        'total_team_members' => $teamMembers->count(),
                        'total_activities' => $teamMembers->sum('total_activities'),
                        'avg_activities_per_member' => $teamMembers->avg('total_activities')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil laporan performa tim',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/reports/warehouse-capacity",
     *     summary="Warehouse Capacity Report",
     *     description="Mengambil laporan kapasitas gudang dan utilisasi ruang",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Laporan kapasitas gudang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Laporan kapasitas gudang berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="warehouses", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="overall_utilization", type="number"),
     *                 @OA\Property(property="capacity_alerts", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     * 
     * Get warehouse capacity report
     */
    public function warehouseCapacity(Request $request): JsonResponse
    {
        try {
            $warehouses = Gudang::with(['areaGudang.penempatanBarang'])
                ->where('aktif', true)
                ->get()
                ->map(function ($warehouse) {
                    $totalAreas = $warehouse->areaGudang->count();
                    $occupiedAreas = $warehouse->areaGudang->filter(function ($area) {
                        return $area->penempatanBarang->count() > 0;
                    })->count();
                    
                    $totalCapacity = $warehouse->areaGudang->sum('kapasitas');
                    $currentStock = $warehouse->areaGudang->sum(function ($area) {
                        return $area->penempatanBarang->sum('jumlah');
                    });

                    $utilization = $totalCapacity > 0 ? ($currentStock / $totalCapacity) * 100 : 0;

                    return [
                        'id' => $warehouse->id,
                        'name' => $warehouse->nama_gudang,
                        'address' => $warehouse->alamat,
                        'total_areas' => $totalAreas,
                        'occupied_areas' => $occupiedAreas,
                        'area_utilization' => $totalAreas > 0 ? ($occupiedAreas / $totalAreas) * 100 : 0,
                        'total_capacity' => $totalCapacity,
                        'current_stock' => $currentStock,
                        'capacity_utilization' => round($utilization, 2),
                        'available_capacity' => $totalCapacity - $currentStock,
                        'status' => $this->getCapacityStatus($utilization)
                    ];
                });

            // Overall utilization
            $totalCapacity = $warehouses->sum('total_capacity');
            $totalStock = $warehouses->sum('current_stock');
            $overallUtilization = $totalCapacity > 0 ? ($totalStock / $totalCapacity) * 100 : 0;

            // Capacity alerts (high utilization > 90% or low utilization < 20%)
            $capacityAlerts = $warehouses->filter(function ($warehouse) {
                return $warehouse['capacity_utilization'] > 90 || $warehouse['capacity_utilization'] < 20;
            })->map(function ($warehouse) {
                $type = $warehouse['capacity_utilization'] > 90 ? 'high' : 'low';
                $message = $type === 'high' 
                    ? "Gudang {$warehouse['name']} hampir penuh ({$warehouse['capacity_utilization']}%)"
                    : "Gudang {$warehouse['name']} utilisasi rendah ({$warehouse['capacity_utilization']}%)";

                return [
                    'warehouse_id' => $warehouse['id'],
                    'warehouse_name' => $warehouse['name'],
                    'type' => $type,
                    'utilization' => $warehouse['capacity_utilization'],
                    'message' => $message
                ];
            })->values();

            // Log report access
            $this->logCustom(
                'view_report',
                'Mengakses laporan kapasitas gudang',
                null,
                ['report_type' => 'warehouse_capacity']
            );

            return response()->json([
                'success' => true,
                'message' => 'Laporan kapasitas gudang berhasil diambil',
                'data' => [
                    'warehouses' => $warehouses,
                    'overall_utilization' => round($overallUtilization, 2),
                    'capacity_alerts' => $capacityAlerts,
                    'summary' => [
                        'total_warehouses' => $warehouses->count(),
                        'total_capacity' => $totalCapacity,
                        'total_stock' => $totalStock,
                        'available_capacity' => $totalCapacity - $totalStock
                    ],
                    'generated_at' => Carbon::now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil laporan kapasitas gudang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/reports/optimization",
     *     summary="Optimization Report",
     *     description="Mengambil laporan optimasi sistem warehouse dengan algoritma Simulated Annealing",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Periode laporan (week, month, quarter)",
     *         @OA\Schema(type="string", enum={"week", "month", "quarter"}, example="month")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laporan optimasi berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Laporan optimasi berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="optimizations", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="performance_metrics", type="object"),
     *                 @OA\Property(property="algorithm_effectiveness", type="object")
     *             )
     *         )
     *     )
     * )
     * 
     * Get optimization report
     */
    public function optimizationReport(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'month');
            
            // Determine date range
            switch ($period) {
                case 'week':
                    $start = Carbon::now()->startOfWeek();
                    $end = Carbon::now()->endOfWeek();
                    break;
                case 'quarter':
                    $start = Carbon::now()->startOfQuarter();
                    $end = Carbon::now()->endOfQuarter();
                    break;
                default: // month
                    $start = Carbon::now()->startOfMonth();
                    $end = Carbon::now()->endOfMonth();
                    break;
            }

            // Get optimizations in period
            $optimizations = LogOptimasi::whereBetween('created_at', [$start, $end])
                ->with('user:id,nama,email')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($opt) {
                    return [
                        'id' => $opt->id,
                        'user' => $opt->user ? $opt->user->nama : 'System',
                        'algorithm' => $opt->algoritma,
                        'status' => $opt->status,
                        'execution_time' => $opt->waktu_eksekusi,
                        'before_score' => $opt->skor_sebelum,
                        'after_score' => $opt->skor_sesudah,
                        'improvement' => $opt->skor_sesudah - $opt->skor_sebelum,
                        'improvement_percentage' => $opt->skor_sebelum > 0 
                            ? round((($opt->skor_sesudah - $opt->skor_sebelum) / $opt->skor_sebelum) * 100, 2)
                            : 0,
                        'created_at' => $opt->created_at->format('Y-m-d H:i:s')
                    ];
                });

            // Performance metrics
            $performanceMetrics = [
                'total_optimizations' => $optimizations->count(),
                'successful_optimizations' => $optimizations->where('status', 'completed')->count(),
                'failed_optimizations' => $optimizations->where('status', 'failed')->count(),
                'avg_execution_time' => $optimizations->avg('execution_time'),
                'avg_improvement' => $optimizations->avg('improvement'),
                'total_improvement' => $optimizations->sum('improvement'),
                'best_improvement' => $optimizations->max('improvement'),
                'worst_improvement' => $optimizations->min('improvement')
            ];

            // Algorithm effectiveness
            $algorithmStats = $optimizations->groupBy('algorithm')->map(function ($items, $algorithm) {
                return [
                    'algorithm' => $algorithm,
                    'total_runs' => $items->count(),
                    'success_rate' => $items->count() > 0 
                        ? ($items->where('status', 'completed')->count() / $items->count()) * 100 
                        : 0,
                    'avg_improvement' => $items->avg('improvement'),
                    'avg_execution_time' => $items->avg('execution_time')
                ];
            })->values();

            // Optimization trends
            $trends = $optimizations->groupBy(function ($item) {
                return Carbon::parse($item['created_at'])->format('Y-m-d');
            })->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'total_optimizations' => $items->count(),
                    'avg_improvement' => $items->avg('improvement')
                ];
            })->values();

            // Log report access
            $this->logCustom(
                'view_report',
                "Mengakses laporan optimasi untuk periode {$period}",
                null,
                ['report_type' => 'optimization', 'period' => $period]
            );

            return response()->json([
                'success' => true,
                'message' => 'Laporan optimasi berhasil diambil',
                'data' => [
                    'period' => ucfirst($period),
                    'date_range' => [
                        'start' => $start->format('Y-m-d'),
                        'end' => $end->format('Y-m-d')
                    ],
                    'optimizations' => $optimizations,
                    'performance_metrics' => $performanceMetrics,
                    'algorithm_effectiveness' => $algorithmStats,
                    'trends' => $trends,
                    'generated_at' => Carbon::now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil laporan optimasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/reports/latest",
     *     summary="Latest Reports",
     *     description="Mengambil laporan terbaru dari berbagai kategori",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Jumlah laporan per kategori (default: 5)",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laporan terbaru berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Laporan terbaru berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="latest_activities", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="latest_optimizations", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="recent_placements", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="alerts", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     * 
     * Get latest reports summary
     */
    public function latestReports(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 5);

            // Latest activities
            $latestActivities = LogAktivitas::with('user:id,nama,email')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'user' => $activity->user ? $activity->user->nama : 'Unknown',
                        'action' => $activity->aksi_formatted,
                        'description' => $activity->deskripsi,
                        'time_ago' => $activity->time_ago,
                        'created_at' => $activity->created_at->format('Y-m-d H:i:s')
                    ];
                });

            // Latest optimizations
            $latestOptimizations = LogOptimasi::with('user:id,nama,email')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($opt) {
                    return [
                        'id' => $opt->id,
                        'user' => $opt->user ? $opt->user->nama : 'System',
                        'algorithm' => $opt->algoritma,
                        'status' => $opt->status,
                        'improvement' => $opt->skor_sesudah - $opt->skor_sebelum,
                        'execution_time' => $opt->waktu_eksekusi,
                        'created_at' => $opt->created_at->format('Y-m-d H:i:s')
                    ];
                });

            // Recent placements
            $recentPlacements = PenempatanBarang::with(['barang:id,kode_barang,nama_barang', 'areaGudang.gudang:id,nama_gudang'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($placement) {
                    return [
                        'id' => $placement->id,
                        'product_code' => $placement->barang->kode_barang ?? '',
                        'product_name' => $placement->barang->nama_barang ?? '',
                        'jumlah' => $placement->jumlah,
                        'warehouse' => $placement->areaGudang->gudang->nama_gudang ?? '',
                        'area' => $placement->areaGudang->nama_area ?? '',
                        'created_at' => $placement->created_at->format('Y-m-d H:i:s')
                    ];
                });

            // System alerts
            $alerts = [];
            
            // Check for high capacity warehouses
            $highCapacityWarehouses = Gudang::with('areaGudang.penempatanBarang')
                ->where('aktif', true)
                ->get()
                ->filter(function ($warehouse) {
                    $utilization = $this->calculateWarehouseUtilizationById($warehouse->id);
                    return $utilization > 90;
                });

            foreach ($highCapacityWarehouses as $warehouse) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Kapasitas Gudang Tinggi',
                    'message' => "Gudang {$warehouse->nama_gudang} hampir penuh",
                    'data' => ['warehouse_id' => $warehouse->id]
                ];
            }

            // Check for expired items
            $expiredCount = PenempatanBarang::where('tanggal_kadaluarsa', '<', Carbon::now())->count();
            if ($expiredCount > 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'title' => 'Barang Kadaluarsa',
                    'message' => "Terdapat {$expiredCount} barang yang sudah kadaluarsa",
                    'data' => ['expired_count' => $expiredCount]
                ];
            }

            // Check for recent failed optimizations
            $failedOptimizations = LogOptimasi::where('status', 'failed')
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->count();
            
            if ($failedOptimizations > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Optimasi Gagal',
                    'message' => "Terdapat {$failedOptimizations} optimasi yang gagal dalam 7 hari terakhir",
                    'data' => ['failed_count' => $failedOptimizations]
                ];
            }

            // Log report access
            $this->logCustom(
                'view_report',
                'Mengakses laporan terbaru',
                null,
                ['report_type' => 'latest', 'limit' => $limit]
            );

            return response()->json([
                'success' => true,
                'message' => 'Laporan terbaru berhasil diambil',
                'data' => [
                    'latest_activities' => $latestActivities,
                    'latest_optimizations' => $latestOptimizations,
                    'recent_placements' => $recentPlacements,
                    'alerts' => $alerts,
                    'summary' => [
                        'total_alerts' => count($alerts),
                        'system_health' => count($alerts) === 0 ? 'good' : (count($alerts) <= 2 ? 'warning' : 'critical')
                    ],
                    'generated_at' => Carbon::now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil laporan terbaru',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to calculate warehouse utilization
     */
    private function calculateWarehouseUtilization(): float
    {
        $totalCapacity = AreaGudang::sum('kapasitas');
        $totalUsed = PenempatanBarang::sum('jumlah');
        
        return $totalCapacity > 0 ? ($totalUsed / $totalCapacity) * 100 : 0;
    }

    /**
     * Helper method to calculate warehouse utilization by ID
     */
    private function calculateWarehouseUtilizationById($warehouseId): float
    {
        if (!$warehouseId) return 0;

        $totalCapacity = AreaGudang::where('gudang_id', $warehouseId)->sum('kapasitas');
        $totalUsed = PenempatanBarang::whereHas('areaGudang', function ($q) use ($warehouseId) {
            $q->where('gudang_id', $warehouseId);
        })->sum('jumlah');
        
        return $totalCapacity > 0 ? ($totalUsed / $totalCapacity) * 100 : 0;
    }

    /**
     * Helper method to get capacity status
     */
    private function getCapacityStatus($utilization): string
    {
        if ($utilization >= 90) return 'critical';
        if ($utilization >= 75) return 'warning';
        if ($utilization >= 25) return 'good';
        return 'low';
    }

    /**
     * Helper method to calculate productivity score
     */
    private function calculateProductivityScore($activities): float
    {
        if ($activities->isEmpty()) return 0;

        // Simple scoring based on activity types and frequency
        $score = 0;
        $weights = [
            'create' => 3,
            'update' => 2,
            'delete' => 1,
            'optimization' => 5,
            'login' => 0.5
        ];

        foreach ($activities as $activity) {
            $weight = $weights[$activity->aksi] ?? 1;
            $score += $weight;
        }

        return round($score / $activities->count(), 2);
    }
}