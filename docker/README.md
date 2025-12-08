# Docker Setup - attendancedev

## 📦 Project Structure

```
attendancedev/                    # Project name
├── attendancedev-postgres        # PostgreSQL database
├── attendancedev-redis           # Redis cache
├── attendancedev-backend         # Laravel PHP-FPM
├── attendancedev-frontend        # React + Vite
├── attendancedev-deepface        # Python face recognition
├── attendancedev-nginx           # Nginx reverse proxy
└── attendancedev-adminer         # Database admin (dev only)
```

## 🚀 Quick Start

### Setup dengan Script

```bash
# Tanpa password
./scripts/docker-dev-setup.sh

# Dengan password sudo
SUDO_PASSWORD=qq ./scripts/docker-dev-setup.sh
```

### Manual Setup

```bash
# 1. Copy environment file
cp .env.docker.example .env

# 2. Build dan start containers
docker compose up -d

# Atau dengan sudo
echo "qq" | sudo -S docker compose up -d
```

## 🛠️ Docker Commands

### Helper Script (Recommended)

```bash
# Start containers
./docker.sh start

# Stop containers
./docker.sh stop

# Restart containers
./docker.sh restart

# View logs
./docker.sh logs -f

# Rebuild all
./docker.sh rebuild

# Clean up
./docker.sh clean

# Dengan sudo password
SUDO_PASSWORD=qq ./docker.sh start
```

### Direct Commands

```bash
# List containers
docker compose ps

# View logs
docker compose logs -f

# Stop all
docker compose down

# Restart specific service
docker compose restart backend

# Execute command in container
docker compose exec backend php artisan migrate

# Shell access
docker compose exec backend bash
docker compose exec frontend sh
docker compose exec postgres psql -U attendance_user -d attendance_system
```

## 📋 Container Management

### Backend (Laravel)

```bash
# Run artisan commands
docker compose exec backend php artisan migrate
docker compose exec backend php artisan tinker
docker compose exec backend php artisan queue:work

# Composer
docker compose exec backend composer install
docker compose exec backend composer update

# Shell access
docker compose exec backend bash
```

### Frontend (React)

```bash
# NPM commands
docker compose exec frontend npm install
docker compose exec frontend npm run build

# Shell access
docker compose exec frontend sh
```

### Database (PostgreSQL)

```bash
# Access psql
docker compose exec postgres psql -U attendance_user -d attendance_system

# Backup database
docker compose exec postgres pg_dump -U attendance_user attendance_system > backup.sql

# Restore database
cat backup.sql | docker compose exec -T postgres psql -U attendance_user -d attendance_system
```

### Redis

```bash
# Access redis-cli
docker compose exec redis redis-cli

# Monitor commands
docker compose exec redis redis-cli MONITOR

# Flush cache
docker compose exec redis redis-cli FLUSHALL
```

## 🔧 Troubleshooting

### Permission Issues

```bash
# Fix storage permissions
sudo chmod -R 777 backend/storage backend/bootstrap/cache

# Or with password
echo "qq" | sudo -S chmod -R 777 backend/storage backend/bootstrap/cache
```

### Container Not Starting

```bash
# Check logs
docker compose logs -f [service-name]

# Rebuild container
docker compose build --no-cache [service-name]
docker compose up -d [service-name]
```

### Port Already in Use

```bash
# Check what's using the port
sudo lsof -i :80
sudo lsof -i :5173

# Kill process or change port in docker-compose.yml
```

### Clean Reset

```bash
# Stop and remove everything
docker compose down -v

# Remove orphaned containers
docker compose down --remove-orphans

# Complete cleanup (removes volumes!)
docker compose down -v --rmi all --remove-orphans
```

## 🌐 Network Configuration

- **Network Name**: `attendancedev-network`
- **Internal Network**: `attendancedev-internal` (production)
- **External Network**: `attendancedev-external` (production)

All services communicate through the internal network using service names (postgres, redis, backend, etc.).

## 📦 Volumes

```bash
# List volumes
docker volume ls | grep attendancedev

# Inspect volume
docker volume inspect attendancedev_postgres_data

# Remove unused volumes
docker volume prune
```

## 🔐 Using Sudo with Password

### Method 1: Environment Variable

```bash
SUDO_PASSWORD=qq ./docker.sh start
SUDO_PASSWORD=qq ./scripts/docker-dev-setup.sh
```

### Method 2: Echo and Pipe

```bash
echo "qq" | sudo -S docker compose up -d
echo "qq" | sudo -S docker compose down
```

### Method 3: Add to Sudoers (Not Recommended)

```bash
# Allow docker without password (use with caution!)
echo "$USER ALL=(ALL) NOPASSWD: /usr/bin/docker" | sudo tee /etc/sudoers.d/docker-nopasswd
```

## 📊 Monitoring

### View Container Stats

```bash
# Real-time resource usage
docker stats

# Specific containers
docker stats attendancedev-backend attendancedev-postgres
```

### Health Checks

```bash
# Check container health
docker compose ps

# Inspect health
docker inspect --format='{{json .State.Health}}' attendancedev-postgres | jq
```

## 🔄 Development Workflow

1. **Start Development**
   ```bash
   ./docker.sh start
   ```

2. **Watch Logs**
   ```bash
   ./docker.sh logs -f backend
   ```

3. **Run Migrations**
   ```bash
   docker compose exec backend php artisan migrate
   ```

4. **Access Frontend**
   - http://localhost:5173

5. **Access Backend API**
   - http://localhost/api

6. **Database Admin**
   - http://localhost:8080 (Adminer)

## 📝 Environment Variables

Key variables in `.env`:

```env
# Database
DB_DATABASE=attendance_system
DB_USERNAME=attendance_user
DB_PASSWORD=attendance123

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# DeepFace
DEEPFACE_BASE_URL=http://deepface:8001
DEEPFACE_MODEL=ArcFace
DEEPFACE_DETECTOR=retinaface
```

## 🚀 Production Deployment

```bash
# Use production compose file
docker compose -f docker-compose.prod.yml up -d

# Or with sudo
echo "qq" | sudo -S docker compose -f docker-compose.prod.yml up -d
```

## 📖 Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- Main project README: `../README.md`
- Deployment guide: `../docs/DEPLOYMENT.md`
