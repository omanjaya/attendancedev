#!/bin/bash

#===============================================================================
# 🚀 ATTENDANCE SYSTEM - START ALL SERVICES
#===============================================================================
#
# Script untuk menjalankan SEMUA services sekaligus:
# - Backend (Laravel)
# - Frontend (React/Vite)
# - Face Recognition (DeepFace Python)
#
# Usage:
#   ./start-all.sh         # Start all services
#   ./start-all.sh stop    # Stop all services
#   ./start-all.sh status  # Check status
#   ./start-all.sh restart # Restart all services
#
#===============================================================================

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Log files
LOG_DIR="/tmp/attendance-dev"
BACKEND_LOG="$LOG_DIR/backend.log"
FRONTEND_LOG="$LOG_DIR/frontend.log"
DEEPFACE_LOG="$LOG_DIR/deepface.log"

# PID files
BACKEND_PID="$LOG_DIR/backend.pid"
FRONTEND_PID="$LOG_DIR/frontend.pid"
DEEPFACE_PID="$LOG_DIR/deepface.pid"

#-------------------------------------------------------------------------------
# HELPER FUNCTIONS
#-------------------------------------------------------------------------------

print_header() {
    echo ""
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

print_step() {
    echo -e "${BLUE}▶${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

ensure_log_dir() {
    mkdir -p "$LOG_DIR"
}

#-------------------------------------------------------------------------------
# START FUNCTIONS
#-------------------------------------------------------------------------------

start_backend() {
    print_step "Starting Backend (Laravel)..."
    
    cd "$PROJECT_DIR/backend"
    
    # Check if already running
    if [ -f "$BACKEND_PID" ] && kill -0 $(cat "$BACKEND_PID") 2>/dev/null; then
        print_warning "Backend already running (PID: $(cat $BACKEND_PID))"
        return
    fi
    
    # Start Laravel
    nohup php artisan serve --host=127.0.0.1 --port=8000 > "$BACKEND_LOG" 2>&1 &
    echo $! > "$BACKEND_PID"
    
    sleep 2
    
    if curl -s http://127.0.0.1:8000/api/health > /dev/null 2>&1; then
        print_success "Backend running at http://127.0.0.1:8000"
    else
        print_warning "Backend starting... (check $BACKEND_LOG)"
    fi
}

start_frontend() {
    print_step "Starting Frontend (Vite)..."
    
    cd "$PROJECT_DIR/frontend"
    
    # Check if already running
    if [ -f "$FRONTEND_PID" ] && kill -0 $(cat "$FRONTEND_PID") 2>/dev/null; then
        print_warning "Frontend already running (PID: $(cat $FRONTEND_PID))"
        return
    fi
    
    # Start Vite
    nohup npm run dev > "$FRONTEND_LOG" 2>&1 &
    echo $! > "$FRONTEND_PID"
    
    sleep 3
    
    if curl -s -I http://localhost:5173 > /dev/null 2>&1; then
        print_success "Frontend running at http://localhost:5173"
    else
        print_warning "Frontend starting... (check $FRONTEND_LOG)"
    fi
}

start_deepface() {
    print_step "Starting DeepFace Service..."
    
    DEEPFACE_DIR="$PROJECT_DIR/python-services/face-recognition"
    
    if [ ! -d "$DEEPFACE_DIR" ]; then
        print_warning "DeepFace directory not found, skipping..."
        return
    fi
    
    cd "$DEEPFACE_DIR"
    
    # Check if already running
    if [ -f "$DEEPFACE_PID" ] && kill -0 $(cat "$DEEPFACE_PID") 2>/dev/null; then
        print_warning "DeepFace already running (PID: $(cat $DEEPFACE_PID))"
        return
    fi
    
    # Check for start-cluster.sh
    if [ -f "start-cluster.sh" ]; then
        print_step "Starting DeepFace cluster (ports 8001-8005)..."
        nohup ./start-cluster.sh > "$DEEPFACE_LOG" 2>&1 &
        echo $! > "$DEEPFACE_PID"
    elif [ -f "app.py" ]; then
        # Start single instance
        if [ -d "venv" ]; then
            source venv/bin/activate
        fi
        nohup python app.py > "$DEEPFACE_LOG" 2>&1 &
        echo $! > "$DEEPFACE_PID"
    else
        print_warning "DeepFace app not found, skipping..."
        return
    fi
    
    sleep 3
    
    if curl -s http://127.0.0.1:8001/health > /dev/null 2>&1; then
        print_success "DeepFace running at http://127.0.0.1:8001"
    else
        print_warning "DeepFace starting... (check $DEEPFACE_LOG)"
    fi
}

#-------------------------------------------------------------------------------
# STOP FUNCTIONS
#-------------------------------------------------------------------------------

stop_backend() {
    print_step "Stopping Backend..."
    
    if [ -f "$BACKEND_PID" ]; then
        kill $(cat "$BACKEND_PID") 2>/dev/null
        rm -f "$BACKEND_PID"
    fi
    
    # Kill any remaining Laravel processes
    pkill -f "php artisan serve" 2>/dev/null
    
    print_success "Backend stopped"
}

stop_frontend() {
    print_step "Stopping Frontend..."
    
    if [ -f "$FRONTEND_PID" ]; then
        kill $(cat "$FRONTEND_PID") 2>/dev/null
        rm -f "$FRONTEND_PID"
    fi
    
    # Kill any remaining Vite processes
    pkill -f "vite" 2>/dev/null
    
    print_success "Frontend stopped"
}

stop_deepface() {
    print_step "Stopping DeepFace..."
    
    if [ -f "$DEEPFACE_PID" ]; then
        kill $(cat "$DEEPFACE_PID") 2>/dev/null
        rm -f "$DEEPFACE_PID"
    fi
    
    # Check for stop script
    DEEPFACE_DIR="$PROJECT_DIR/python-services/face-recognition"
    if [ -f "$DEEPFACE_DIR/stop-cluster.sh" ]; then
        cd "$DEEPFACE_DIR" && ./stop-cluster.sh 2>/dev/null
    fi
    
    # Kill any remaining DeepFace processes
    pkill -f "deepface" 2>/dev/null
    pkill -f "gunicorn.*8001" 2>/dev/null
    
    print_success "DeepFace stopped"
}

#-------------------------------------------------------------------------------
# STATUS FUNCTION
#-------------------------------------------------------------------------------

show_status() {
    print_header "📊 SERVICE STATUS"
    echo ""
    
    # Backend
    if [ -f "$BACKEND_PID" ] && kill -0 $(cat "$BACKEND_PID") 2>/dev/null; then
        echo -e "  ${GREEN}●${NC} Backend     : Running (PID: $(cat $BACKEND_PID)) - http://127.0.0.1:8000"
    elif curl -s http://127.0.0.1:8000/api/health > /dev/null 2>&1; then
        echo -e "  ${GREEN}●${NC} Backend     : Running - http://127.0.0.1:8000"
    else
        echo -e "  ${RED}●${NC} Backend     : Stopped"
    fi
    
    # Frontend
    if [ -f "$FRONTEND_PID" ] && kill -0 $(cat "$FRONTEND_PID") 2>/dev/null; then
        echo -e "  ${GREEN}●${NC} Frontend    : Running (PID: $(cat $FRONTEND_PID)) - http://localhost:5173"
    elif curl -s -I http://localhost:5173 > /dev/null 2>&1; then
        echo -e "  ${GREEN}●${NC} Frontend    : Running - http://localhost:5173"
    else
        echo -e "  ${RED}●${NC} Frontend    : Stopped"
    fi
    
    # DeepFace
    deepface_running=false
    for port in 8001 8002 8003 8004 8005; do
        if curl -s http://127.0.0.1:$port/health > /dev/null 2>&1; then
            deepface_running=true
            break
        fi
    done
    
    if $deepface_running; then
        echo -e "  ${GREEN}●${NC} DeepFace    : Running - http://127.0.0.1:8001-8005"
    else
        echo -e "  ${YELLOW}●${NC} DeepFace    : Not running (optional)"
    fi
    
    echo ""
    echo -e "  ${BLUE}Log files:${NC}"
    echo "    Backend:  $BACKEND_LOG"
    echo "    Frontend: $FRONTEND_LOG"
    echo "    DeepFace: $DEEPFACE_LOG"
    echo ""
}

#-------------------------------------------------------------------------------
# MAIN
#-------------------------------------------------------------------------------

main() {
    ensure_log_dir
    
    case "${1:-start}" in
        start)
            print_header "🚀 STARTING ALL SERVICES"
            echo ""
            start_backend
            start_frontend
            start_deepface
            echo ""
            sleep 2
            show_status
            echo -e "  ${GREEN}All services started!${NC}"
            echo ""
            echo "  📝 View logs:"
            echo "     tail -f $LOG_DIR/*.log"
            echo ""
            echo "  🛑 Stop all:"
            echo "     $0 stop"
            echo ""
            ;;
        stop)
            print_header "🛑 STOPPING ALL SERVICES"
            echo ""
            stop_backend
            stop_frontend
            stop_deepface
            echo ""
            print_success "All services stopped"
            echo ""
            ;;
        restart)
            print_header "🔄 RESTARTING ALL SERVICES"
            echo ""
            stop_backend
            stop_frontend
            stop_deepface
            sleep 2
            start_backend
            start_frontend
            start_deepface
            echo ""
            sleep 2
            show_status
            ;;
        status)
            show_status
            ;;
        logs)
            echo "Following all logs (Ctrl+C to stop)..."
            tail -f $LOG_DIR/*.log
            ;;
        *)
            echo "Usage: $0 {start|stop|restart|status|logs}"
            echo ""
            echo "Commands:"
            echo "  start   - Start all services (default)"
            echo "  stop    - Stop all services"
            echo "  restart - Restart all services"
            echo "  status  - Show service status"
            echo "  logs    - Follow all log files"
            exit 1
            ;;
    esac
}

main "$@"
