# 📄 Contoh Response API - Sistem Optimasi Gudang

## 🔗 Base URL & Authentication
```
Base URL: http://127.0.0.1:8000/api
Authorization: Bearer YOUR_TOKEN_HERE
```

---

## 📊 1. GET /api/log-optimasi/statistics

### Response:
```json
{
  "status": "success",
  "message": "Statistik optimasi berhasil diambil", 
  "data": {
    "total_optimasi": 16,
    "optimasi_berhasil": 14,
    "optimasi_gagal": 1,
    "optimasi_berjalan": 1,
    "algoritma_populer": [
      {
        "algoritma": "Simulated Annealing",
        "total": 16
      }
    ],
    "rata_rata_waktu": 0.15
  }
}
```

---

## ⚙️ 2. POST /api/optimization/simulated-annealing

### Request Body:
```json
{
  "gudang_ids": [1],
  "barang_ids": [1, 2, 3, 4, 5, 6],
  "prioritas_optimasi": "balanced",
  "target_utilisasi": 95,
  "keterangan": "Optimasi full synchronous"
}
```

### Response (Sukses):
```json
{
  "status": "success",
  "message": "Optimisasi penempatan barang di ruang gudang berhasil diselesaikan",
  "data": {
    "log_optimasi_id": 16,
    "algoritma": "Simulated Annealing",
    "total_gudang": 1,
    "total_barang": 6,
    "prioritas_optimasi": "balanced",
    "target_utilisasi": 95,
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

### Response (Error Validation):
```json
{
  "status": "error",
  "message": "Validasi data gudang dan barang gagal",
  "errors": {
    "prioritas_optimasi": ["The selected prioritas optimasi is invalid."],
    "gudang_ids.0": ["The selected gudang_ids.0 is invalid."]
  }
}
```

---

## 📋 3. GET /api/rekomendasi-penempatan

### Query Parameters:
```
?log_optimasi_id=16&per_page=3&status=menunggu
```

### Response:
```json
{
  "status": "success",
  "message": "Rekomendasi penempatan berhasil diambil",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 40,
        "log_optimasi_id": 16,
        "barang_id": 3,
        "area_gudang_saat_ini": null,
        "area_gudang_rekomendasi": {
          "id": 2,
          "gudang_id": 1,
          "kode_area": "A1-02",
          "nama_area": "Area A1 - Rak Sedang Updated",
          "koordinat_x": "50.00",
          "koordinat_y": "20.00",
          "panjang": "10.00",
          "lebar": "8.00",
          "tinggi": "4.00",
          "kapasitas": "400.00",
          "kapasitas_terpakai": "0.00",
          "tersedia": true,
          "jenis_area": "lantai"
        },
        "koordinat_x_spesifik": "55.480000",
        "koordinat_y_spesifik": "27.220000",
        "alasan": "Optimasi SA: Dokumen Kontrak di Area A1 - Rak Sedang Updated",
        "confidence_score": "0.8500",
        "algoritma": "Simulated Annealing",
        "prioritas": "sedang",
        "status": "menunggu",
        "catatan": null,
        "disetujui_oleh": null,
        "tanggal_persetujuan": null,
        "created_at": "2025-10-18T20:22:12.000000Z",
        "updated_at": "2025-10-18T20:22:12.000000Z",
        "barang": {
          "id": 3,
          "kode_barang": "DOK-001",
          "nama_barang": "Dokumen Kontrak",
          "kategori_barang_id": 3,
          "panjang": "25.00",
          "lebar": "18.00",
          "tinggi": "2.00",
          "berat": "0.30",
          "mudah_pecah": false,
          "prioritas": "sedang",
          "deskripsi": "Dokumen penting kontrak bisnis",
          "barcode": "1234567890125",
          "aktif": true
        },
        "log_optimasi": {
          "id": 16,
          "algoritma": "Simulated Annealing",
          "status": "selesai",
          "waktu_mulai": "2025-10-18T13:22:12.000000Z",
          "waktu_selesai": "2025-10-18T20:22:12.000000Z"
        }
      }
    ],
    "first_page_url": "http://127.0.0.1:8000/api/rekomendasi-penempatan?page=1",
    "from": 1,
    "last_page": 2,
    "last_page_url": "http://127.0.0.1:8000/api/rekomendasi-penempatan?page=2",
    "links": [
      {"url": null, "label": "&laquo; Previous", "page": null, "active": false},
      {"url": "http://127.0.0.1:8000/api/rekomendasi-penempatan?page=1", "label": "1", "page": 1, "active": true},
      {"url": "http://127.0.0.1:8000/api/rekomendasi-penempatan?page=2", "label": "2", "page": 2, "active": false},
      {"url": "http://127.0.0.1:8000/api/rekomendasi-penempatan?page=2", "label": "Next &raquo;", "page": 2, "active": false}
    ],
    "next_page_url": "http://127.0.0.1:8000/api/rekomendasi-penempatan?page=2",
    "path": "http://127.0.0.1:8000/api/rekomendasi-penempatan",
    "per_page": 3,
    "prev_page_url": null,
    "to": 3,
    "total": 6
  }
}
```

---

## ✅ 4. POST /api/rekomendasi-penempatan/bulk-approve

### Request Body:
```json
{
  "rekomendasi_ids": [40, 41, 42, 43, 44, 45],
  "catatan": "Approved for implementation"
}
```

### Response:
```json
{
  "status": "success",
  "message": "6 rekomendasi berhasil disetujui",
  "data": {
    "approved_count": 6,
    "catatan": "Approved for implementation",
    "tanggal_persetujuan": "2025-10-18T20:25:30.000000Z"
  }
}
```

---

## 📈 5. GET /api/rekomendasi-penempatan/statistics

### Response:
```json
{
  "status": "success",
  "message": "Statistik rekomendasi berhasil diambil",
  "data": {
    "total_rekomendasi": 42,
    "menunggu": 15,
    "disetujui": 20,
    "ditolak": 5,
    "diimplementasi": 2,
    "tingkat_persetujuan": 47.62,
    "rekomendasi_per_prioritas": [
      {"prioritas": "tinggi", "total": 15},
      {"prioritas": "sedang", "total": 20},
      {"prioritas": "rendah", "total": 7}
    ],
    "rekomendasi_per_algoritma": [
      {"algoritma": "Simulated Annealing", "total": 42}
    ]
  }
}
```

---

## 📚 6. GET /api/log-optimasi

### Query Parameters:
```
?status=selesai&per_page=10&page=1
```

### Response:
```json
{
  "status": "success",
  "message": "Log optimasi berhasil diambil",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 16,
        "algoritma": "Simulated Annealing",
        "parameter_optimasi": "{\"gudang_ids\":[1],\"barang_ids\":[1,2,3,4,5,6]}",
        "target_optimasi": "Test full optimization synchronous",
        "estimasi_waktu": 300,
        "status": "selesai",
        "waktu_mulai": "2025-10-18T13:22:12.000000Z",
        "waktu_selesai": "2025-10-18T20:22:12.000000Z",
        "hasil_optimasi": {
          "algorithm": "Simulated Annealing",
          "final_cost": 15147.807321860531,
          "total_items": 6,
          "areas_utilized": 4,
          "execution_time": 0.1
        },
        "dibuat_oleh": 1,
        "created_at": "2025-10-18T13:22:12.000000Z",
        "updated_at": "2025-10-18T20:22:12.000000Z"
      }
    ],
    "total": 12,
    "per_page": 10,
    "current_page": 1,
    "last_page": 2
  }
}
```

---

## 🔍 7. GET /api/log-optimasi/{id}

### Response:
```json
{
  "status": "success",
  "message": "Detail log optimasi berhasil diambil",
  "data": {
    "id": 16,
    "algoritma": "Simulated Annealing",
    "parameter_optimasi": "{\"gudang_ids\":[1],\"barang_ids\":[1,2,3,4,5,6]}",
    "target_optimasi": "Test full optimization synchronous",
    "estimasi_waktu": 300,
    "status": "selesai",
    "waktu_mulai": "2025-10-18T13:22:12.000000Z",
    "waktu_selesai": "2025-10-18T20:22:12.000000Z",
    "hasil_optimasi": {
      "algorithm": "Simulated Annealing",
      "final_cost": 15147.807321860531,
      "total_items": 6,
      "areas_utilized": 4,
      "execution_time": 0.1
    },
    "metrik_hasil": null,
    "log_error": null,
    "dibuat_oleh": 1,
    "rekomendasi_penempatan": [
      {
        "id": 40,
        "barang_id": 3,
        "area_gudang_rekomendasi": 2,
        "koordinat_x_spesifik": "55.480000",
        "koordinat_y_spesifik": "27.220000",
        "status": "menunggu",
        "barang": {
          "id": 3,
          "kode_barang": "DOK-001",
          "nama_barang": "Dokumen Kontrak"
        },
        "area_gudang": {
          "id": 2,
          "kode_area": "A1-02", 
          "nama_area": "Area A1 - Rak Sedang Updated"
        }
      }
    ]
  }
}
```

---

## 🏠 8. GET /api/gudang

### Response:
```json
{
  "success": true,
  "message": "Daftar gudang berhasil diambil",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "nama_gudang": "Gudang Utama PT. NCS Bandung",
        "alamat": "Jl. Mutiara No.2 A, Cijagra, Kec. Lengkong, Kota Bandung, Jawa Barat",
        "total_kapasitas": "12000.00",
        "kapasitas_terpakai": "0.00",
        "panjang": "50.00",
        "lebar": "30.00",
        "tinggi": "8.00",
        "aktif": true,
        "area_gudang": [
          {
            "id": 1,
            "gudang_id": 1,
            "kode_area": "A1-01",
            "nama_area": "Area A1 - Rak Tinggi",
            "koordinat_x": "5.00",
            "koordinat_y": "5.00", 
            "panjang": "10.00",
            "lebar": "8.00",
            "tinggi": "6.00",
            "kapasitas": "480.00",
            "kapasitas_terpakai": "0.00",
            "tersedia": true,
            "jenis_area": "rak"
          }
        ]
      }
    ]
  }
}
```

---

## 📦 9. GET /api/barang

### Response:
```json
{
  "success": true,
  "message": "Daftar barang berhasil diambil",
  "data": {
    "data": [
      {
        "id": 1,
        "kode_barang": "ELK-001",
        "nama_barang": "Laptop Gaming ASUS ROG",
        "kategori_barang_id": 1,
        "panjang": "35.00",
        "lebar": "25.00",
        "tinggi": "5.00",
        "berat": "2.50",
        "mudah_pecah": true,
        "prioritas": "tinggi",
        "deskripsi": "Laptop gaming high-end",
        "barcode": "1234567890123",
        "aktif": true,
        "volume": 0.004375,
        "total_stok": 0,
        "dimensi": "35.00 x 25.00 x 5.00 cm",
        "prioritas_text": "Tinggi",
        "status_text": "Aktif",
        "mudah_pecah_text": "Ya",
        "kategori_barang": {
          "id": 1,
          "nama_kategori": "Elektronik",
          "kode_kategori": "ELK",
          "deskripsi": "Peralatan elektronik dan gadget"
        }
      }
    ]
  }
}
```

---

## 🎯 10. GET /api/optimization/algorithms

### Response:
```json
{
  "status": "success",
  "message": "Informasi algoritma optimasi ruang gudang tersedia",
  "data": [
    {
      "name": "Simulated Annealing",
      "description": "Algoritma optimasi metaheuristik yang terinspirasi dari proses pendinginan logam. Efektif untuk masalah optimasi kompleks dengan ruang pencarian yang luas.",
      "pros": [
        "Dapat escape dari local optimum",
        "Fleksibel untuk berbagai jenis masalah",
        "Tidak memerlukan gradient/turunan",
        "Cocok untuk masalah diskrit dan kontinyu"
      ],
      "cons": [
        "Membutuhkan tuning parameter yang cermat",
        "Waktu komputasi relatif lama",
        "Tidak menjamin solusi optimal global"
      ],
      "complexity": "Medium-High",
      "parameters": {
        "temperature_initial": {
          "type": "float",
          "default": 1000.0,
          "min": 100,
          "max": 5000,
          "description": "Suhu awal algoritma"
        },
        "cooling_rate": {
          "type": "float", 
          "default": 0.95,
          "min": 0.8,
          "max": 0.99,
          "description": "Laju pendinginan"
        },
        "max_iterations": {
          "type": "integer",
          "default": 1000,
          "min": 100,
          "max": 10000,
          "description": "Maksimum iterasi"
        }
      },
      "use_cases": [
        "Optimasi penempatan barang di ruang gudang",
        "Minimalisir ruang kosong yang tidak termanfaatkan", 
        "Balanced antara efisiensi ruang dan aksesibilitas",
        "Mengakomodasi berbagai jenis dan prioritas barang"
      ],
      "input_data": {
        "gudang_data": "Data gudang dengan dimensi, area, dan kapasitas penyimpanan",
        "barang_data": "Data barang dengan dimensi, berat, dan kategori",
        "prioritas_optimasi": "Fokus optimasi: utilisasi ruang, aksesibilitas, atau seimbang",
        "target_utilisasi": "Target persentase penggunaan ruang gudang (50-100%)"
      },
      "output_results": {
        "penempatan_optimal": "Rekomendasi penempatan barang per area gudang",
        "tingkat_utilisasi": "Persentase penggunaan ruang yang dicapai",
        "efisiensi_ruang": "Analisis efisiensi penggunaan ruang kosong",
        "laporan_optimasi": "Laporan lengkap hasil optimasi"
      },
      "benefits": [
        "Memaksimalkan utilisasi ruang gudang yang tersedia",
        "Mengurangi area kosong yang tidak termanfaatkan",
        "Meningkatkan efisiensi operasional gudang",
        "Memberikan rekomendasi penempatan barang yang optimal",
        "Mendukung pengambilan keputusan layout gudang"
      ],
      "limitations": [
        "Memerlukan data gudang dan barang yang lengkap dan akurat",
        "Waktu komputasi bergantung pada jumlah barang dan kompleksitas gudang",
        "Hasil optimasi perlu disesuaikan dengan kondisi operasional"
      ],
      "estimated_time": "3-8 menit tergantung kompleksitas data",
      "research_focus": "Optimisasi penempatan barang di ruang gudang untuk maksimalkan efisiensi penyimpanan"
    }
  ]
}
```

---

## 🏢 11. GET /api/optimization/warehouse-state

### Response:
```json
{
  "status": "success",
  "message": "Status gudang berhasil diambil",
  "data": {
    "total_areas": 5,
    "total_items": 6,
    "total_capacity": 2002.0,
    "used_capacity": 0.0,
    "capacity_utilization": 0.0,
    "available_areas": 5,
    "areas_summary": [
      {
        "id": 1,
        "kode_area": "A1-01",
        "nama_area": "Area A1 - Rak Tinggi",
        "koordinat_x": 5.0,
        "koordinat_y": 5.0,
        "panjang": 10.0,
        "lebar": 8.0,
        "tinggi": 6.0,
        "kapasitas": 480.0,
        "kapasitas_terpakai": 0.0
      }
    ],
    "items_summary": [
      {
        "id": 1,
        "kode_barang": "ELK-001",
        "nama_barang": "Laptop Gaming ASUS ROG",
        "kategori": "Elektronik",
        "dimensi": "35.00 x 25.00 x 5.00 cm",
        "volume": 0.004375,
        "prioritas": "tinggi",
        "mudah_pecah": true
      }
    ]
  }
}
```

---

## ⚠️ Error Response Examples

### 401 Unauthorized:
```json
{
  "message": "Unauthenticated."
}
```

### 422 Validation Error:
```json
{
  "status": "error", 
  "message": "Validasi gagal",
  "errors": {
    "gudang_ids": ["The gudang ids field is required."],
    "barang_ids": ["The barang ids field is required."],
    "prioritas_optimasi": ["The selected prioritas optimasi is invalid."]
  }
}
```

### 404 Not Found:
```json
{
  "status": "error",
  "message": "Log optimasi tidak ditemukan"
}
```

### 500 Server Error:
```json
{
  "status": "error",
  "message": "Gagal menjalankan optimasi: Python script error"
}
```

---

## 📱 Mobile Implementation Notes

### 🔄 Polling untuk Real-time Updates:
Karena optimasi sekarang synchronous, tidak perlu polling. Tapi untuk monitoring background tasks lain:

```dart
Timer.periodic(Duration(seconds: 5), (timer) {
  if (mounted) {
    _checkOptimizationStatus();
  } else {
    timer.cancel();
  }
});
```

### 💾 Caching Strategy:
```dart
// Cache gudang dan barang list
final gudangCache = <String, dynamic>{};
final barangCache = <String, dynamic>{};

Future<List<Gudang>> getGudangList({bool forceRefresh = false}) async {
  const cacheKey = 'gudang_list';
  
  if (!forceRefresh && gudangCache.containsKey(cacheKey)) {
    final cached = gudangCache[cacheKey];
    if (DateTime.now().difference(cached['timestamp']).inMinutes < 30) {
      return cached['data'] as List<Gudang>;
    }
  }
  
  final data = await _apiService.getGudangList();
  gudangCache[cacheKey] = {
    'data': data,
    'timestamp': DateTime.now()
  };
  
  return data;
}
```

### 🎨 Status Color Mapping:
```dart
const statusColors = {
  'selesai': Colors.green,
  'sedang_berjalan': Colors.orange, 
  'gagal': Colors.red,
  'dibatalkan': Colors.grey,
  'menunggu': Colors.blue,
  'disetujui': Colors.teal,
  'ditolak': Colors.redAccent,
  'diimplementasi': Colors.purple
};
```

---

**📱 Semoga dokumentasi ini membantu proses development mobile app! 🚀**