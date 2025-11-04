#!/bin/bash

# Script untuk testing API Log Aktivitas
# NCS Warehouse Management System

BASE_URL="http://localhost:8000/api"
CONTENT_TYPE="Content-Type: application/json"
ACCEPT="Accept: application/json"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Testing API Log Aktivitas NCS        ${NC}"
echo -e "${BLUE}========================================${NC}"

# Function to print test result
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ PASS${NC}: $2"
    else
        echo -e "${RED}✗ FAIL${NC}: $2"
    fi
}

# Function to make authenticated request
make_auth_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    
    if [ -z "$data" ]; then
        curl -s -X $method "$BASE_URL$endpoint" \
            -H "$CONTENT_TYPE" \
            -H "$ACCEPT" \
            -H "Authorization: Bearer $TOKEN"
    else
        curl -s -X $method "$BASE_URL$endpoint" \
            -H "$CONTENT_TYPE" \
            -H "$ACCEPT" \
            -H "Authorization: Bearer $TOKEN" \
            -d "$data"
    fi
}

# Test 1: Login to get token
echo -e "\n${YELLOW}Test 1: Login untuk mendapatkan token${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -d '{
        "email": "admin@ncs.com",
        "password": "password123"
    }')

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.token // empty')

if [ -n "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
    print_result 0 "Login berhasil, token diperoleh"
    echo "Token: ${TOKEN:0:20}..."
else
    print_result 1 "Login gagal, tidak bisa mendapatkan token"
    echo "Response: $LOGIN_RESPONSE"
    echo -e "${RED}Pastikan server berjalan dan user admin@ncs.com ada di database${NC}"
    exit 1
fi

# Test 2: Get log aktivitas list
echo -e "\n${YELLOW}Test 2: Mengambil daftar log aktivitas${NC}"
RESPONSE=$(make_auth_request "GET" "/log-aktivitas")
SUCCESS=$(echo $RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    TOTAL=$(echo $RESPONSE | jq -r '.data.total // 0')
    print_result 0 "Berhasil mengambil daftar log aktivitas (Total: $TOTAL)"
else
    print_result 1 "Gagal mengambil daftar log aktivitas"
    echo "Response: $RESPONSE"
fi

# Test 3: Create new log aktivitas
echo -e "\n${YELLOW}Test 3: Menambahkan log aktivitas baru${NC}"
CREATE_DATA='{
    "aksi": "test",
    "deskripsi": "Testing log aktivitas dari script",
    "model_type": "App\\Models\\Test",
    "model_id": 1,
    "data_baru": {
        "test_field": "test_value",
        "timestamp": "2024-01-15 10:30:00"
    }
}'

RESPONSE=$(make_auth_request "POST" "/log-aktivitas" "$CREATE_DATA")
SUCCESS=$(echo $RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    LOG_ID=$(echo $RESPONSE | jq -r '.data.id')
    print_result 0 "Berhasil menambahkan log aktivitas (ID: $LOG_ID)"
else
    print_result 1 "Gagal menambahkan log aktivitas"
    echo "Response: $RESPONSE"
fi

# Test 4: Get specific log aktivitas
if [ -n "$LOG_ID" ] && [ "$LOG_ID" != "null" ]; then
    echo -e "\n${YELLOW}Test 4: Mengambil detail log aktivitas${NC}"
    RESPONSE=$(make_auth_request "GET" "/log-aktivitas/$LOG_ID")
    SUCCESS=$(echo $RESPONSE | jq -r '.success')
    
    if [ "$SUCCESS" = "true" ]; then
        AKSI=$(echo $RESPONSE | jq -r '.data.aksi')
        print_result 0 "Berhasil mengambil detail log aktivitas (Aksi: $AKSI)"
    else
        print_result 1 "Gagal mengambil detail log aktivitas"
        echo "Response: $RESPONSE"
    fi
fi

# Test 5: Get statistics
echo -e "\n${YELLOW}Test 5: Mengambil statistik log aktivitas${NC}"
RESPONSE=$(make_auth_request "GET" "/log-aktivitas/statistics")
SUCCESS=$(echo $RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    TOTAL_AKTIVITAS=$(echo $RESPONSE | jq -r '.data.total_aktivitas // 0')
    AKTIVITAS_HARI_INI=$(echo $RESPONSE | jq -r '.data.aktivitas_hari_ini // 0')
    print_result 0 "Berhasil mengambil statistik (Total: $TOTAL_AKTIVITAS, Hari ini: $AKTIVITAS_HARI_INI)"
else
    print_result 1 "Gagal mengambil statistik log aktivitas"
    echo "Response: $RESPONSE"
fi

# Test 6: Get my activities
echo -e "\n${YELLOW}Test 6: Mengambil aktivitas user saat ini${NC}"
RESPONSE=$(make_auth_request "GET" "/log-aktivitas/my-activities")
SUCCESS=$(echo $RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    TOTAL_MY=$(echo $RESPONSE | jq -r '.data.total // 0')
    print_result 0 "Berhasil mengambil aktivitas user (Total: $TOTAL_MY)"
else
    print_result 1 "Gagal mengambil aktivitas user"
    echo "Response: $RESPONSE"
fi

# Test 7: Filter log aktivitas
echo -e "\n${YELLOW}Test 7: Filter log aktivitas berdasarkan aksi${NC}"
RESPONSE=$(make_auth_request "GET" "/log-aktivitas?aksi=test&per_page=5")
SUCCESS=$(echo $RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    TOTAL_FILTERED=$(echo $RESPONSE | jq -r '.data.total // 0')
    print_result 0 "Berhasil filter log aktivitas (Total filtered: $TOTAL_FILTERED)"
else
    print_result 1 "Gagal filter log aktivitas"
    echo "Response: $RESPONSE"
fi

# Test 8: Export log aktivitas
echo -e "\n${YELLOW}Test 8: Export log aktivitas${NC}"
EXPORT_DATA='{
    "format": "json",
    "start_date": "2024-01-01",
    "end_date": "2024-12-31"
}'

RESPONSE=$(make_auth_request "POST" "/log-aktivitas/export" "$EXPORT_DATA")
SUCCESS=$(echo $RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    DOWNLOAD_URL=$(echo $RESPONSE | jq -r '.data.download_url // "N/A"')
    TOTAL_RECORDS=$(echo $RESPONSE | jq -r '.data.total_records // 0')
    print_result 0 "Berhasil export log aktivitas (Records: $TOTAL_RECORDS)"
    echo "Download URL: $DOWNLOAD_URL"
else
    print_result 1 "Gagal export log aktivitas"
    echo "Response: $RESPONSE"
fi

# Test 9: Test with invalid data
echo -e "\n${YELLOW}Test 9: Test validasi dengan data tidak valid${NC}"
INVALID_DATA='{
    "aksi": "",
    "deskripsi": ""
}'

RESPONSE=$(make_auth_request "POST" "/log-aktivitas" "$INVALID_DATA")
SUCCESS=$(echo $RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "false" ]; then
    print_result 0 "Validasi bekerja dengan baik (menolak data tidak valid)"
else
    print_result 1 "Validasi tidak bekerja (menerima data tidak valid)"
    echo "Response: $RESPONSE"
fi

# Test 10: Test pagination
echo -e "\n${YELLOW}Test 10: Test pagination${NC}"
RESPONSE=$(make_auth_request "GET" "/log-aktivitas?page=1&per_page=5")
SUCCESS=$(echo $RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    CURRENT_PAGE=$(echo $RESPONSE | jq -r '.data.current_page // 0')
    PER_PAGE=$(echo $RESPONSE | jq -r '.data.per_page // 0')
    print_result 0 "Pagination bekerja (Page: $CURRENT_PAGE, Per page: $PER_PAGE)"
else
    print_result 1 "Pagination tidak bekerja"
    echo "Response: $RESPONSE"
fi

# Test 11: Cleanup old logs (optional - be careful in production)
echo -e "\n${YELLOW}Test 11: Test cleanup logs lama (simulasi)${NC}"
CLEANUP_DATA='{
    "older_than_days": 365
}'

# Uncomment this for actual cleanup test
# RESPONSE=$(make_auth_request "POST" "/log-aktivitas/cleanup" "$CLEANUP_DATA")
# SUCCESS=$(echo $RESPONSE | jq -r '.success')

# For simulation, just check endpoint exists
RESPONSE=$(curl -s -X POST "$BASE_URL/log-aktivitas/cleanup" \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -H "Authorization: Bearer invalid_token" \
    -d "$CLEANUP_DATA")

# If we get 401 (unauthorized), endpoint exists but needs valid token
if echo "$RESPONSE" | grep -q "401\|Unauthorized\|invalid"; then
    print_result 0 "Cleanup endpoint tersedia (perlu token valid)"
else
    print_result 1 "Cleanup endpoint tidak ditemukan"
fi

# Test 12: Logout
echo -e "\n${YELLOW}Test 12: Logout${NC}"
RESPONSE=$(make_auth_request "POST" "/auth/logout")
SUCCESS=$(echo $RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    print_result 0 "Logout berhasil"
else
    print_result 1 "Logout gagal"
    echo "Response: $RESPONSE"
fi

# Summary
echo -e "\n${BLUE}========================================${NC}"
echo -e "${BLUE}           Testing Selesai              ${NC}"
echo -e "${BLUE}========================================${NC}"

echo -e "\n${YELLOW}Catatan:${NC}"
echo "1. Pastikan server Laravel berjalan di http://localhost:8000"
echo "2. Pastikan database telah dimigrasi dan seeded"
echo "3. Pastikan user admin@ncs.com dengan password 'password123' tersedia"
echo "4. Untuk cleanup test, uncomment baris yang sesuai jika diperlukan"
echo ""
echo "Untuk melihat log aktivitas yang baru dibuat, cek:"
echo "  GET $BASE_URL/log-aktivitas"
echo ""
echo -e "${GREEN}Testing selesai!${NC}"