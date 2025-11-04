#!/bin/bash

# Script untuk menjalankan server dan membuka Swagger UI
# NCS Warehouse Management System

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  NCS Warehouse API Documentation       ${NC}"
echo -e "${BLUE}========================================${NC}"

# Function to check if port is available
check_port() {
    local port=$1
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null ; then
        return 1  # Port is in use
    else
        return 0  # Port is available
    fi
}

# Function to start Laravel server
start_server() {
    echo -e "\n${YELLOW}Starting Laravel development server...${NC}"
    
    if check_port 8000; then
        echo -e "${GREEN}Port 8000 is available${NC}"
        php artisan serve --host=0.0.0.0 --port=8000 &
        SERVER_PID=$!
        echo "Server PID: $SERVER_PID"
        
        # Wait for server to start
        sleep 3
        
        if kill -0 $SERVER_PID 2>/dev/null; then
            echo -e "${GREEN}✓ Laravel server started successfully on http://localhost:8000${NC}"
            return 0
        else
            echo -e "${RED}✗ Failed to start Laravel server${NC}"
            return 1
        fi
    else
        echo -e "${YELLOW}Port 8000 is already in use${NC}"
        echo -e "${GREEN}Assuming Laravel server is already running${NC}"
        return 0
    fi
}

# Function to regenerate Swagger docs
generate_swagger() {
    echo -e "\n${YELLOW}Regenerating Swagger documentation...${NC}"
    
    if php artisan l5-swagger:generate; then
        echo -e "${GREEN}✓ Swagger documentation generated successfully${NC}"
    else
        echo -e "${RED}✗ Failed to generate Swagger documentation${NC}"
        return 1
    fi
}

# Function to show API information
show_api_info() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}         API Information                ${NC}"
    echo -e "${BLUE}========================================${NC}"
    
    echo -e "\n${GREEN}Main Endpoints:${NC}"
    echo -e "🔐 Authentication:"
    echo -e "   POST   /api/auth/login"
    echo -e "   POST   /api/auth/logout"
    echo -e "   GET    /api/auth/me"
    echo -e "   PUT    /api/auth/update-profile"
    echo -e "   PUT    /api/auth/change-password"
    
    echo -e "\n📊 Log Aktivitas:"
    echo -e "   GET    /api/log-aktivitas"
    echo -e "   POST   /api/log-aktivitas"
    echo -e "   GET    /api/log-aktivitas/{id}"
    echo -e "   GET    /api/log-aktivitas/statistics"
    echo -e "   GET    /api/log-aktivitas/my-activities"
    
    echo -e "\n🏢 Master Data:"
    echo -e "   GET    /api/gudang"
    echo -e "   GET    /api/area-gudang"
    echo -e "   GET    /api/kategori-barang"
    echo -e "   GET    /api/barang"
    echo -e "   GET    /api/penempatan-barang"
    
    echo -e "\n🔧 Optimization:"
    echo -e "   POST   /api/optimization/simulated-annealing"
    echo -e "   GET    /api/optimization/warehouse-state"
    
    echo -e "\n${GREEN}Documentation URLs:${NC}"
    echo -e "📖 Swagger UI:     http://localhost:8000/api/documentation"
    echo -e "📄 JSON Docs:      http://localhost:8000/api/docs.json"
    echo -e "🌐 API Base URL:   http://localhost:8000/api"
    
    echo -e "\n${GREEN}Testing Scripts:${NC}"
    echo -e "🧪 Log Aktivitas:  ./script/test_log_aktivitas_api.sh"
    echo -e "👤 Update Profile: ./script/test_update_profile_api.sh"
}

# Function to open browser
open_browser() {
    local url=$1
    echo -e "\n${YELLOW}Opening Swagger UI in browser...${NC}"
    
    # Detect OS and open browser accordingly
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        open "$url"
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Linux
        xdg-open "$url"
    elif [[ "$OSTYPE" == "msys" || "$OSTYPE" == "cygwin" ]]; then
        # Windows
        start "$url"
    else
        echo -e "${YELLOW}Please open your browser and navigate to: $url${NC}"
    fi
}

# Function to test API connectivity
test_api() {
    echo -e "\n${YELLOW}Testing API connectivity...${NC}"
    
    # Test health endpoint (if exists) or try to login
    RESPONSE=$(curl -s -w "%{http_code}" -o /dev/null "http://localhost:8000/api/auth/login")
    
    if [ "$RESPONSE" = "405" ] || [ "$RESPONSE" = "422" ]; then
        echo -e "${GREEN}✓ API is responding (HTTP $RESPONSE)${NC}"
        return 0
    elif [ "$RESPONSE" = "000" ]; then
        echo -e "${RED}✗ Cannot connect to API server${NC}"
        return 1
    else
        echo -e "${YELLOW}API responding with HTTP $RESPONSE${NC}"
        return 0
    fi
}

# Main execution
main() {
    # Check if we're in the right directory
    if [ ! -f "artisan" ]; then
        echo -e "${RED}Error: Not in Laravel project directory${NC}"
        echo -e "Please run this script from the project root directory"
        exit 1
    fi
    
    # Generate Swagger documentation
    generate_swagger || exit 1
    
    # Start server if needed
    start_server || exit 1
    
    # Test API connectivity
    sleep 2
    test_api || {
        echo -e "${RED}API server is not responding. Please check the server.${NC}"
        exit 1
    }
    
    # Show API information
    show_api_info
    
    # Ask user if they want to open browser
    echo -e "\n${YELLOW}Would you like to open Swagger UI in your browser? (y/n): ${NC}"
    read -r OPEN_BROWSER
    
    if [[ $OPEN_BROWSER =~ ^[Yy]$ ]]; then
        open_browser "http://localhost:8000/api/documentation"
    fi
    
    echo -e "\n${GREEN}Setup complete!${NC}"
    echo -e "${YELLOW}Press Ctrl+C to stop the server when you're done.${NC}"
    
    # Keep script running if we started the server
    if [ -n "$SERVER_PID" ]; then
        echo -e "\n${BLUE}Server is running... Press Ctrl+C to stop${NC}"
        trap "echo -e '\n${YELLOW}Stopping server...${NC}'; kill $SERVER_PID 2>/dev/null; exit 0" INT
        wait $SERVER_PID
    fi
}

# Show help if requested
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --help, -h     Show this help message"
    echo "  --no-browser   Don't open browser automatically"
    echo "  --port PORT    Use specific port (default: 8000)"
    echo ""
    echo "This script will:"
    echo "1. Generate Swagger documentation"
    echo "2. Start Laravel development server (if not running)"
    echo "3. Test API connectivity"
    echo "4. Display API information"
    echo "5. Optionally open Swagger UI in browser"
    exit 0
fi

# Run main function
main