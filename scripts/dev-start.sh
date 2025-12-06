#!/bin/bash

# Development Server Start Script
# Starts all development services concurrently

echo "🚀 Starting School Attendance System Development Environment"
echo "============================================================"

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: Please run this script from the project root directory"
    exit 1
fi

# Function to cleanup background processes
cleanup() {
    echo
    echo "🛑 Stopping all services..."
    jobs -p | xargs -r kill
    exit 0
}

# Set trap to cleanup on script exit
trap cleanup SIGINT SIGTERM

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "❌ No .env file found. Run './scripts/dev-setup.sh' first"
    exit 1
fi

# Clear caches
echo "🧹 Clearing caches..."
php artisan optimize:clear

# Check database
if [ ! -f "database/database.sqlite" ]; then
    echo "🗄️ Creating database..."
    touch database/database.sqlite
    php artisan migrate
fi

echo "📋 Starting services..."
echo

# Start Laravel development server
echo "🌐 Starting Laravel server on http://localhost:8000..."
php artisan serve &
SERVER_PID=$!

# Wait a moment for server to start
sleep 2

# Start Vite development server with hot reload
echo "⚡ Starting Vite development server with hot reload..."
npm run dev &
VITE_PID=$!

# Start queue worker
echo "⚙️ Starting queue worker..."
php artisan queue:work --daemon &
QUEUE_PID=$!

# Start log monitoring
echo "📊 Starting log monitoring..."
php artisan pail &
LOG_PID=$!

echo
echo "✅ All services started successfully!"
echo
echo "📋 Services running:"
echo "   🌐 Laravel Server: http://localhost:8000"
echo "   ⚡ Vite HMR Server: http://localhost:5173"
echo "   ⚙️ Queue Worker: Running in background"
echo "   📊 Log Monitor: Running in background"
echo
echo "📖 Available endpoints:"
echo "   🏠 Dashboard: http://localhost:8000"
echo "   🔐 Login: http://localhost:8000/login"
echo "   📚 API Docs: http://localhost:8000/api/documentation"
echo "   ❤️ Health Check: http://localhost:8000/api/health"
echo
echo "💡 Press Ctrl+C to stop all services"
echo

# Wait for all background processes
wait