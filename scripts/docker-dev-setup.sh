#!/bin/bash
# ============================================
# Docker Development Setup Script
# ============================================
# Usage: ./scripts/docker-dev-setup.sh
# ============================================

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${YELLOW}🐳 Setting up Docker Development Environment...${NC}"

# 1. Check & Setup Environment Variables
echo -e "${GREEN}1. Checking environment configuration...${NC}"
if [ ! -f .env ]; then
    if [ -f .env.docker.example ]; then
        cp .env.docker.example .env
        echo -e "${YELLOW}Created .env from example. Please check variables!${NC}"
    else
        echo -e "${RED}Error: .env.docker.example not found!${NC}"
        exit 1
    fi
fi

# 2. Fix Directory Permissions (Host Side)
echo -e "${GREEN}2. Setting up directory permissions...${NC}"
chmod -R 777 backend/storage backend/bootstrap/cache
chmod -R 777 backend/public
echo "Permissions set to 777 for storage and cache directories (Dev Mode only)"

# 3. Build & Start Containers
echo -e "${GREEN}3. Building and starting containers...${NC}"
docker-compose build
docker-compose up -d

# 4. Wait for Database
echo -e "${GREEN}4. Waiting for database to be ready...${NC}"
docker-compose exec -T postgres pg_isready --timeout=30

# 5. Install Dependencies & Migrate
echo -e "${GREEN}5. Installing Backend Dependencies & Migrating...${NC}"
docker-compose exec -T backend composer install
docker-compose exec -T backend php artisan key:generate
docker-compose exec -T backend php artisan migrate:fresh --seed

echo -e "${GREEN}6. Installing Frontend Dependencies...${NC}"
docker-compose exec -T frontend npm install

echo -e "${GREEN}✅ Development Environment Ready!${NC}"
echo ""
echo "Access the application:"
echo "  - Frontend: http://localhost"
echo "  - Backend API: http://localhost/api"
echo "  - Adminer (DB): http://localhost:8080"
echo ""
