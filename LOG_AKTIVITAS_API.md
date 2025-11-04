# API Log Aktivitas - NCS Warehouse System

## Deskripsi
API untuk mengelola log aktivitas sistem termasuk pelacakan aksi pengguna, perubahan data, dan analisis aktivitas.

## Base URL
```
/api/log-aktivitas
```

## Authentication
Semua endpoint memerlukan autentikasi menggunakan Bearer Token (Sanctum).

```
Headers:
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## Endpoints

### 1. GET /api/log-aktivitas
**Deskripsi:** Mengambil daftar log aktivitas dengan filtering dan pagination.

**Parameters:**
- `page` (optional): Nomor halaman (default: 1)
- `per_page` (optional): Jumlah data per halaman (default: 15, max: 100)
- `search` (optional): Pencarian berdasarkan aksi atau deskripsi
- `aksi` (optional): Filter berdasarkan jenis aksi
- `user_id` (optional): Filter berdasarkan ID user
- `start_date` (optional): Tanggal mulai (format: Y-m-d)
- `end_date` (optional): Tanggal akhir (format: Y-m-d)
- `model_type` (optional): Filter berdasarkan tipe model

**Response Success (200):**
```json
{
    "success": true,
    "message": "Data log aktivitas berhasil diambil",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "aksi": "create",
                "deskripsi": "Menambahkan barang baru",
                "user_id": 1,
                "model_type": "App\\Models\\Barang",
                "model_id": 15,
                "data_lama": null,
                "data_baru": {...},
                "timestamp": "2024-01-15 10:30:00",
                "created_at": "2024-01-15T10:30:00.000000Z",
                "updated_at": "2024-01-15T10:30:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "Admin User",
                    "email": "admin@example.com"
                },
                "formatted_date": "15 Januari 2024, 10:30",
                "time_ago": "2 jam yang lalu"
            }
        ],
        "first_page_url": "http://localhost/api/log-aktivitas?page=1",
        "from": 1,
        "last_page": 5,
        "last_page_url": "http://localhost/api/log-aktivitas?page=5",
        "links": [...],
        "next_page_url": "http://localhost/api/log-aktivitas?page=2",
        "path": "http://localhost/api/log-aktivitas",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 75
    }
}
```

### 2. POST /api/log-aktivitas
**Deskripsi:** Menambahkan log aktivitas baru.

**Request Body:**
```json
{
    "aksi": "create",
    "deskripsi": "Menambahkan barang baru dengan kode BRG001",
    "model_type": "App\\Models\\Barang",
    "model_id": 15,
    "data_lama": null,
    "data_baru": {
        "kode_barang": "BRG001",
        "nama_barang": "Laptop ASUS",
        "kategori_id": 1
    }
}
```

**Validation Rules:**
- `aksi`: required, string, max 50 characters
- `deskripsi`: required, string
- `model_type`: optional, string
- `model_id`: optional, integer
- `data_lama`: optional, array
- `data_baru`: optional, array

**Response Success (201):**
```json
{
    "success": true,
    "message": "Log aktivitas berhasil ditambahkan",
    "data": {
        "id": 1,
        "aksi": "create",
        "deskripsi": "Menambahkan barang baru dengan kode BRG001",
        "user_id": 1,
        "model_type": "App\\Models\\Barang",
        "model_id": 15,
        "data_lama": null,
        "data_baru": {...},
        "timestamp": "2024-01-15T10:30:00.000000Z",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

### 3. GET /api/log-aktivitas/{id}
**Deskripsi:** Mengambil detail log aktivitas berdasarkan ID.

**Response Success (200):**
```json
{
    "success": true,
    "message": "Detail log aktivitas berhasil diambil",
    "data": {
        "id": 1,
        "aksi": "create",
        "deskripsi": "Menambahkan barang baru",
        "user_id": 1,
        "model_type": "App\\Models\\Barang",
        "model_id": 15,
        "data_lama": null,
        "data_baru": {...},
        "timestamp": "2024-01-15 10:30:00",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z",
        "user": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        },
        "formatted_date": "15 Januari 2024, 10:30",
        "time_ago": "2 jam yang lalu"
    }
}
```

### 4. GET /api/log-aktivitas/statistics
**Deskripsi:** Mengambil statistik log aktivitas.

**Parameters:**
- `start_date` (optional): Tanggal mulai (format: Y-m-d)
- `end_date` (optional): Tanggal akhir (format: Y-m-d)

**Response Success (200):**
```json
{
    "success": true,
    "message": "Statistik log aktivitas berhasil diambil",
    "data": {
        "total_aktivitas": 150,
        "aktivitas_hari_ini": 25,
        "aktivitas_minggu_ini": 89,
        "aktivitas_bulan_ini": 134,
        "top_aksi": [
            {
                "aksi": "create",
                "jumlah": 45
            },
            {
                "aksi": "update",
                "jumlah": 38
            },
            {
                "aksi": "delete",
                "jumlah": 12
            }
        ],
        "top_users": [
            {
                "user_id": 1,
                "name": "Admin User",
                "jumlah": 89
            },
            {
                "user_id": 2,
                "name": "Operator",
                "jumlah": 45
            }
        ],
        "aktivitas_per_hari": [
            {
                "tanggal": "2024-01-15",
                "jumlah": 25
            },
            {
                "tanggal": "2024-01-14",
                "jumlah": 18
            }
        ]
    }
}
```

### 5. GET /api/log-aktivitas/my-activities
**Deskripsi:** Mengambil log aktivitas milik user yang sedang login.

**Parameters:**
- `page` (optional): Nomor halaman
- `per_page` (optional): Jumlah data per halaman
- `aksi` (optional): Filter berdasarkan jenis aksi
- `start_date` (optional): Tanggal mulai
- `end_date` (optional): Tanggal akhir

**Response:** Sama seperti endpoint GET /api/log-aktivitas tetapi hanya menampilkan data milik user yang login.

### 6. POST /api/log-aktivitas/cleanup
**Deskripsi:** Membersihkan log aktivitas lama.

**Request Body:**
```json
{
    "older_than_days": 90
}
```

**Validation Rules:**
- `older_than_days`: required, integer, min 30

**Response Success (200):**
```json
{
    "success": true,
    "message": "Log aktivitas berhasil dibersihkan",
    "data": {
        "deleted_count": 45,
        "cutoff_date": "2023-10-15 00:00:00"
    }
}
```

### 7. POST /api/log-aktivitas/export
**Deskripsi:** Mengekspor log aktivitas ke berbagai format.

**Request Body:**
```json
{
    "format": "csv",
    "start_date": "2024-01-01",
    "end_date": "2024-01-31",
    "aksi": ["create", "update"],
    "user_id": [1, 2]
}
```

**Validation Rules:**
- `format`: required, in: csv,excel,json
- `start_date`: optional, date
- `end_date`: optional, date
- `aksi`: optional, array
- `user_id`: optional, array of integers

**Response Success (200):**
```json
{
    "success": true,
    "message": "Data log aktivitas berhasil diekspor",
    "data": {
        "download_url": "http://localhost/storage/exports/log_aktivitas_20240115.csv",
        "file_size": "2.5 MB",
        "total_records": 1250,
        "generated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

## Error Responses

### 400 Bad Request
```json
{
    "success": false,
    "message": "Request tidak valid",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

### 401 Unauthorized
```json
{
    "success": false,
    "message": "Token tidak valid atau sudah expired"
}
```

### 403 Forbidden
```json
{
    "success": false,
    "message": "Tidak memiliki akses untuk resource ini"
}
```

### 404 Not Found
```json
{
    "success": false,
    "message": "Log aktivitas tidak ditemukan"
}
```

### 422 Validation Error
```json
{
    "success": false,
    "message": "Validasi gagal",
    "errors": {
        "aksi": ["Field aksi wajib diisi"],
        "deskripsi": ["Field deskripsi wajib diisi"]
    }
}
```

### 500 Internal Server Error
```json
{
    "success": false,
    "message": "Terjadi kesalahan pada server",
    "error": "Error details..."
}
```

## Contoh Penggunaan

### 1. Mengambil Log Aktivitas dengan Filter
```bash
curl -X GET "http://localhost/api/log-aktivitas?search=create&start_date=2024-01-01&end_date=2024-01-31&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 2. Menambah Log Aktivitas
```bash
curl -X POST "http://localhost/api/log-aktivitas" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "aksi": "create",
    "deskripsi": "Menambahkan barang baru",
    "model_type": "App\\Models\\Barang",
    "model_id": 15
  }'
```

### 3. Mengambil Statistik
```bash
curl -X GET "http://localhost/api/log-aktivitas/statistics?start_date=2024-01-01&end_date=2024-01-31" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 4. Ekspor Data
```bash
curl -X POST "http://localhost/api/log-aktivitas/export" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "format": "csv",
    "start_date": "2024-01-01",
    "end_date": "2024-01-31"
  }'
```

## Helper untuk Logging

Untuk memudahkan penggunaan, Anda dapat menggunakan method static dari model LogAktivitas:

```php
// Dalam Controller atau Service
use App\Models\LogAktivitas;

// Log aktivitas sederhana
LogAktivitas::log('create', 'Menambahkan barang baru');

// Log dengan detail model
LogAktivitas::log('update', 'Mengubah data barang', $barang, $dataLama, $dataBaru);

// Log dengan user spesifik
LogAktivitas::log('delete', 'Menghapus data user', $user, null, null, $targetUserId);
```

## Tips Penggunaan

1. **Filtering Efisien**: Gunakan parameter filter untuk mengurangi jumlah data yang diambil
2. **Pagination**: Selalu gunakan pagination untuk performa yang lebih baik
3. **Export**: Gunakan export untuk analisis data yang lebih mendalam
4. **Cleanup**: Lakukan cleanup secara berkala untuk menjaga performa database
5. **Monitoring**: Pantau statistik untuk memahami pola penggunaan sistem