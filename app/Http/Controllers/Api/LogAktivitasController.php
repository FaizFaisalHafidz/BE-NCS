<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="LogAktivitas",
 *     type="object",
 *     title="Log Aktivitas",
 *     description="Model log aktivitas sistem",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="aksi", type="string", example="create"),
 *     @OA\Property(property="deskripsi", type="string", example="Menambahkan barang baru"),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="model_type", type="string", example="App\\Models\\Barang"),
 *     @OA\Property(property="model_id", type="integer", example=15),
 *     @OA\Property(property="data_lama", type="object", nullable=true),
 *     @OA\Property(property="data_baru", type="object", nullable=true),
 *     @OA\Property(property="timestamp", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="formatted_date", type="string", example="15 Januari 2024, 10:30"),
 *     @OA\Property(property="time_ago", type="string", example="2 jam yang lalu")
 * )
 */

class LogAktivitasController extends Controller
{
    /**
     * @OA\Get(
     *     path="/log-aktivitas",
     *     summary="Get log aktivitas list",
     *     description="Mengambil daftar log aktivitas dengan filtering dan pagination",
     *     tags={"Log Aktivitas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Nomor halaman",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query", 
     *         description="Jumlah data per halaman (max: 100)",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Pencarian berdasarkan aksi atau deskripsi",
     *         @OA\Schema(type="string", example="create")
     *     ),
     *     @OA\Parameter(
     *         name="aksi",
     *         in="query",
     *         description="Filter berdasarkan jenis aksi",
     *         @OA\Schema(type="string", example="create")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter berdasarkan ID user", 
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Tanggal mulai (format: Y-m-d)",
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Tanggal akhir (format: Y-m-d)",
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Parameter(
     *         name="model_type",
     *         in="query",
     *         description="Filter berdasarkan tipe model",
     *         @OA\Schema(type="string", example="App\\Models\\Barang")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data log aktivitas berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data log aktivitas berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/LogAktivitas")),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string", nullable=true),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="prev_page_url", type="string", nullable=true),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token tidak valid")
     *         )
     *     )
     * )
     * 
     * Display a listing of log aktivitas.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = LogAktivitas::with(['user:id,nama,email'])
                ->orderBy('created_at', 'desc');

            // Filter by user
            if ($request->has('user_id') && $request->user_id) {
                $query->byUser($request->user_id);
            }

            // Filter by action
            if ($request->has('aksi') && $request->aksi) {
                $query->byAksi($request->aksi);
            }

            // Filter by model type
            if ($request->has('model_type') && $request->model_type) {
                $query->where('model_type', $request->model_type);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $query->where('created_at', '>=', $startDate);
            }

            if ($request->has('end_date') && $request->end_date) {
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $query->where('created_at', '<=', $endDate);
            }

            // Quick filters
            if ($request->has('filter')) {
                switch ($request->filter) {
                    case 'today':
                        $query->today();
                        break;
                    case 'week':
                        $query->thisWeek();
                        break;
                    case 'month':
                        $query->thisMonth();
                        break;
                }
            }

            // Search in description
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('deskripsi', 'like', "%{$search}%")
                      ->orWhere('aksi', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $logs = $query->paginate($perPage);

            // Transform data
            $logs->getCollection()->transform(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => [
                        'id' => $log->user ? $log->user->id : null,
                        'name' => $log->user ? $log->user->name : null,
                        'email' => $log->user ? $log->user->email : null,
                    ],
                    'aksi' => $log->aksi,
                    'aksi_formatted' => $log->aksi_formatted,
                    'deskripsi' => $log->deskripsi,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'data_lama' => $log->data_lama,
                    'data_baru' => $log->data_baru,
                    'waktu' => $log->waktu,
                    'created_at' => $log->created_at,
                    'updated_at' => $log->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Data log aktivitas berhasil diambil',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data log aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/log-aktivitas/{id}",
     *     summary="Get log aktivitas detail",
     *     description="Mengambil detail log aktivitas berdasarkan ID",
     *     tags={"Log Aktivitas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID log aktivitas",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail log aktivitas berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detail log aktivitas berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/LogAktivitas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Log aktivitas tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Log aktivitas tidak ditemukan")
     *         )
     *     )
     * )
     * 
     * Display the specified log aktivitas.
     */
    public function show($id): JsonResponse
    {
        try {
            $log = LogAktivitas::with(['user:id,nama,email'])->find($id);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log aktivitas tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail log aktivitas berhasil diambil',
                'data' => [
                    'id' => $log->id,
                    'user' => [
                        'id' => $log->user ? $log->user->id : null,
                        'name' => $log->user ? $log->user->name : null,
                        'email' => $log->user ? $log->user->email : null,
                    ],
                    'aksi' => $log->aksi,
                    'aksi_formatted' => $log->aksi_formatted,
                    'deskripsi' => $log->deskripsi,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'data_lama' => $log->data_lama,
                    'data_baru' => $log->data_baru,
                    'waktu' => $log->waktu,
                    'created_at' => $log->created_at,
                    'updated_at' => $log->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail log aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/log-aktivitas",
     *     summary="Create new log aktivitas",
     *     description="Menambahkan log aktivitas baru ke sistem",
     *     tags={"Log Aktivitas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"aksi", "deskripsi"},
     *             @OA\Property(property="aksi", type="string", maxLength=50, example="create"),
     *             @OA\Property(property="deskripsi", type="string", example="Menambahkan barang baru dengan kode BRG001"),
     *             @OA\Property(property="model_type", type="string", example="App\\Models\\Barang"),
     *             @OA\Property(property="model_id", type="integer", example=15),
     *             @OA\Property(property="data_lama", type="object", nullable=true),
     *             @OA\Property(
     *                 property="data_baru", 
     *                 type="object", 
     *                 nullable=true,
     *                 example={"kode_barang": "BRG001", "nama_barang": "Laptop ASUS", "kategori_id": 1}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Log aktivitas berhasil ditambahkan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Log aktivitas berhasil ditambahkan"),
     *             @OA\Property(property="data", ref="#/components/schemas/LogAktivitas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validasi gagal"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     * 
     * Store a newly created log aktivitas.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'aksi' => 'required|string|max:50',
                'deskripsi' => 'required|string',
                'model_type' => 'nullable|string',
                'model_id' => 'nullable|integer',
                'data_lama' => 'nullable|array',
                'data_baru' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();

            // Set user_id from authenticated user
            $logData = array_merge($validatedData, [
                'timestamp' => now(),
                'user_id' => Auth::id(),
            ]);

            $log = LogAktivitas::create($logData);

            return response()->json([
                'success' => true,
                'message' => 'Log aktivitas berhasil ditambahkan',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan log aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/log-aktivitas/statistics",
     *     summary="Get log aktivitas statistics",
     *     description="Mengambil statistik log aktivitas sistem",
     *     tags={"Log Aktivitas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Tanggal mulai untuk filter statistik (format: Y-m-d)",
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Tanggal akhir untuk filter statistik (format: Y-m-d)",
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statistik log aktivitas berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistik log aktivitas berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_aktivitas", type="integer", example=150),
     *                 @OA\Property(property="aktivitas_hari_ini", type="integer", example=25),
     *                 @OA\Property(property="aktivitas_minggu_ini", type="integer", example=89),
     *                 @OA\Property(property="aktivitas_bulan_ini", type="integer", example=134),
     *                 @OA\Property(
     *                     property="top_aksi",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="aksi", type="string", example="create"),
     *                         @OA\Property(property="jumlah", type="integer", example=45)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="top_users",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Admin User"),
     *                         @OA\Property(property="jumlah", type="integer", example=89)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="aktivitas_per_hari",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="tanggal", type="string", format="date", example="2024-01-15"),
     *                         @OA\Property(property="jumlah", type="integer", example=25)
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     * 
     * Get activity statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // day, week, month, year

            $query = LogAktivitas::query();

            // Set time range based on period
            switch ($period) {
                case 'day':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->thisWeek();
                    break;
                case 'month':
                    $query->thisMonth();
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }

            // Total activities
            $totalActivities = $query->count();

            // Activities by action
            $activitiesByAction = $query->selectRaw('aksi, COUNT(*) as total')
                ->groupBy('aksi')
                ->orderBy('total', 'desc')
                ->get();

            // Activities by user (top 10)
            $activitiesByUser = $query->with(['user:id,nama'])
                ->selectRaw('user_id, COUNT(*) as total')
                ->groupBy('user_id')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->user_id,
                        'user_name' => $item->user ? $item->user->nama : 'Unknown',
                        'total' => $item->total
                    ];
                });

            // Daily activities for the period
            $dailyActivities = [];
            if ($period === 'month') {
                $dailyActivities = LogAktivitas::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            }

            // Most active hours
            $hourlyActivities = $query->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Statistik aktivitas berhasil diambil',
                'data' => [
                    'period' => $period,
                    'total_activities' => $totalActivities,
                    'activities_by_action' => $activitiesByAction,
                    'activities_by_user' => $activitiesByUser,
                    'daily_activities' => $dailyActivities,
                    'hourly_activities' => $hourlyActivities,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/log-aktivitas/my-activities",
     *     summary="Get current user activities",
     *     description="Mengambil log aktivitas milik user yang sedang login",
     *     tags={"Log Aktivitas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Nomor halaman",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query", 
     *         description="Jumlah data per halaman",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="aksi",
     *         in="query",
     *         description="Filter berdasarkan jenis aksi",
     *         @OA\Schema(type="string", example="create")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Tanggal mulai (format: Y-m-d)",
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Tanggal akhir (format: Y-m-d)",
     *         @OA\Schema(type="string", format="date", example="2024-01-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data aktivitas user berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data aktivitas user berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/LogAktivitas")),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     )
     * )
     * 
     * Get user's own activity log.
     */
    public function myActivities(Request $request): JsonResponse
    {
        try {
            $query = LogAktivitas::byUser(Auth::id())
                ->orderBy('created_at', 'desc');

            // Filter by action
            if ($request->has('aksi') && $request->aksi) {
                $query->byAksi($request->aksi);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $query->where('created_at', '>=', $startDate);
            }

            if ($request->has('end_date') && $request->end_date) {
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $query->where('created_at', '<=', $endDate);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $logs = $query->paginate($perPage);

            // Transform data
            $logs->getCollection()->transform(function ($log) {
                return [
                    'id' => $log->id,
                    'aksi' => $log->aksi,
                    'aksi_formatted' => $log->aksi_formatted,
                    'deskripsi' => $log->deskripsi,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'waktu' => $log->waktu,
                    'created_at' => $log->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Data aktivitas saya berhasil diambil',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/log-aktivitas/cleanup",
     *     summary="Cleanup old log activities",
     *     description="Membersihkan log aktivitas yang sudah lama untuk optimasi database",
     *     tags={"Log Aktivitas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"older_than_days"},
     *             @OA\Property(
     *                 property="older_than_days", 
     *                 type="integer", 
     *                 minimum=30,
     *                 description="Hapus log yang lebih lama dari jumlah hari ini (minimal 30 hari)",
     *                 example=90
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Log aktivitas berhasil dibersihkan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Log aktivitas berhasil dibersihkan"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="deleted_count", type="integer", example=45),
     *                 @OA\Property(property="cutoff_date", type="string", format="date-time", example="2023-10-15 00:00:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validasi gagal"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     * 
     * Delete old log activities (cleanup).
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'days' => 'required|integer|min:30', // Minimal 30 hari
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $days = $request->days;
            $cutoffDate = Carbon::now()->subDays($days);
            
            $deletedCount = LogAktivitas::where('created_at', '<', $cutoffDate)->delete();

            LogAktivitas::log(
                'cleanup',
                "Membersihkan log aktivitas lebih dari {$days} hari ({$deletedCount} record dihapus)"
            );

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus {$deletedCount} log aktivitas lama",
                'data' => [
                    'deleted_count' => $deletedCount,
                    'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membersihkan log aktivitas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/log-aktivitas/export",
     *     summary="Export log activities",
     *     description="Mengekspor log aktivitas ke berbagai format file",
     *     tags={"Log Aktivitas"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"format"},
     *             @OA\Property(
     *                 property="format", 
     *                 type="string", 
     *                 enum={"csv", "excel", "json"},
     *                 description="Format file export",
     *                 example="csv"
     *             ),
     *             @OA\Property(
     *                 property="start_date", 
     *                 type="string", 
     *                 format="date",
     *                 description="Tanggal mulai untuk filter export",
     *                 example="2024-01-01"
     *             ),
     *             @OA\Property(
     *                 property="end_date", 
     *                 type="string", 
     *                 format="date",
     *                 description="Tanggal akhir untuk filter export",
     *                 example="2024-01-31"
     *             ),
     *             @OA\Property(
     *                 property="aksi", 
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 description="Filter berdasarkan aksi tertentu",
     *                 example={"create", "update"}
     *             ),
     *             @OA\Property(
     *                 property="user_id", 
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 description="Filter berdasarkan user ID tertentu",
     *                 example={1, 2}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data log aktivitas berhasil diekspor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data log aktivitas berhasil diekspor"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="download_url", type="string", example="http://localhost/storage/exports/log_aktivitas_20240115.csv"),
     *                 @OA\Property(property="file_size", type="string", example="2.5 MB"),
     *                 @OA\Property(property="total_records", type="integer", example=1250),
     *                 @OA\Property(property="generated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validasi gagal"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     * 
     * Export activity logs.
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'format' => 'required|in:csv,excel',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'user_id' => 'nullable|exists:users,id',
                'aksi' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = LogAktivitas::with(['user:id,nama,email']);

            // Apply filters
            if ($request->user_id) {
                $query->byUser($request->user_id);
            }

            if ($request->aksi) {
                $query->byAksi($request->aksi);
            }

            if ($request->start_date) {
                $query->where('created_at', '>=', Carbon::parse($request->start_date)->startOfDay());
            }

            if ($request->end_date) {
                $query->where('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
            }

            $logs = $query->orderBy('created_at', 'desc')->get();

            // Prepare export data
            $exportData = $logs->map(function ($log) {
                return [
                    'ID' => $log->id,
                    'User' => ($log->user ? $log->user->name : null) ?? 'Unknown',
                    'Email' => ($log->user ? $log->user->email : null) ?? '',
                    'Aksi' => $log->aksi_formatted,
                    'Deskripsi' => $log->deskripsi,
                    'Model Type' => $log->model_type,
                    'Model ID' => $log->model_id,
                    'IP Address' => $log->ip_address,
                    'Waktu' => $log->waktu,
                ];
            });

            // Generate filename
            $filename = 'log_aktivitas_' . now()->format('Y-m-d_H-i-s');

            // Log export activity
            LogAktivitas::log(
                'export',
                "Mengekspor {$logs->count()} data log aktivitas dalam format {$request->format}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Data siap untuk diekspor',
                'data' => [
                    'filename' => $filename,
                    'format' => $request->format,
                    'total_records' => $logs->count(),
                    'export_data' => $exportData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}