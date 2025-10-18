# 📱 Panduan Integrasi Mobile - Sistem Optimasi Gudang

## 🎯 Overview
Dokumentasi ini menjelaskan cara mengintegrasikan aplikasi mobile dengan API optimasi gudang yang menggunakan algoritma Simulated Annealing untuk optimasi penempatan barang.

---

## 🔐 Authentication
**Base URL:** `http://127.0.0.1:8000/api`

Semua endpoint memerlukan autentikasi menggunakan Bearer Token:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 📱 Struktur Halaman Mobile

### 1. 🏠 **Halaman Dashboard Optimasi**
**Route:** `/optimization-dashboard`

#### 📊 Komponen yang harus ada:
- **Statistics Cards:**
  - Total Optimasi
  - Optimasi Berhasil 
  - Optimasi Gagal
  - Optimasi Berjalan
- **Quick Actions:**
  - Button "Mulai Optimasi Baru"
  - Button "Lihat History"
  - Button "Lihat Rekomendasi"

#### 🔌 API Endpoints:
```
GET /api/log-optimasi/statistics
```

#### 📱 UI Implementation:
```dart
// Example StatCard Widget
StatCard(
  title: "Total Optimasi",
  value: "16",
  icon: Icons.analytics,
  color: Colors.blue
)

// Quick Action Button
ElevatedButton(
  onPressed: () => Navigator.push(context, 
    MaterialPageRoute(builder: (context) => OptimizationFormPage())
  ),
  child: Text("Mulai Optimasi Baru")
)
```

---

### 2. ⚙️ **Halaman Form Optimasi**
**Route:** `/optimization-form`

#### 📋 Form Fields yang diperlukan:
1. **Pilih Gudang** (Multi-select)
   - Dropdown/Checkbox list gudang aktif
2. **Pilih Barang** (Multi-select) 
   - Dropdown/Checkbox list barang aktif
3. **Prioritas Optimasi** (Radio Button)
   - `space_utilization` - Maksimalkan Ruang
   - `accessibility` - Kemudahan Akses  
   - `balanced` - Seimbang
4. **Target Utilisasi** (Slider)
   - Range: 50-100%
   - Default: 85%
5. **Keterangan** (TextArea)
   - Optional description

#### 🔌 API Endpoints untuk Data:
```
GET /api/gudang              # Daftar gudang
GET /api/barang              # Daftar barang  
GET /api/optimization/algorithms  # Info algoritma
```

#### 📤 Submit Optimasi:
```
POST /api/optimization/simulated-annealing
```

#### 📱 UI Implementation:
```dart
class OptimizationForm extends StatefulWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text("Optimasi Gudang")),
      body: Form(
        child: Column(
          children: [
            // Multi-select Gudang
            MultiSelectChip(
              title: "Pilih Gudang",
              items: gudangList,
              onSelectionChanged: (selected) => setState(() => selectedGudang = selected)
            ),
            
            // Multi-select Barang  
            MultiSelectChip(
              title: "Pilih Barang",
              items: barangList,
              onSelectionChanged: (selected) => setState(() => selectedBarang = selected)
            ),
            
            // Prioritas Radio
            RadioListTile<String>(
              title: Text("Maksimalkan Ruang"),
              value: "space_utilization",
              groupValue: prioritas,
              onChanged: (value) => setState(() => prioritas = value)
            ),
            
            // Target Utilisasi Slider
            Slider(
              min: 50,
              max: 100,
              divisions: 50,
              label: "${targetUtilisasi.round()}%",
              value: targetUtilisasi,
              onChanged: (value) => setState(() => targetUtilisasi = value)
            ),
            
            // Submit Button
            ElevatedButton(
              onPressed: _submitOptimization,
              child: Text("Mulai Optimasi")
            )
          ]
        )
      )
    );
  }
}
```

---

### 3. 📈 **Halaman Hasil Optimasi** 
**Route:** `/optimization-result`

#### 📊 Komponen Result Screen:
- **Status Badge:** `selesai`, `gagal`, `sedang_berjalan`
- **Ringkasan Hasil:**
  - Total Barang: 6 items
  - Area Digunakan: 4 areas  
  - Waktu Eksekusi: 0.21s
  - Final Cost: 15,147.81
- **Action Buttons:**
  - "Lihat Detail Rekomendasi"
  - "Jalankan Optimasi Lagi"
  - "Bagikan Hasil"

