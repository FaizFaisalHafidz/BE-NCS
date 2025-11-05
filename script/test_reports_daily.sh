#!/bin/bash

# Test script untuk laporan harian
echo "=== Testing Daily Report API ==="
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

echo "🔄 Testing daily report endpoint..."
echo "URL: $BASE_URL/reports/daily"
echo ""

# Test daily report
response=$(curl -s -w "\n%{http_code}" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    "$BASE_URL/reports/daily")

# Extract response body and status code
http_code=$(echo "$response" | tail -n1)
response_body=$(echo "$response" | head -n -1)

echo "📋 Response Status: $http_code"
echo "📄 Response Body:"
echo "$response_body" | jq '.' 2>/dev/null || echo "$response_body"
echo ""

# Test with specific date
echo "🔄 Testing daily report with specific date..."
echo "URL: $BASE_URL/reports/daily?date=2024-01-15"
echo ""

response_with_date=$(curl -s -w "\n%{http_code}" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    "$BASE_URL/reports/daily?date=2024-01-15")

# Extract response body and status code
http_code_date=$(echo "$response_with_date" | tail -n1)
response_body_date=$(echo "$response_with_date" | head -n -1)

echo "📋 Response Status: $http_code_date"
echo "📄 Response Body:"
echo "$response_body_date" | jq '.' 2>/dev/null || echo "$response_body_date"
echo ""

# Summary
echo "=== Summary ==="
if [ "$http_code" = "200" ]; then
    echo "✅ Daily report (today): SUCCESS"
else
    echo "❌ Daily report (today): FAILED ($http_code)"
fi

if [ "$http_code_date" = "200" ]; then
    echo "✅ Daily report (specific date): SUCCESS"
else
    echo "❌ Daily report (specific date): FAILED ($http_code_date)"
fi

echo ""
echo "=== Test completed ==="