# API Update Profile & Change Password - NCS Warehouse System

## Deskripsi
API untuk mengelola update profile pengguna dan mengubah password dengan keamanan yang terjamin.

## Base URL
```
/api/auth
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

### 1. PUT /api/auth/update-profile
**Deskripsi:** Mengupdate profil pengguna yang sedang login.

**Request Body:**
```json
{
    "name": "John Doe Updated",
    "email": "john.updated@example.com",
    "phone": "081234567890",
    "address": "Jl. Sudirman No. 123, Jakarta"
}
```

**Validation Rules:**
- `name`: required, string, max 255 characters
- `email`: required, valid email, unique (kecuali email user saat ini)
- `phone`: optional, string, max 20 characters
- `address`: optional, string, max 500 characters

**Response Success (200):**
```json
{
    "success": true,
    "message": "Profile berhasil diupdate",
    "data": {
        "id": 1,
        "name": "John Doe Updated",
        "email": "john.updated@example.com",
        "phone": "081234567890",
        "address": "Jl. Sudirman No. 123, Jakarta",
        "aktif": true,
        "role": "admin",
        "status_text": "Aktif",
        "email_verified_at": "2024-01-15T10:30:00.000000Z",
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T15:45:00.000000Z"
    }
}
```

**Response Validation Error (422):**
```json
{
    "success": false,
    "message": "Validasi gagal",
    "errors": {
        "email": ["Email sudah digunakan oleh pengguna lain"],
        "name": ["Nama wajib diisi"]
    }
}
```

### 2. PUT /api/auth/change-password
**Deskripsi:** Mengubah password pengguna yang sedang login.

**Request Body:**
```json
{
    "current_password": "oldpassword123",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123",
    "revoke_all_tokens": false
}
```

**Validation Rules:**
- `current_password`: required, string
- `new_password`: required, string, min 8 characters, confirmed
- `new_password_confirmation`: required, string, harus sama dengan new_password
- `revoke_all_tokens`: optional, boolean (default: false)

**Response Success (200):**
```json
{
    "success": true,
    "message": "Password berhasil diubah"
}
```

**Response Success dengan Token Revocation (200):**
```json
{
    "success": true,
    "message": "Password berhasil diubah. Silakan login kembali",
    "revoked_tokens": true
}
```

**Response Wrong Current Password (400):**
```json
{
    "success": false,
    "message": "Password lama tidak cocok"
}
```

**Response Validation Error (422):**
```json
{
    "success": false,
    "message": "Validasi gagal",
    "errors": {
        "new_password": ["Password minimal 8 karakter"],
        "new_password_confirmation": ["Konfirmasi password tidak cocok"]
    }
}
```

## Error Responses

### 401 Unauthorized
```json
{
    "success": false,
    "message": "Token tidak valid atau sudah expired"
}
```

### 500 Internal Server Error
```json
{
    "success": false,
    "message": "Terjadi kesalahan sistem",
    "error": "Error details..." // Hanya muncul jika APP_DEBUG=true
}
```

## Contoh Penggunaan

### 1. Update Profile
```bash
curl -X PUT "http://localhost:8000/api/auth/update-profile" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "John Doe Updated",
    "email": "john.updated@example.com",
    "phone": "081234567890",
    "address": "Jl. Sudirman No. 123, Jakarta"
  }'
```

### 2. Change Password
```bash
curl -X PUT "http://localhost:8000/api/auth/change-password" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "current_password": "oldpassword123",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
  }'
```

### 3. Change Password dengan Token Revocation
```bash
curl -X PUT "http://localhost:8000/api/auth/change-password" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "current_password": "oldpassword123",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123",
    "revoke_all_tokens": true
  }'
```

## Security Features

### 1. Logging Aktivitas
Semua aksi update profile dan change password akan otomatis tercatat dalam log aktivitas dengan detail:

**Update Profile:**
- Aksi: `update`
- Deskripsi: "Mengupdate profile pengguna"
- Data lama dan baru disimpan untuk audit trail

**Change Password:**
- Aksi: `change_password_success` untuk berhasil
- Aksi: `change_password_failed` untuk gagal
- Aksi: `change_password_error` untuk error sistem
- IP address dan user agent dicatat

### 2. Password Security
- Verifikasi password lama sebelum update
- Password baru di-hash menggunakan bcrypt
- Minimum 8 karakter untuk password baru
- Konfirmasi password wajib

### 3. Token Management
- Opsi untuk revoke semua token setelah change password
- Berguna untuk logout dari semua device setelah change password

## Tips Penggunaan

### 1. Update Profile
- Email harus unique di sistem
- Field phone dan address bersifat opsional
- Data lama akan disimpan dalam log untuk audit trail

### 2. Change Password
- Selalu verifikasi password lama
- Gunakan password yang kuat (min 8 karakter)
- Pertimbangkan menggunakan `revoke_all_tokens: true` untuk keamanan maksimal

### 3. Error Handling
- Tangani error validasi dengan baik di frontend
- Berikan feedback yang jelas kepada user
- Log error untuk debugging jika diperlukan

### 4. Mobile App Integration
```javascript
// Contoh untuk React Native / JavaScript
const updateProfile = async (profileData, token) => {
  try {
    const response = await fetch('http://localhost:8000/api/auth/update-profile', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(profileData)
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Update berhasil
      console.log('Profile updated:', result.data);
    } else {
      // Handle validation errors
      console.log('Validation errors:', result.errors);
    }
  } catch (error) {
    console.error('Network error:', error);
  }
};

const changePassword = async (passwordData, token) => {
  try {
    const response = await fetch('http://localhost:8000/api/auth/change-password', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(passwordData)
    });
    
    const result = await response.json();
    
    if (result.success) {
      if (result.revoked_tokens) {
        // Redirect to login karena semua token di-revoke
        redirectToLogin();
      } else {
        // Password changed successfully
        showSuccessMessage('Password berhasil diubah');
      }
    } else {
      // Handle errors
      if (response.status === 400) {
        showErrorMessage('Password lama tidak cocok');
      } else {
        showErrorMessage(result.message);
      }
    }
  } catch (error) {
    console.error('Network error:', error);
  }
};
```

## Database Schema Changes

Jika belum ada field `phone` dan `address` di tabel users, tambahkan migration:

```php
// database/migrations/xxxx_add_profile_fields_to_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address']);
        });
    }
}
```

## Testing

Gunakan script testing berikut untuk menguji endpoint:

```bash
#!/bin/bash

# Test Update Profile & Change Password
BASE_URL="http://localhost:8000/api"

# Login first
TOKEN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@ncs.com", "password": "password123"}' \
  | jq -r '.data.token')

# Test Update Profile
curl -X PUT "$BASE_URL/auth/update-profile" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Admin Updated",
    "email": "admin@ncs.com",
    "phone": "081234567890",
    "address": "Jakarta"
  }'

# Test Change Password
curl -X PUT "$BASE_URL/auth/change-password" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "current_password": "password123",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
  }'
```