#### 🔌 API Response dari Submit:
```json
{
  "status": "success",
  "message": "Optimisasi berhasil diselesaikan",
  "data": {
    "log_optimasi_id": 16,
    "algoritma": "Simulated Annealing", 
    "total_gudang": 1,
    "total_barang": 6,
    "status": "selesai",
    "waktu_mulai": "2025-10-18 13:22:12",
    "waktu_selesai": "2025-10-18 20:22:12", 
    "hasil_optimasi": {
      "algorithm": "Simulated Annealing",
      "final_cost": 15147.807321860531,
      "total_items": 6,
      "areas_utilized": 4,
      "execution_time": 0.1
    },
    "total_rekomendasi": 6,
    "waktu_eksekusi": "0.21s"
  }
}
```

#### 📱 UI Implementation:
```dart
class OptimizationResult extends StatelessWidget {
  final OptimizationData result;
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text("Hasil Optimasi"),
        actions: [
          IconButton(
            icon: Icon(Icons.share),
            onPressed: _shareResult
          )
        ]
      ),
      body: Column(
        children: [
          // Status Card
          Card(
            child: ListTile(
              leading: _getStatusIcon(result.status),
              title: Text("Status: ${result.status}"),
              subtitle: Text("ID: ${result.logOptimasiId}")
            )
          ),
          
          // Metrics Grid
          GridView.count(
            crossAxisCount: 2,
            children: [
              MetricCard(label: "Total Barang", value: "${result.totalBarang}"),
              MetricCard(label: "Area Digunakan", value: "${result.hasilOptimasi.areasUtilized}"),  
              MetricCard(label: "Waktu Eksekusi", value: result.waktuEksekusi),
              MetricCard(label: "Final Cost", value: "${result.hasilOptimasi.finalCost.toStringAsFixed(2)}")
            ]
          ),
          
          // Action Buttons
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              ElevatedButton(
                onPressed: () => _viewRecommendations(result.logOptimasiId),
                child: Text("Lihat Rekomendasi")
              ),
              OutlinedButton(
                onPressed: () => Navigator.pop(context),
                child: Text("Optimasi Lagi") 
              )
            ]
          )
        ]
      )
    );
  }
}
```

---

### 4. 📋 **Halaman Daftar Rekomendasi**
**Route:** `/recommendations`

#### 📊 List Item Components:
- **Item Card dengan info:**
  - Nama Barang & Kode
  - Area Tujuan Rekomendasi
  - Koordinat Spesifik (X, Y)
  - Status Badge
  - Confidence Score
  - Action Button (Setujui/Tolak)

#### 🔌 API Endpoints:
```
GET /api/rekomendasi-penempatan?log_optimasi_id=16
GET /api/rekomendasi-penempatan/statistics
POST /api/rekomendasi-penempatan/bulk-approve
```

#### 📱 UI Implementation:
```dart
class RecommendationsList extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text("Rekomendasi Penempatan"),
        actions: [
          IconButton(
            icon: Icon(Icons.check_circle),
            onPressed: _bulkApprove
          )
        ]
      ),
      body: ListView.builder(
        itemCount: recommendations.length,
        itemBuilder: (context, index) {
          final item = recommendations[index];
          return Card(
            margin: EdgeInsets.all(8.0),
            child: ExpansionTile(
              leading: CircleAvatar(
                backgroundColor: _getPriorityColor(item.prioritas),
                child: Text(item.barang.kodeBarang.substring(0, 2))
              ),
              title: Text(item.barang.namaBarang),
              subtitle: Text("→ ${item.areaGudangRekomendasi.namaArea}"),
              trailing: StatusBadge(status: item.status),
              children: [
                Padding(
                  padding: EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.location_on, size: 16),
                          SizedBox(width: 4),
                          Text("Koordinat: (${item.koordinatXSpesifik}, ${item.koordinatYSpesifik})")
                        ]
                      ),
                      SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(Icons.thumb_up, size: 16),
                          SizedBox(width: 4),
                          Text("Confidence: ${(item.confidenceScore * 100).toInt()}%")
                        ]
                      ),
                      SizedBox(height: 8),
                      Text("Alasan: ${item.alasan}"),
                      SizedBox(height: 16),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          TextButton(
                            onPressed: () => _rejectRecommendation(item.id),
                            child: Text("Tolak"),
                            style: TextButton.styleFrom(foregroundColor: Colors.red)
                          ),
                          SizedBox(width: 8),
                          ElevatedButton(
                            onPressed: () => _approveRecommendation(item.id),
                            child: Text("Setujui")
                          )
                        ]
                      )
                    ]
                  )
                )
              ]
            )
          );
        }
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _selectMultiple,
        icon: Icon(Icons.checklist),
        label: Text("Pilih Multiple")
      )
    );
  }
}
```

---

### 5. 📚 **Halaman History/Log Optimasi**
**Route:** `/optimization-history`

