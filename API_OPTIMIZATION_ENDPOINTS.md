# 🚀 Warehouse Optimization API Endpoints

Dokumentasi lengkap API endpoints untuk sistem optimasi warehouse menggunakan Simulated Annealing.

---

## 📋 Daftar Endpoint

### 🔍 **1. Optimization Management**

#### Get Available Algorithms
```bash
GET /api/optimization/algorithms
```
**Response:**
```json
{
  "status": "success",
  "message": "Daftar algoritma optimasi tersedia",
  "data": [
    {
      "name": "Simulated Annealing",
      "description": "Algoritma optimasi metaheuristik untuk penempatan barang optimal",
      "parameters": {
        "temperature_initial": {"type": "float", "min": 100, "max": 5000, "default": 1000},
        "temperature_final": {"type": "float", "min": 0.01, "max": 10, "default": 0.1},
        "cooling_rate": {"type": "float", "min": 0.8, "max": 0.99, "default": 0.95},
        "max_iterations": {"type": "integer", "min": 100, "max": 5000, "default": 1000}
      },
      "estimated_time": "3-10 menit"
    }
  ]
}
```

#### Get Warehouse State
```bash
GET /api/optimization/warehouse-state
```
**Response:**
```json
{
  "status": "success",
  "data": {
    "areas": [...],
    "barang": [...],
    "statistics": {
      "total_areas": 5,
      "total_items": 6,
      "total_capacity": 2002,
      "used_capacity": 0,
      "capacity_utilization": 0
    }
  }
}
```

#### Run Simulated Annealing Optimization
```bash
POST /api/optimization/simulated-annealing
Content-Type: application/json

{
  "temperature_initial": 1000,
  "temperature_final": 0.1,
  "cooling_rate": 0.95,
  "max_iterations": 1000,
  "target_optimasi": "Optimasi penempatan barang dengan SA"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Optimasi Simulated Annealing telah dimulai",
  "data": {
    "log_optimasi_id": 1,
    "algoritma": "Simulated Annealing",
    "parameters": {...},
    "status": "sedang_berjalan",
    "waktu_mulai": "2025-10-18T10:00:00Z"
  }
}
```

#### Get Optimization Status
```bash
GET /api/optimization/{logOptimasiId}/status
```

#### Cancel Running Optimization
```bash
POST /api/optimization/{logOptimasiId}/cancel
```

---

### 📊 **2. Log Optimasi Management**

#### Get All Optimization Logs
```bash
GET /api/log-optimasi
GET /api/log-optimasi?status=selesai
GET /api/log-optimasi?algoritma=Simulated Annealing
GET /api/log-optimasi?per_page=20
```

#### Create Optimization Log
```bash
POST /api/log-optimasi
Content-Type: application/json

{
  "algoritma": "Simulated Annealing",
  "parameter_optimasi": "{\"temp_initial\": 1000}",
  "target_optimasi": "Test optimization",
  "estimasi_waktu": 300
}
```

#### Get Specific Log
```bash
GET /api/log-optimasi/{id}
```

#### Update Log Status
```bash
PUT /api/log-optimasi/{id}
Content-Type: application/json

{
  "status": "selesai",
  "hasil_optimasi": "{\"best_cost\": 15188.96}",
  "metrik_hasil": "{\"total_items\": 6}",
  "waktu_selesai": "2025-10-18T10:05:00Z"
}
```

#### Get Optimization Statistics
```bash
GET /api/log-optimasi/statistics
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "total_optimasi": 10,
    "optimasi_berhasil": 8,
    "optimasi_gagal": 1,
    "optimasi_berjalan": 1,
    "algoritma_populer": [...],
    "rata_rata_waktu": 285.5
  }
}
```

---

### 🎯 **3. Rekomendasi Penempatan Management**

#### Get All Recommendations
```bash
GET /api/rekomendasi-penempatan
GET /api/rekomendasi-penempatan?status=menunggu
GET /api/rekomendasi-penempatan?log_optimasi_id=1
GET /api/rekomendasi-penempatan?algoritma=Simulated Annealing
```

#### Store Multiple Recommendations
```bash
POST /api/rekomendasi-penempatan
Content-Type: application/json

{
  "log_optimasi_id": 1,
  "rekomendasi": [
    {
      "barang_id": 1,
      "area_gudang_rekomendasi": 3,
      "koordinat_x_spesifik": 15.50,
      "koordinat_y_spesifik": 8.25,
      "alasan": "Optimasi SA: Laptop Gaming di Area B1",
      "prioritas": "tinggi",
      "confidence_score": 0.85,
      "algoritma": "Simulated Annealing"
    }
  ]
}
```

