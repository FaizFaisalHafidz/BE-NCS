#!/bin/bash

# Script untuk testing API Update Profile & Change Password
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
echo -e "${BLUE}  Testing Update Profile & Password     ${NC}"
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

# Test 2: Get current user profile
echo -e "\n${YELLOW}Test 2: Mengambil profil user saat ini${NC}"
CURRENT_PROFILE=$(make_auth_request "GET" "/auth/me")
SUCCESS=$(echo $CURRENT_PROFILE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    CURRENT_NAME=$(echo $CURRENT_PROFILE | jq -r '.data.name')
    CURRENT_EMAIL=$(echo $CURRENT_PROFILE | jq -r '.data.email')
    print_result 0 "Berhasil mengambil profil ($CURRENT_NAME - $CURRENT_EMAIL)"
else
    print_result 1 "Gagal mengambil profil user"
    echo "Response: $CURRENT_PROFILE"
fi

# Test 3: Update profile with valid data
echo -e "\n${YELLOW}Test 3: Update profil dengan data valid${NC}"
UPDATE_DATA='{
    "name": "Admin Updated Test",
    "email": "admin@ncs.com",
    "phone": "081234567890",
    "address": "Jl. Testing No. 123, Jakarta"
}'

UPDATE_RESPONSE=$(make_auth_request "PUT" "/auth/update-profile" "$UPDATE_DATA")
SUCCESS=$(echo $UPDATE_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    UPDATED_NAME=$(echo $UPDATE_RESPONSE | jq -r '.data.name')
    UPDATED_PHONE=$(echo $UPDATE_RESPONSE | jq -r '.data.phone')
    print_result 0 "Berhasil update profil ($UPDATED_NAME, Phone: $UPDATED_PHONE)"
else
    print_result 1 "Gagal update profil"
    echo "Response: $UPDATE_RESPONSE"
fi

# Test 4: Update profile with invalid email (duplicate)
echo -e "\n${YELLOW}Test 4: Test validasi email duplikat${NC}"
INVALID_EMAIL_DATA='{
    "name": "Admin Test",
    "email": "duplicate@example.com",
    "phone": "081234567890"
}'

# First create a user with this email (simulate)
INVALID_RESPONSE=$(make_auth_request "PUT" "/auth/update-profile" "$INVALID_EMAIL_DATA")
SUCCESS=$(echo $INVALID_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "false" ]; then
    ERRORS=$(echo $INVALID_RESPONSE | jq -r '.errors.email[0] // "No email error"')
    print_result 0 "Validasi email bekerja dengan baik"
    echo "Error: $ERRORS"
else
    print_result 0 "Update berhasil (email belum ada duplikat di sistem)"
fi

# Test 5: Update profile with invalid data
echo -e "\n${YELLOW}Test 5: Test validasi data tidak valid${NC}"
INVALID_DATA='{
    "name": "",
    "email": "invalid-email"
}'

INVALID_RESPONSE=$(make_auth_request "PUT" "/auth/update-profile" "$INVALID_DATA")
SUCCESS=$(echo $INVALID_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "false" ]; then
    print_result 0 "Validasi data tidak valid bekerja dengan baik"
    echo "Errors: $(echo $INVALID_RESPONSE | jq -r '.errors')"
else
    print_result 1 "Validasi tidak bekerja (menerima data tidak valid)"
fi

# Test 6: Change password with correct current password
echo -e "\n${YELLOW}Test 6: Ubah password dengan password lama yang benar${NC}"
CHANGE_PASSWORD_DATA='{
    "current_password": "password123",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}'

PASSWORD_RESPONSE=$(make_auth_request "PUT" "/auth/change-password" "$CHANGE_PASSWORD_DATA")
SUCCESS=$(echo $PASSWORD_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    print_result 0 "Password berhasil diubah"
    # Update our test password for next tests
    CURRENT_PASSWORD="newpassword123"
else
    print_result 1 "Gagal mengubah password"
    echo "Response: $PASSWORD_RESPONSE"
    CURRENT_PASSWORD="password123"  # Keep old password if change failed
fi

# Test 7: Try to change password with wrong current password
echo -e "\n${YELLOW}Test 7: Test dengan password lama yang salah${NC}"
WRONG_PASSWORD_DATA='{
    "current_password": "wrongpassword",
    "new_password": "anotherpassword123",
    "new_password_confirmation": "anotherpassword123"
}'

WRONG_RESPONSE=$(make_auth_request "PUT" "/auth/change-password" "$WRONG_PASSWORD_DATA")
SUCCESS=$(echo $WRONG_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "false" ]; then
    MESSAGE=$(echo $WRONG_RESPONSE | jq -r '.message')
    print_result 0 "Validasi password lama bekerja dengan baik"
    echo "Message: $MESSAGE"
else
    print_result 1 "Validasi password lama tidak bekerja"
fi

# Test 8: Test password validation (too short, no confirmation)
echo -e "\n${YELLOW}Test 8: Test validasi password (terlalu pendek)${NC}"
SHORT_PASSWORD_DATA='{
    "current_password": "'$CURRENT_PASSWORD'",
    "new_password": "123",
    "new_password_confirmation": "123"
}'

SHORT_RESPONSE=$(make_auth_request "PUT" "/auth/change-password" "$SHORT_PASSWORD_DATA")
SUCCESS=$(echo $SHORT_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "false" ]; then
    ERRORS=$(echo $SHORT_RESPONSE | jq -r '.errors')
    print_result 0 "Validasi panjang password bekerja dengan baik"
    echo "Errors: $ERRORS"
