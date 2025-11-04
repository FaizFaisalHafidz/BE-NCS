#!/bin/bash

# Script untuk testing API Analytics
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
echo -e "${BLUE}      Testing API Analytics NCS        ${NC}"
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
    local params=$3
    
    if [ -z "$params" ]; then
        curl -s -X $method "$BASE_URL$endpoint" \
            -H "$CONTENT_TYPE" \
            -H "$ACCEPT" \
            -H "Authorization: Bearer $TOKEN"
    else
        curl -s -X $method "$BASE_URL$endpoint?$params" \
            -H "$CONTENT_TYPE" \
            -H "$ACCEPT" \
            -H "Authorization: Bearer $TOKEN"
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

# Test 2: Dashboard Analytics
echo -e "\n${YELLOW}Test 2: Dashboard Analytics${NC}"
DASHBOARD_RESPONSE=$(make_auth_request "GET" "/analytics/dashboard")
SUCCESS=$(echo $DASHBOARD_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    TOTAL_GUDANG=$(echo $DASHBOARD_RESPONSE | jq -r '.data.total_gudang // 0')
    TOTAL_BARANG=$(echo $DASHBOARD_RESPONSE | jq -r '.data.total_barang // 0')
    KAPASITAS_TERPAKAI=$(echo $DASHBOARD_RESPONSE | jq -r '.data.kapasitas_terpakai // 0')
    print_result 0 "Dashboard data berhasil diambil"
    echo "  - Total Gudang: $TOTAL_GUDANG"
    echo "  - Total Barang: $TOTAL_BARANG"
    echo "  - Kapasitas Terpakai: ${KAPASITAS_TERPAKAI}%"
else
    print_result 1 "Gagal mengambil data dashboard"
    echo "Response: $DASHBOARD_RESPONSE"
fi

# Test 3: Dashboard Analytics dengan periode
echo -e "\n${YELLOW}Test 3: Dashboard Analytics dengan periode bulan${NC}"
DASHBOARD_PERIOD_RESPONSE=$(make_auth_request "GET" "/analytics/dashboard" "period=month")
SUCCESS=$(echo $DASHBOARD_PERIOD_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    print_result 0 "Dashboard dengan periode bulan berhasil diambil"
else
    print_result 1 "Gagal mengambil dashboard dengan periode"
    echo "Response: $DASHBOARD_PERIOD_RESPONSE"
fi

# Test 4: Utilization Analytics
echo -e "\n${YELLOW}Test 4: Utilization Analytics${NC}"
UTILIZATION_RESPONSE=$(make_auth_request "GET" "/analytics/utilization")
SUCCESS=$(echo $UTILIZATION_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    RATA_RATA_UTILISASI=$(echo $UTILIZATION_RESPONSE | jq -r '.data.rata_rata_utilisasi // 0')
    TOTAL_KAPASITAS=$(echo $UTILIZATION_RESPONSE | jq -r '.data.total_kapasitas // 0')
    print_result 0 "Data utilisasi berhasil diambil"
    echo "  - Rata-rata Utilisasi: ${RATA_RATA_UTILISASI}%"
    echo "  - Total Kapasitas: $TOTAL_KAPASITAS"
else
    print_result 1 "Gagal mengambil data utilisasi"
    echo "Response: $UTILIZATION_RESPONSE"
fi

# Test 5: Utilization per Gudang
echo -e "\n${YELLOW}Test 5: Utilization per Gudang${NC}"
UTILIZATION_GUDANG_RESPONSE=$(make_auth_request "GET" "/analytics/utilization" "per=gudang")
SUCCESS=$(echo $UTILIZATION_GUDANG_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    print_result 0 "Data utilisasi per gudang berhasil diambil"
    # Count detail utilisasi items
    DETAIL_COUNT=$(echo $UTILIZATION_GUDANG_RESPONSE | jq -r '.data.detail_utilisasi | length')
    echo "  - Jumlah detail gudang: $DETAIL_COUNT"
else
    print_result 1 "Gagal mengambil utilisasi per gudang"
    echo "Response: $UTILIZATION_GUDANG_RESPONSE"
fi

# Test 6: Utilization per Area
echo -e "\n${YELLOW}Test 6: Utilization per Area${NC}"
UTILIZATION_AREA_RESPONSE=$(make_auth_request "GET" "/analytics/utilization" "per=area")
SUCCESS=$(echo $UTILIZATION_AREA_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    print_result 0 "Data utilisasi per area berhasil diambil"
else
    print_result 1 "Gagal mengambil utilisasi per area"
    echo "Response: $UTILIZATION_AREA_RESPONSE"
fi

# Test 7: Performance Analytics
echo -e "\n${YELLOW}Test 7: Performance Analytics${NC}"
PERFORMANCE_RESPONSE=$(make_auth_request "GET" "/analytics/performance")
SUCCESS=$(echo $PERFORMANCE_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    RATA_RATA_PENEMPATAN=$(echo $PERFORMANCE_RESPONSE | jq -r '.data.rata_rata_penempatan_per_hari // 0')
    TOTAL_OPTIMASI=$(echo $PERFORMANCE_RESPONSE | jq -r '.data.total_optimasi // 0')
    TINGKAT_KEBERHASILAN=$(echo $PERFORMANCE_RESPONSE | jq -r '.data.tingkat_keberhasilan_optimasi // 0')
    print_result 0 "Data performa berhasil diambil"
    echo "  - Rata-rata Penempatan/Hari: $RATA_RATA_PENEMPATAN"
    echo "  - Total Optimasi: $TOTAL_OPTIMASI"
    echo "  - Tingkat Keberhasilan: ${TINGKAT_KEBERHASILAN}%"
else
    print_result 1 "Gagal mengambil data performa"
    echo "Response: $PERFORMANCE_RESPONSE"
fi

# Test 8: Performance dengan filter tanggal
echo -e "\n${YELLOW}Test 8: Performance dengan filter tanggal${NC}"
START_DATE="2024-01-01"
END_DATE="2024-12-31"
PERFORMANCE_DATE_RESPONSE=$(make_auth_request "GET" "/analytics/performance" "start_date=$START_DATE&end_date=$END_DATE")
SUCCESS=$(echo $PERFORMANCE_DATE_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    print_result 0 "Data performa dengan filter tanggal berhasil diambil"
else
    print_result 1 "Gagal mengambil performa dengan filter tanggal"
    echo "Response: $PERFORMANCE_DATE_RESPONSE"
fi

# Test 9: Performance dengan group by month
echo -e "\n${YELLOW}Test 9: Performance dengan group by month${NC}"
PERFORMANCE_GROUP_RESPONSE=$(make_auth_request "GET" "/analytics/performance" "group=month")
SUCCESS=$(echo $PERFORMANCE_GROUP_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    print_result 0 "Data performa dengan group month berhasil diambil"
    # Check if trend data exists
    TREND_COUNT=$(echo $PERFORMANCE_GROUP_RESPONSE | jq -r '.data.trend_performa | length')
    echo "  - Jumlah data trend: $TREND_COUNT"
else
    print_result 1 "Gagal mengambil performa dengan group month"
    echo "Response: $PERFORMANCE_GROUP_RESPONSE"
fi

# Test 10: Test validasi parameter tidak valid
echo -e "\n${YELLOW}Test 10: Test validasi parameter tidak valid${NC}"
INVALID_RESPONSE=$(make_auth_request "GET" "/analytics/utilization" "per=invalid_value")
SUCCESS=$(echo $INVALID_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "false" ]; then
    print_result 0 "Validasi parameter bekerja dengan baik"
    echo "Error: $(echo $INVALID_RESPONSE | jq -r '.message')"
else
    print_result 1 "Validasi parameter tidak bekerja"
fi

# Test 11: Test unauthorized access
echo -e "\n${YELLOW}Test 11: Test akses tanpa token${NC}"
UNAUTHORIZED_RESPONSE=$(curl -s -X GET "$BASE_URL/analytics/dashboard" \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT")

# Check if we get 401 or error message
if echo "$UNAUTHORIZED_RESPONSE" | grep -q "401\|Unauthorized\|Unauthenticated"; then
    print_result 0 "Proteksi autentikasi bekerja dengan baik"
else
    print_result 1 "Proteksi autentikasi tidak bekerja"
    echo "Response: $UNAUTHORIZED_RESPONSE"
fi

# Test 12: Comprehensive test - all endpoints
echo -e "\n${YELLOW}Test 12: Test semua endpoint secara berurutan${NC}"
ENDPOINTS=(
    "/analytics/dashboard"
    "/analytics/utilization"
    "/analytics/performance"
)

SUCCESS_COUNT=0
TOTAL_ENDPOINTS=${#ENDPOINTS[@]}

for endpoint in "${ENDPOINTS[@]}"; do
    RESPONSE=$(make_auth_request "GET" "$endpoint")
    SUCCESS=$(echo $RESPONSE | jq -r '.success')
    
    if [ "$SUCCESS" = "true" ]; then
        ((SUCCESS_COUNT++))
    fi
done

if [ $SUCCESS_COUNT -eq $TOTAL_ENDPOINTS ]; then
    print_result 0 "Semua endpoint analytics dapat diakses ($SUCCESS_COUNT/$TOTAL_ENDPOINTS)"
else
    print_result 1 "Beberapa endpoint analytics gagal ($SUCCESS_COUNT/$TOTAL_ENDPOINTS)"
fi

# Test 13: Logout
echo -e "\n${YELLOW}Test 13: Logout${NC}"
LOGOUT_RESPONSE=$(make_auth_request "POST" "/auth/logout")
SUCCESS=$(echo $LOGOUT_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    print_result 0 "Logout berhasil"
else
    print_result 1 "Logout gagal"
    echo "Response: $LOGOUT_RESPONSE"
fi

# Summary
echo -e "\n${BLUE}========================================${NC}"
echo -e "${BLUE}           Testing Selesai              ${NC}"
echo -e "${BLUE}========================================${NC}"

echo -e "\n${YELLOW}Ringkasan Endpoint Analytics:${NC}"
echo "1. GET /analytics/dashboard - Dashboard KPI utama"
echo "2. GET /analytics/utilization - Data utilisasi gudang/area"
echo "3. GET /analytics/performance - Metrik performa sistem"
echo ""
echo -e "${YELLOW}Parameter yang didukung:${NC}"
echo "Dashboard: period (today|week|month|year)"
echo "Utilization: gudang_id, per (gudang|area)"
echo "Performance: start_date, end_date, group (day|week|month)"
echo ""
echo -e "${YELLOW}Catatan:${NC}"
echo "1. Pastikan server Laravel berjalan di http://localhost:8000"
echo "2. Pastikan database telah dimigrasi dan seeded"
echo "3. Pastikan user admin@ncs.com dengan password 'password123' tersedia"
echo "4. Data analytics dihitung berdasarkan data yang ada di database"
echo ""
echo "Untuk melihat Swagger UI:"
echo "  http://localhost:8000/api/documentation"
echo ""
echo -e "${GREEN}Testing Analytics API selesai!${NC}"
