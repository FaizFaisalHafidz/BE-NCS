#!/bin/bash

# Test script untuk semua endpoint laporan
echo "=== Testing All Reports API Endpoints ==="
echo ""

# Variables
BASE_URL="http://localhost:8000/api"
TOKEN_FILE="token.txt"

# Check if token file exists
if [ ! -f "$TOKEN_FILE" ]; then
    echo "Error: File token.txt tidak ditemukan!"
    echo "Silakan login terlebih dahulu untuk mendapatkan token."
    exit 1
fi

# Read token from file
TOKEN=$(cat "$TOKEN_FILE")

# Function to test endpoint
test_endpoint() {
    local endpoint="$1"
    local description="$2"
    
    echo "🔄 Testing $description..."
    echo "URL: $BASE_URL$endpoint"
    echo ""
    
    response=$(curl -s -w "\n%{http_code}" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        "$BASE_URL$endpoint")
    
    # Extract response body and status code
    http_code=$(echo "$response" | tail -n1)
    response_body=$(echo "$response" | head -n -1)
    
    echo "📋 Response Status: $http_code"
    echo "📄 Response Body:"
    echo "$response_body" | jq '.' 2>/dev/null || echo "$response_body"
    echo ""
    echo "----------------------------------------"
    echo ""
    
    return $http_code
}

# Test all report endpoints
declare -a endpoints=(
    "/reports/daily|Daily Report"
    "/reports/weekly|Weekly Report"
    "/reports/inventory|Inventory Report"
    "/reports/team-performance|Team Performance Report"
    "/reports/warehouse-capacity|Warehouse Capacity Report"
    "/reports/optimization|Optimization Report"
    "/reports/latest|Latest Reports"
)

declare -a results=()

# Test each endpoint
for item in "${endpoints[@]}"; do
    IFS='|' read -r endpoint description <<< "$item"
    test_endpoint "$endpoint" "$description"
    status=$?
    
    if [ $status -eq 200 ]; then
        results+=("✅ $description: SUCCESS")
    else
        results+=("❌ $description: FAILED ($status)")
    fi
done

echo ""
echo "=== Testing with Parameters ==="
echo ""

# Test with parameters
test_endpoint "/reports/daily?date=2024-01-15" "Daily Report with specific date"
status=$?
if [ $status -eq 200 ]; then
    results+=("✅ Daily Report (with date): SUCCESS")
else
    results+=("❌ Daily Report (with date): FAILED ($status)")
fi

test_endpoint "/reports/weekly?week_start=2024-01-08" "Weekly Report with specific week"
status=$?
if [ $status -eq 200 ]; then
    results+=("✅ Weekly Report (with week): SUCCESS")
else
    results+=("❌ Weekly Report (with week): FAILED ($status)")
fi

test_endpoint "/reports/inventory?warehouse_id=1" "Inventory Report with warehouse filter"
status=$?
if [ $status -eq 200 ]; then
    results+=("✅ Inventory Report (with filter): SUCCESS")
else
    results+=("❌ Inventory Report (with filter): FAILED ($status)")
fi

test_endpoint "/reports/team-performance?period=week" "Team Performance with week period"
status=$?
if [ $status -eq 200 ]; then
    results+=("✅ Team Performance (week): SUCCESS")
else
    results+=("❌ Team Performance (week): FAILED ($status)")
fi

test_endpoint "/reports/optimization?period=quarter" "Optimization Report with quarter period"
status=$?
if [ $status -eq 200 ]; then
    results+=("✅ Optimization Report (quarter): SUCCESS")
else
    results+=("❌ Optimization Report (quarter): FAILED ($status)")
fi

test_endpoint "/reports/latest?limit=10" "Latest Reports with custom limit"
status=$?
if [ $status -eq 200 ]; then
    results+=("✅ Latest Reports (custom limit): SUCCESS")
else
    results+=("❌ Latest Reports (custom limit): FAILED ($status)")
fi

echo ""
echo "=== Summary ==="
for result in "${results[@]}"; do
    echo "$result"
done

echo ""
echo "=== Test completed ==="