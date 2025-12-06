#!/bin/bash
# ============================================
# Docker Deployment Script
# ============================================
# Usage: ./docker-deploy.sh [dev|prod|build|logs|shell]
# ============================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Functions
print_header() {
    echo -e "${BLUE}============================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}============================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Check if Docker is installed
check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    print_success "Docker is installed"
}

# Check if .env file exists
check_env() {
    if [ ! -f "$PROJECT_DIR/.env" ]; then
        print_warning ".env file not found. Creating from example..."
        cp "$PROJECT_DIR/.env.docker.example" "$PROJECT_DIR/.env"
        print_warning "Please edit .env file with your configuration"
        exit 1
    fi
    print_success ".env file exists"
}

# Development mode
dev() {
    print_header "Starting Development Environment"
    
    cd "$PROJECT_DIR"
    
    # Start services
    docker-compose up -d
    
    # Wait for database
    print_warning "Waiting for database to be ready..."
    sleep 5
    
    # Run migrations
    print_warning "Running migrations..."
    docker-compose exec -T backend php artisan migrate --seed --force || true
    
    print_success "Development environment is ready!"
    echo ""
    echo "Access the application:"
    echo "  - Frontend: http://localhost:5173"
    echo "  - Backend API: http://localhost/api"
    echo "  - Adminer (DB): http://localhost:8080"
    echo ""
    echo "Default credentials:"
    echo "  - Email: admin@school.edu"
    echo "  - Password: password"
}

# Production mode
prod() {
    print_header "Starting Production Environment"
    
    cd "$PROJECT_DIR"
    
    # Build images
    print_warning "Building production images..."
    docker-compose -f docker-compose.prod.yml build
    
    # Start services
    print_warning "Starting services..."
    docker-compose -f docker-compose.prod.yml up -d
    
    # Wait for database
    print_warning "Waiting for database to be ready..."
    sleep 10
    
    # Run migrations
    print_warning "Running migrations..."
    docker-compose -f docker-compose.prod.yml exec -T backend php artisan migrate --force || true
    
    # Optimize Laravel
    print_warning "Optimizing Laravel..."
    docker-compose -f docker-compose.prod.yml exec -T backend php artisan config:cache
    docker-compose -f docker-compose.prod.yml exec -T backend php artisan route:cache
    docker-compose -f docker-compose.prod.yml exec -T backend php artisan view:cache
    
    print_success "Production environment is ready!"
}

# Build only
build() {
    print_header "Building Docker Images"
    
    cd "$PROJECT_DIR"
    
    if [ "$1" == "prod" ]; then
        docker-compose -f docker-compose.prod.yml build --no-cache
    else
        docker-compose build --no-cache
    fi
    
    print_success "Build completed!"
}

# Show logs
logs() {
    cd "$PROJECT_DIR"
    
    if [ -n "$1" ]; then
        docker-compose logs -f "$1"
    else
        docker-compose logs -f
    fi
}

# Shell access
shell() {
    cd "$PROJECT_DIR"
    
    service="${1:-backend}"
    
    case $service in
        backend)
            docker-compose exec backend sh
            ;;
        postgres)
            docker-compose exec postgres psql -U attendance_user -d attendance_system
            ;;
        redis)
            docker-compose exec redis redis-cli
            ;;
        *)
            docker-compose exec "$service" sh
            ;;
    esac
}

# Stop all services
stop() {
    print_header "Stopping All Services"
    
    cd "$PROJECT_DIR"
    
    docker-compose down
    
    print_success "All services stopped!"
}

# Restart services
restart() {
    print_header "Restarting Services"
    
    cd "$PROJECT_DIR"
    
    docker-compose restart
    
    print_success "Services restarted!"
}

# Backup database
backup() {
    print_header "Backing Up Database"
    
    cd "$PROJECT_DIR"
    
    BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
    
    docker-compose exec -T postgres pg_dump -U attendance_user attendance_system > "$PROJECT_DIR/backups/$BACKUP_FILE"
    
    print_success "Database backed up to: backups/$BACKUP_FILE"
}

# Run artisan commands
artisan() {
    cd "$PROJECT_DIR"
    
    docker-compose exec backend php artisan "$@"
}

# Show help
show_help() {
    echo "Usage: ./docker-deploy.sh [command] [options]"
    echo ""
    echo "Commands:"
    echo "  dev         Start development environment"
    echo "  prod        Start production environment"
    echo "  build       Build Docker images (add 'prod' for production)"
    echo "  logs        Show logs (optional: service name)"
    echo "  shell       Access shell (optional: service name)"
    echo "  stop        Stop all services"
    echo "  restart     Restart all services"
    echo "  backup      Backup database"
    echo "  artisan     Run artisan command"
    echo "  help        Show this help message"
    echo ""
    echo "Examples:"
    echo "  ./docker-deploy.sh dev"
    echo "  ./docker-deploy.sh prod"
    echo "  ./docker-deploy.sh logs backend"
    echo "  ./docker-deploy.sh shell postgres"
    echo "  ./docker-deploy.sh artisan migrate"
}

# Main
main() {
    check_docker
    check_env
    
    case "${1:-help}" in
        dev)
            dev
            ;;
        prod)
            prod
            ;;
        build)
            build "$2"
            ;;
        logs)
            logs "$2"
            ;;
        shell)
            shell "$2"
            ;;
        stop)
            stop
            ;;
        restart)
            restart
            ;;
        backup)
            backup
            ;;
        artisan)
            shift
            artisan "$@"
            ;;
        help|*)
            show_help
            ;;
    esac
}

main "$@"
