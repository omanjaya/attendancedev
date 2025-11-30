# Development Servers Quick Reference

## 🚀 Current Status

### Backend (Laravel 12 API)
- **URL:** http://127.0.0.1:8000
- **Health:** http://127.0.0.1:8000/api/health
- **Docs:** http://127.0.0.1:8000/api/documentation
- **Log:** `/tmp/backend.log`

### Frontend (React 19 SPA)
- **URL:** http://localhost:5173
- **Log:** `/tmp/frontend.log`

### API Proxy
Frontend `/api/*` → Backend `:8000/api/*` (configured in `frontend/vite.config.ts`)

---

## 🛠️ Management Commands

### Start Servers

**Option 1: Both servers (recommended)**
```bash
cd /home/omanjaya/Project/attendancedev
npm run dev
```

**Option 2: Individual servers**
```bash
# Terminal 1 - Backend
cd backend
php artisan serve

# Terminal 2 - Frontend
cd frontend
npm run dev
```

**Option 3: Background (current setup)**
```bash
# Backend
cd backend && nohup php artisan serve > /tmp/backend.log 2>&1 &

# Frontend
cd frontend && nohup npm run dev > /tmp/frontend.log 2>&1 &
```

### Stop Servers

```bash
# Stop all dev servers
pkill -f "php artisan serve"
pkill -f "vite"

# Or kill specific PIDs
kill $(cat /tmp/backend.pid)
kill $(cat /tmp/frontend.pid)
```

### View Logs

```bash
# Backend logs
tail -f /tmp/backend.log

# Frontend logs
tail -f /tmp/frontend.log

# Both logs
tail -f /tmp/backend.log /tmp/frontend.log
```

### Check Status

```bash
# Check if servers are running
ps aux | grep -E "php artisan serve|vite" | grep -v grep

# Test backend health
curl http://127.0.0.1:8000/api/health

# Test frontend
curl -I http://localhost:5173
```

---

## 📂 Project Structure

```
attendancedev/
├── backend/              # Laravel 12 API
│   ├── app/              # Controllers, Services, Models
│   ├── routes/           # API routes
│   ├── database/         # Migrations, seeders
│   ├── .env              # Backend config
│   └── artisan           # Laravel CLI
│
├── frontend/             # React 19 SPA
│   ├── src/              # React components, pages
│   ├── public/           # Static assets
│   ├── vite.config.ts    # Vite + proxy config
│   └── package.json      # Frontend deps
│
├── shared/               # Shared types/constants
│   ├── types/            # TypeScript interfaces
│   └── constants/        # Shared constants
│
└── package.json          # Root workspace manager
```

---

## 🔧 Troubleshooting

### Port Already in Use

```bash
# Find process using port
lsof -i :8000    # Backend
lsof -i :5173    # Frontend

# Kill process
kill -9 <PID>
```

### Backend Not Starting

```bash
cd backend
composer install --ignore-platform-req=ext-gd
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend Not Starting

```bash
cd frontend
rm -rf node_modules package-lock.json
npm install
npm run dev
```

### API Not Accessible from Frontend

1. Check backend is running: `curl http://127.0.0.1:8000/api/health`
2. Check Vite proxy config in `frontend/vite.config.ts`
3. Check CORS settings in `backend/config/cors.php`

### Database Issues

```bash
cd backend
php artisan migrate:fresh --seed
```

---

## 📚 Additional Commands

### Database

```bash
cd backend
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Reset database
php artisan migrate:fresh --seed # Reset + seed
php artisan db:seed              # Run seeders only
```

### Testing

```bash
# All tests
npm test

# Backend tests
npm run test:backend
# Or: cd backend && php artisan test

# Frontend tests
npm run test:frontend
# Or: cd frontend && npm run test

# E2E tests
npm run test:e2e
# Or: cd frontend && npm run test:e2e
```

### Code Quality

```bash
# Lint frontend
npm run lint
# Or: cd frontend && npm run lint

# Full quality check (frontend)
npm run quality
# Or: cd frontend && npm run quality
```

### Building

```bash
# Build all
npm run build

# Build frontend only
npm run build:frontend
# Or: cd frontend && npm run build

# Build backend (optimize deps)
npm run build:backend
# Or: cd backend && composer install --optimize-autoloader --no-dev
```

---

## 🎯 Quick Start for New Developers

1. **Clone & Setup**
   ```bash
   git clone <repo>
   cd attendancedev
   npm install
   cd backend && composer install --ignore-platform-req=ext-gd
   ```

2. **Configure Backend**
   ```bash
   cd backend
   cp .env.example .env
   php artisan key:generate
   touch database/database.sqlite
   php artisan migrate --seed
   ```

3. **Start Servers**
   ```bash
   cd /home/omanjaya/Project/attendancedev
   npm run dev
   ```

4. **Access Application**
   - Frontend: http://localhost:5173
   - API: http://127.0.0.1:8000

---

**Last Updated:** 2025-11-30  
**Monorepo Version:** 1.0.0