#### 📊 List Components:
- **Search & Filter Bar**
- **Log Cards dengan info:**
  - Algoritma & Tanggal
  - Status dengan color coding
  - Ringkasan hasil (total barang, area)
  - Durasi eksekusi
  - Action: Lihat Detail

#### 🔌 API Endpoints:
```
GET /api/log-optimasi?status=selesai&per_page=20
GET /api/log-optimasi/{id}  # Detail log
```

#### 📱 UI Implementation:
```dart
class OptimizationHistory extends StatefulWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text("History Optimasi"),
        actions: [
          IconButton(
            icon: Icon(Icons.filter_list),
            onPressed: _showFilterDialog
          )
        ]
      ),
      body: Column(
        children: [
          // Search Bar
          Padding(
            padding: EdgeInsets.all(16.0),
            child: TextField(
              decoration: InputDecoration(
                hintText: "Cari berdasarkan algoritma...",
                prefixIcon: Icon(Icons.search),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10)
                )
              ),
              onChanged: _filterLogs
            )
          ),
          
          // Logs List
          Expanded(
            child: ListView.builder(
              itemCount: logs.length,
              itemBuilder: (context, index) {
                final log = logs[index];
                return Card(
                  margin: EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                  child: ListTile(
                    leading: CircleAvatar(
                      backgroundColor: _getStatusColor(log.status),
                      child: Icon(
                        _getStatusIcon(log.status),
                        color: Colors.white
                      )
                    ),
                    title: Text("${log.algoritma} #${log.id}"),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(DateFormat('dd MMM yyyy HH:mm').format(log.waktuMulai)),
                        if (log.hasilOptimasi != null) 
                          Text("${log.hasilOptimasi.totalItems} barang → ${log.hasilOptimasi.areasUtilized} area")
                      ]
                    ),
                    trailing: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        StatusBadge(status: log.status),
                        if (log.waktuEksekusi != null)
                          Text("${log.waktuEksekusi}s", style: TextStyle(fontSize: 12))
                      ]
                    ),
                    onTap: () => _showLogDetail(log)
                  )
                );
              }
            )
          )
        ]
      )
    );
  }
}
```

---

### 6. 🔍 **Halaman Detail Log Optimasi**
**Route:** `/optimization-detail/:id`

#### 📊 Detail Components:
- **Header dengan status dan basic info**
- **Parameter Optimasi (JSON formatted)**
- **Hasil Optimasi Metrics**
- **Timeline/Progress (jika ada)**
- **List Rekomendasi terkait**
- **Action Buttons:** Ulangi Optimasi, Export

#### 🔌 API Endpoint:
```
GET /api/log-optimasi/{id}
```

---

## 🔧 Utility Functions & Models

### 📱 Data Models:
```dart
class OptimizationLog {
  final int id;
  final String algoritma;
  final String parameterOptimasi;
  final String targetOptimasi;
  final String status;
  final DateTime waktuMulai;
  final DateTime? waktuSelesai;
  final OptimizationResult? hasilOptimasi;
  final String? waktuEksekusi;
}

class RecommendationItem {
  final int id;
  final int logOptimasiId;
  final Barang barang;
  final AreaGudang areaGudangRekomendasi;
  final double koordinatXSpesifik;
  final double koordinatYSpesifik;
  final String alasan;
  final double confidenceScore;
  final String status;
  final String prioritas;
}
```

### 🎨 UI Helper Functions:
```dart
Color getStatusColor(String status) {
  switch (status) {
    case 'selesai': return Colors.green;
    case 'gagal': return Colors.red;
    case 'sedang_berjalan': return Colors.orange;
    case 'dibatalkan': return Colors.grey;
    default: return Colors.blue;
  }
}

IconData getStatusIcon(String status) {
  switch (status) {
    case 'selesai': return Icons.check_circle;
    case 'gagal': return Icons.error;
    case 'sedang_berjalan': return Icons.hourglass_empty;
    case 'dibatalkan': return Icons.cancel;
    default: return Icons.info;
  }
}
```

---

## 🚀 Implementation Steps

### Phase 1: Basic Integration
1. ✅ Setup API service dengan authentication
2. ✅ Implement optimization form
3. ✅ Create result display screen
4. ✅ Basic history list

### Phase 2: Advanced Features  
1. ✅ Recommendation management
2. ✅ Bulk approval functionality
3. ✅ Search and filters
4. ✅ Statistics dashboard

### Phase 3: Enhancement
1. ✅ Real-time updates (WebSocket/Polling)
2. ✅ Offline capability
3. ✅ Export functionality
4. ✅ Push notifications

---

## 🔗 API Integration Examples