#### Get Specific Recommendation
```bash
GET /api/rekomendasi-penempatan/{id}
```

#### Update Recommendation Status
```bash
PATCH /api/rekomendasi-penempatan/{id}/status
Content-Type: application/json

{
  "status": "disetujui",
  "catatan": "Rekomendasi bagus untuk efisiensi picking",
  "disetujui_oleh": 1
}
```

#### Bulk Approve Recommendations
```bash
POST /api/rekomendasi-penempatan/bulk-approve
Content-Type: application/json

{
  "rekomendasi_ids": [1, 2, 3, 4],
  "catatan": "Batch approval untuk optimasi #1"
}
```

#### Get Recommendations Statistics
```bash
GET /api/rekomendasi-penempatan/statistics
GET /api/rekomendasi-penempatan/statistics?log_optimasi_id=1
```

---

## 🧪 **Testing Commands**

### Test Workflow Lengkap:

1. **Cek algoritma yang tersedia:**
```bash
curl -X GET "http://127.0.0.1:8000/api/optimization/algorithms" \
  -H "Authorization: Bearer $(cat token.txt)"
```

2. **Cek status warehouse:**
```bash
curl -X GET "http://127.0.0.1:8000/api/optimization/warehouse-state" \
  -H "Authorization: Bearer $(cat token.txt)"
```

3. **Jalankan optimasi:**
```bash
curl -X POST "http://127.0.0.1:8000/api/optimization/simulated-annealing" \
  -H "Authorization: Bearer $(cat token.txt)" \
  -H "Content-Type: application/json" \
  -d '{
    "temperature_initial": 1000,
    "cooling_rate": 0.95,
    "max_iterations": 500,
    "target_optimasi": "Test optimization via API"
  }'
```

4. **Monitor status optimasi:**
```bash
curl -X GET "http://127.0.0.1:8000/api/optimization/1/status" \
  -H "Authorization: Bearer $(cat token.txt)"
```

5. **Lihat log optimasi:**
```bash
curl -X GET "http://127.0.0.1:8000/api/log-optimasi" \
  -H "Authorization: Bearer $(cat token.txt)"
```

6. **Lihat rekomendasi hasil optimasi:**
```bash
curl -X GET "http://127.0.0.1:8000/api/rekomendasi-penempatan?log_optimasi_id=1" \
  -H "Authorization: Bearer $(cat token.txt)"
```

7. **Approve rekomendasi:**
```bash
curl -X PATCH "http://127.0.0.1:8000/api/rekomendasi-penempatan/1/status" \
  -H "Authorization: Bearer $(cat token.txt)" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "disetujui",
    "catatan": "Rekomendasi excellent!"
  }'
```

---

## 📈 **Status Codes & Error Handling**

### Success Responses:
- `200 OK` - Request berhasil
- `201 Created` - Resource berhasil dibuat
- `202 Accepted` - Request diterima untuk async processing

### Error Responses:
- `400 Bad Request` - Request tidak valid
- `401 Unauthorized` - Token tidak valid
- `404 Not Found` - Resource tidak ditemukan
- `422 Unprocessable Entity` - Validasi gagal
- `500 Internal Server Error` - Server error

### Example Error Response:
```json
{
  "status": "error",
  "message": "Validasi gagal",
  "errors": {
    "temperature_initial": ["The temperature initial must be at least 100."]
  }
}
```

---

## 🔐 **Authentication**

Semua endpoint memerlukan Sanctum Bearer Token:
```bash
Authorization: Bearer your_token_here
```

---

## 📝 **Status Values**

### Log Optimasi Status:
- `berjalan` / `sedang_berjalan` - Optimasi sedang berjalan
- `selesai` - Optimasi berhasil selesai
- `gagal` - Optimasi gagal
- `dibatalkan` - Optimasi dibatalkan user

### Rekomendasi Status:
- `menunggu` - Menunggu review
- `disetujui` - Disetujui untuk implementasi
- `ditolak` - Ditolak
- `diimplementasi` - Sudah diimplementasi

### Prioritas Rekomendasi:
- `rendah` - Prioritas rendah
- `sedang` - Prioritas sedang
- `tinggi` - Prioritas tinggi

---

## 🚀 **Next Steps**

1. **Integrasi Python Script** - Connect API dengan script Python
2. **Real-time Updates** - WebSocket untuk status real-time
3. **Advanced Analytics** - Dashboard dan reporting
4. **Batch Operations** - Multiple optimization runs
5. **Performance Monitoring** - Metrics dan alerting

---

*API Documentation v1.0*  
*Generated: 18 Oktober 2025*