else
    print_result 1 "Validasi panjang password tidak bekerja"
fi

# Test 9: Test password confirmation mismatch
echo -e "\n${YELLOW}Test 9: Test konfirmasi password tidak cocok${NC}"
MISMATCH_PASSWORD_DATA='{
    "current_password": "'$CURRENT_PASSWORD'",
    "new_password": "testpassword123",
    "new_password_confirmation": "differentpassword123"
}'

MISMATCH_RESPONSE=$(make_auth_request "PUT" "/auth/change-password" "$MISMATCH_PASSWORD_DATA")
SUCCESS=$(echo $MISMATCH_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "false" ]; then
    print_result 0 "Validasi konfirmasi password bekerja dengan baik"
else
    print_result 1 "Validasi konfirmasi password tidak bekerja"
fi

# Test 10: Change password with token revocation
echo -e "\n${YELLOW}Test 10: Ubah password dengan revoke semua token${NC}"
REVOKE_PASSWORD_DATA='{
    "current_password": "'$CURRENT_PASSWORD'",
    "new_password": "finalpassword123",
    "new_password_confirmation": "finalpassword123",
    "revoke_all_tokens": true
}'

REVOKE_RESPONSE=$(make_auth_request "PUT" "/auth/change-password" "$REVOKE_PASSWORD_DATA")
SUCCESS=$(echo $REVOKE_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    REVOKED=$(echo $REVOKE_RESPONSE | jq -r '.revoked_tokens')
    if [ "$REVOKED" = "true" ]; then
        print_result 0 "Password diubah dan token berhasil di-revoke"
        echo "Message: $(echo $REVOKE_RESPONSE | jq -r '.message')"
    else
        print_result 0 "Password berhasil diubah (tanpa revoke token)"
    fi
else
    print_result 1 "Gagal mengubah password dengan revoke token"
    echo "Response: $REVOKE_RESPONSE"
fi

# Test 11: Try to use old token after revocation (should fail)
echo -e "\n${YELLOW}Test 11: Test menggunakan token lama setelah revoke${NC}"
OLD_TOKEN_RESPONSE=$(make_auth_request "GET" "/auth/me")
SUCCESS=$(echo $OLD_TOKEN_RESPONSE | jq -r '.success')

if [ "$SUCCESS" = "false" ]; then
    print_result 0 "Token revocation bekerja dengan baik (token lama tidak valid)"
else
    print_result 1 "Token revocation tidak bekerja (token lama masih valid)"
fi

# Test 12: Login with new password to verify change
echo -e "\n${YELLOW}Test 12: Login dengan password baru untuk verifikasi${NC}"
NEW_LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
    -H "$CONTENT_TYPE" \
    -H "$ACCEPT" \
    -d '{
        "email": "admin@ncs.com",
        "password": "finalpassword123"
    }')

NEW_TOKEN=$(echo $NEW_LOGIN_RESPONSE | jq -r '.data.token // empty')

if [ -n "$NEW_TOKEN" ] && [ "$NEW_TOKEN" != "null" ]; then
    print_result 0 "Login dengan password baru berhasil"
    echo "New Token: ${NEW_TOKEN:0:20}..."
else
    print_result 1 "Login dengan password baru gagal"
    echo "Response: $NEW_LOGIN_RESPONSE"
fi

# Test 13: Reset password back to original for other tests
echo -e "\n${YELLOW}Test 13: Reset password kembali untuk test lain${NC}"
if [ -n "$NEW_TOKEN" ] && [ "$NEW_TOKEN" != "null" ]; then
    RESET_PASSWORD_DATA='{
        "current_password": "finalpassword123",
        "new_password": "password123",
        "new_password_confirmation": "password123"
    }'

    RESET_RESPONSE=$(curl -s -X PUT "$BASE_URL/auth/change-password" \
        -H "$CONTENT_TYPE" \
        -H "$ACCEPT" \
        -H "Authorization: Bearer $NEW_TOKEN" \
        -d "$RESET_PASSWORD_DATA")
    
    SUCCESS=$(echo $RESET_RESPONSE | jq -r '.success')
    if [ "$SUCCESS" = "true" ]; then
        print_result 0 "Password berhasil direset ke password123"
    else
        print_result 1 "Gagal reset password"
        echo "Response: $RESET_RESPONSE"
    fi
else
    print_result 1 "Tidak bisa reset password (tidak ada token baru)"
fi

# Summary
echo -e "\n${BLUE}========================================${NC}"
echo -e "${BLUE}           Testing Selesai              ${NC}"
echo -e "${BLUE}========================================${NC}"

echo -e "\n${YELLOW}Catatan:${NC}"
echo "1. Pastikan server Laravel berjalan di http://localhost:8000"
echo "2. Pastikan database telah dimigrasi dan seeded"
echo "3. Pastikan user admin@ncs.com dengan password 'password123' tersedia"
echo "4. Pastikan tabel users memiliki kolom phone dan address (atau buat migration)"
echo "5. Password telah direset kembali ke 'password123' untuk test lain"
echo ""
echo "Endpoint yang ditest:"
echo "  PUT $BASE_URL/auth/update-profile"
echo "  PUT $BASE_URL/auth/change-password"
echo ""
echo -e "${GREEN}Testing selesai!${NC}"