### 🚀 Submit Optimasi:
```dart
Future<OptimizationResult> submitOptimization({
  required List<int> gudangIds,
  required List<int> barangIds,
  required String prioritas,
  required double targetUtilisasi,
  String? keterangan
}) async {
  final response = await http.post(
    Uri.parse('$baseUrl/optimization/simulated-annealing'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json'
    },
    body: jsonEncode({
      'gudang_ids': gudangIds,
      'barang_ids': barangIds,  
      'prioritas_optimasi': prioritas,
      'target_utilisasi': targetUtilisasi,
      'keterangan': keterangan
    })
  );
  
  if (response.statusCode == 200) {
    return OptimizationResult.fromJson(jsonDecode(response.body)['data']);
  } else {
    throw Exception('Optimasi gagal: ${response.body}');
  }
}
```

### 📋 Fetch Rekomendasi:
```dart
Future<List<RecommendationItem>> getRecommendations({
  int? logOptimasiId,
  String? status,
  int page = 1
}) async {
  final queryParams = {
    if (logOptimasiId != null) 'log_optimasi_id': logOptimasiId.toString(),
    if (status != null) 'status': status,
    'page': page.toString(),
    'per_page': '15'
  };
  
  final uri = Uri.parse('$baseUrl/rekomendasi-penempatan').replace(
    queryParameters: queryParams
  );
  
  final response = await http.get(uri, headers: {
    'Authorization': 'Bearer $token'
  });
  
  if (response.statusCode == 200) {
    final data = jsonDecode(response.body)['data']['data'] as List;
    return data.map((item) => RecommendationItem.fromJson(item)).toList();
  } else {
    throw Exception('Gagal mengambil rekomendasi');
  }
}
```

### ✅ Bulk Approve:
```dart
Future<bool> bulkApproveRecommendations({
  required List<int> recommendationIds,
  String? catatan
}) async {
  final response = await http.post(
    Uri.parse('$baseUrl/rekomendasi-penempatan/bulk-approve'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json'
    },
    body: jsonEncode({
      'rekomendasi_ids': recommendationIds,
      'catatan': catatan
    })
  );
  
  return response.statusCode == 200;
}
```

---

## ⚠️ Error Handling

### 🚨 Common Error Cases:
1. **422 Validation Error:** Invalid parameters
2. **401 Unauthorized:** Token expired/invalid  
3. **500 Server Error:** Optimization failed
4. **404 Not Found:** Resource tidak ditemukan

### 🛠️ Error Handler Example:
```dart
class ApiException implements Exception {
  final int statusCode;
  final String message;
  final Map<String, dynamic>? errors;
  
  ApiException(this.statusCode, this.message, [this.errors]);
  
  factory ApiException.fromResponse(http.Response response) {
    final body = jsonDecode(response.body);
    return ApiException(
      response.statusCode,
      body['message'] ?? 'Unknown error',
      body['errors']
    );
  }
}

// Usage in service
try {
  final result = await submitOptimization(...);
  return result;
} on ApiException catch (e) {
  if (e.statusCode == 422) {
    _showValidationErrors(e.errors);
  } else {
    _showErrorSnackBar(e.message);
  }
  rethrow;
} catch (e) {
  _showErrorSnackBar('Terjadi kesalahan koneksi');
  rethrow;
}
```

---

## 📱 UX/UI Guidelines

### 🎨 Design Principles:
- **Loading States:** Show progress untuk operasi async
- **Empty States:** Pesan informatif saat tidak ada data  
- **Error States:** Error message yang user-friendly
- **Success Feedback:** Confirmation setelah action berhasil

### 📊 Performance Tips:
- **Pagination:** Load data secara bertahap
- **Caching:** Cache data yang sering diakses  
- **Lazy Loading:** Load detail hanya saat diperlukan
- **Optimistic Updates:** Update UI sebelum API response

---

## 🧪 Testing Checklist

### ✅ Unit Tests:
- [ ] API service methods
- [ ] Data model parsing  
- [ ] Utility functions
- [ ] Form validation

### ✅ Integration Tests:
- [ ] Form submission flow
- [ ] Result display accuracy
- [ ] Recommendation approval
- [ ] History filtering

### ✅ UI Tests:
- [ ] Navigation flow
- [ ] Loading states
- [ ] Error handling
- [ ] Responsive design

---

## 📞 Support & Contact

Jika ada pertanyaan atau butuh klarifikasi implementasi:
- **Backend Developer:** [Team Backend]
- **API Documentation:** `/api/documentation` 
- **Slack Channel:** #mobile-integration

---

**🎯 Happy Coding! Semoga integrasi berjalan lancar! 🚀**