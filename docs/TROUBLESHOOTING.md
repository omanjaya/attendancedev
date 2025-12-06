# ❓ Troubleshooting Guide

Panduan mengatasi masalah umum.

## 🔴 Common Errors

### Database Connection Error

**Gejala:**

```
SQLSTATE[08006] Connection refused
```

**Solusi:**

```bash
# 1. Check PostgreSQL status
sudo systemctl status postgresql
sudo systemctl start postgresql

# 2. Verify credentials di .env
cat backend/.env | grep DB_

# 3. Test connection
psql -U postgres -h localhost -d attendance_system
```

### Port Already in Use

**Gejala:**

```
Address already in use :8000 atau :5173
```

**Solusi:**

```bash
# Find process
lsof -i :8000
lsof -i :5173

# Kill process
kill -9 <PID>

# Atau kill semua dev processes
pkill -f "php artisan serve"
pkill -f "vite"
```

### CORS Error

**Gejala:**

```
Access to XMLHttpRequest blocked by CORS policy
```

**Solusi:**

1. Check `backend/config/cors.php`
2. Pastikan `SANCTUM_STATEFUL_DOMAINS` di `.env` include frontend domain
3. Clear config cache:

```bash
cd backend
php artisan config:clear
```

### Permission Denied (Storage)

**Gejala:**

```
Permission denied: storage/logs/laravel.log
```

**Solusi:**

```bash
cd backend
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 500 Internal Server Error

**Solusi:**

```bash
# 1. Check Laravel logs
tail -50 backend/storage/logs/laravel.log

# 2. Enable debug mode temporarily
# Edit .env: APP_DEBUG=true

# 3. Clear all caches
cd backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Face Recognition Not Working

**Gejala:**

- Face verification timeout
- Connection refused to :8001

**Solusi:**

```bash
# 1. Check DeepFace service
cd python-services/face-recognition
./check-cluster.sh

# 2. Restart cluster
./stop-cluster.sh
./start-cluster.sh

# 3. Check logs
tail -f logs/*.log
```

### Queue Not Processing

**Gejala:**

- Jobs stuck in queue
- Notifications not sent

**Solusi:**

```bash
# 1. Check queue worker
sudo supervisorctl status

# 2. Restart workers
sudo supervisorctl restart attendance-worker:*

# 3. Manual process (debug)
cd backend
php artisan queue:work --once
```

### Redis Connection Error

**Gejala:**

```
Connection refused [tcp://127.0.0.1:6379]
```

**Solusi:**

```bash
# 1. Check Redis status
sudo systemctl status redis-server
sudo systemctl start redis-server

# 2. Test connection
redis-cli ping

# 3. Check password
redis-cli
AUTH your_password
```

### Migration Error

**Gejala:**

```
Table already exists / Column not found
```

**Solusi:**

```bash
cd backend

# Option 1: Fresh migration (DEVELOPMENT ONLY)
php artisan migrate:fresh --seed

# Option 2: Rollback and re-run
php artisan migrate:rollback
php artisan migrate

# Option 3: Skip specific migration
php artisan migrate --pretend  # Preview dulu
```

### API 401 Unauthorized

**Solusi:**

1. Token expired? Login ulang
2. Check token di request header: `Authorization: Bearer {token}`
3. Verify token di database:

```bash
cd backend
php artisan tinker
>>> \App\Models\PersonalAccessToken::all();
```

### API 403 Forbidden

**Solusi:**

1. Check user role dan permissions
2. Verify di seeder apakah permission sudah di-assign:

```bash
cd backend
php artisan permission:cache-reset
```

---

## 🛠️ Diagnostics Commands

### System Status

```bash
# All services
sudo systemctl status nginx php8.2-fpm postgresql redis-server supervisor

# Ports in use
sudo netstat -tlpn | grep -E ':80|:443|:5432|:6379|:8000|:8001'

# Disk space
df -h

# Memory
free -h

# CPU
top -bn1 | head -20
```

### Application Health

```bash
# Backend health check
curl http://localhost:8000/api/health

# Frontend accessible
curl -I http://localhost:5173

# DeepFace health
for port in 8001 8002 8003 8004 8005; do
  curl -s http://localhost:$port/health && echo " - Port $port OK"
done
```

### Logs Location

| Service | Log Path |
|---------|----------|
| Laravel | `backend/storage/logs/laravel.log` |
| Nginx | `/var/log/nginx/error.log` |
| PostgreSQL | `/var/log/postgresql/` |
| PHP-FPM | `/var/log/php8.2-fpm.log` |
| Redis | `/var/log/redis/redis-server.log` |
| DeepFace | `python-services/face-recognition/logs/` |
| Supervisor | `/var/log/supervisor/` |

---

## 🔄 Reset Commands

### Development Reset

```bash
# Reset database
cd backend
php artisan migrate:fresh --seed

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload

# Reset frontend
cd frontend
rm -rf node_modules .vite dist
npm install
```

### Production Reset (Careful!)

```bash
cd backend

# Clear application cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart php8.2-fpm nginx
sudo supervisorctl restart all
```

---

## 📞 Getting Help

1. Check logs terlebih dahulu
2. Reproduksi error dengan `APP_DEBUG=true`
3. Cari error message di dokumentasi
4. Buat issue di GitHub dengan:
   - Error message lengkap
   - Steps to reproduce
   - Environment info (OS, PHP version, etc)
