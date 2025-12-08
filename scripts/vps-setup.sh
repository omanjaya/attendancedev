#!/bin/bash
# ============================================
# VPS Initial Setup Script
# ============================================
# Jalankan script ini di VPS baru untuk setup awal
# Usage: curl -sSL https://raw.githubusercontent.com/YOUR_USER/attendancedev/main/scripts/vps-setup.sh | sudo bash
# ============================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log() { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }
info() { echo -e "${BLUE}[i]${NC} $1"; }

# ==========================================
# Configuration
# ==========================================
PROJECT_DIR="/opt/attendancedev"
DOMAIN="${DOMAIN:-}"
DOCKERHUB_USERNAME="${DOCKERHUB_USERNAME:-}"

# ==========================================
# Check if running as root
# ==========================================
if [[ $EUID -ne 0 ]]; then
   error "This script must be run as root (use sudo)"
fi

echo ""
echo "============================================"
echo "   Attendance System - VPS Setup"
echo "============================================"
echo ""

# ==========================================
# 1. Update System
# ==========================================
log "Updating system packages..."
apt-get update -qq
apt-get upgrade -y -qq

# ==========================================
# 2. Install Docker
# ==========================================
if ! command -v docker &> /dev/null; then
    log "Installing Docker..."
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker
    systemctl start docker
else
    log "Docker already installed"
fi

# ==========================================
# 3. Install Docker Compose
# ==========================================
if ! command -v docker compose &> /dev/null; then
    log "Installing Docker Compose plugin..."
    apt-get install -y docker-compose-plugin
else
    log "Docker Compose already installed"
fi

# ==========================================
# 4. Install Required Tools
# ==========================================
log "Installing required tools..."
apt-get install -y -qq \
    git \
    curl \
    wget \
    htop \
    ufw \
    fail2ban \
    certbot

# ==========================================
# 5. Setup Firewall
# ==========================================
log "Configuring firewall..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# ==========================================
# 6. Setup Fail2ban
# ==========================================
log "Configuring fail2ban..."
systemctl enable fail2ban
systemctl start fail2ban

# ==========================================
# 7. Create Project Directory
# ==========================================
log "Creating project directory..."
mkdir -p $PROJECT_DIR
cd $PROJECT_DIR

# ==========================================
# 8. Clone Repository (jika belum ada)
# ==========================================
if [ ! -d "$PROJECT_DIR/.git" ]; then
    log "Cloning repository..."
    read -p "Enter GitHub repository URL: " REPO_URL
    git clone $REPO_URL $PROJECT_DIR
else
    log "Repository already exists, pulling latest..."
    git fetch origin
    git reset --hard origin/main
fi

# ==========================================
# 9. Setup Environment File
# ==========================================
if [ ! -f "$PROJECT_DIR/.env" ]; then
    log "Setting up environment file..."
    cp $PROJECT_DIR/.env.docker.example $PROJECT_DIR/.env
    
    # Generate APP_KEY
    APP_KEY=$(openssl rand -base64 32)
    sed -i "s|APP_KEY=.*|APP_KEY=base64:$APP_KEY|g" $PROJECT_DIR/.env
    
    # Generate secure passwords
    DB_PASSWORD=$(openssl rand -hex 16)
    REDIS_PASSWORD=$(openssl rand -hex 16)
    
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|g" $PROJECT_DIR/.env
    sed -i "s|REDIS_PASSWORD=.*|REDIS_PASSWORD=$REDIS_PASSWORD|g" $PROJECT_DIR/.env
    
    warn "Environment file created at $PROJECT_DIR/.env"
    warn "Please edit it with your domain and other settings!"
else
    log "Environment file already exists"
fi

# ==========================================
# 10. Create Docker config directories
# ==========================================
log "Creating Docker config directories..."
mkdir -p $PROJECT_DIR/docker/nginx/certbot

# ==========================================
# 11. Login to Docker Hub (optional)
# ==========================================
if [ -n "$DOCKERHUB_USERNAME" ]; then
    log "Please login to Docker Hub..."
    docker login -u $DOCKERHUB_USERNAME
fi

# ==========================================
# Summary
# ==========================================
echo ""
echo "============================================"
echo "   Setup Complete!"
echo "============================================"
echo ""
info "Next steps:"
echo ""
echo "1. Edit the environment file:"
echo "   nano $PROJECT_DIR/.env"
echo ""
echo "2. Set your domain (APP_URL and VITE_* variables)"
echo ""
echo "3. Start the services:"
echo "   cd $PROJECT_DIR"
echo "   docker compose -f docker-compose.hub.yml up -d"
echo ""
echo "4. Run database migrations:"
echo "   docker exec attendancedev-backend php artisan migrate --seed"
echo ""
echo "5. Setup SSL (optional):"
echo "   certbot certonly --webroot -w /var/www/certbot -d yourdomain.com"
echo ""
echo "============================================"
echo ""
