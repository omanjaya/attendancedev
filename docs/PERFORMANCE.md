# Performance Optimization Guide

Panduan lengkap untuk mengoptimalkan performa Attendance System.

## 📊 Target Performance

| Metric | Target | Current |
|--------|--------|---------|
| API Response Time (avg) | < 100ms | - |
| Page Load Time | < 2s | - |
| Face Recognition | < 3s | - |
| Database Query | < 50ms | - |
| Concurrent Users | 500+ | - |

## 🚀 Quick Optimization

```bash
# Jalankan script optimasi
./scripts/optimize-laravel.sh

# Atau via Docker
docker-compose exec backend /var/www/html/scripts/optimize-laravel.sh
```

## 🔧 Layer-by-Layer Optimization

### 1. PHP & Laravel

#### OPcache Configuration

File: `docker/php/php.ini`

```ini
; Maximum performance settings
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0        ; Disable in production
opcache.revalidate_freq=0
opcache.interned_strings_buffer=16
opcache.save_comments=0              ; May break some packages
```

#### Preloading (PHP 7.4+)

```ini
opcache.preload=/var/www/html/preload.php
opcache.preload_user=www-data
```

#### JIT Compilation (PHP 8.0+)

```ini
opcache.jit_buffer_size=100M
opcache.jit=1255
```

### 2. PostgreSQL Tuning

File: `docker/postgres/postgresql.conf`

**Untuk VPS 4GB RAM:**

```ini
shared_buffers = 1GB
effective_cache_size = 3GB
maintenance_work_mem = 256MB
work_mem = 16MB
```

**Untuk VPS 8GB RAM:**

```ini
shared_buffers = 2GB
effective_cache_size = 6GB
maintenance_work_mem = 512MB
work_mem = 32MB
```

**Query Performance:**

```sql
-- Analyze all tables
ANALYZE;

-- Check index usage
SELECT 
    schemaname, tablename, indexname, 
    idx_scan, idx_tup_read, idx_tup_fetch
FROM pg_stat_user_indexes
ORDER BY idx_scan DESC;

-- Find slow queries
SELECT query, calls, mean_time, total_time
FROM pg_stat_statements
ORDER BY mean_time DESC
LIMIT 20;
```

### 3. Redis Caching

#### Session Storage

```env
SESSION_DRIVER=redis
SESSION_CONNECTION=session
```

#### Cache Configuration

```env
CACHE_DRIVER=redis
REDIS_CACHE_DB=1
```

#### Queue via Redis (faster than database)

```env
QUEUE_CONNECTION=redis
```

### 4. Nginx Optimization

#### Enable Caching

```nginx
# Cache API responses
location ~ ^/api/v1/(locations|holidays) {
    proxy_cache api_cache;
    proxy_cache_valid 200 5m;
    add_header X-Cache-Status $upstream_cache_status;
}
```

#### Enable Gzip/Brotli

```nginx
gzip on;
gzip_comp_level 5;
gzip_min_length 256;
```

#### Connection Pooling

```nginx
upstream php-fpm {
    server backend:9000;
    keepalive 32;
}
```

### 5. Frontend Optimization

#### Vite Build Optimization

```javascript
// vite.config.ts
export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['react', 'react-dom'],
          ui: ['@radix-ui/react-dialog', '@radix-ui/react-dropdown-menu'],
        },
      },
    },
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
  },
});
```

#### Code Splitting

```typescript
// Lazy load heavy components
const Calendar = lazy(() => import('./components/Calendar'));
const Reports = lazy(() => import('./pages/Reports'));
```

### 6. DeepFace Optimization

#### Model Caching

```python
# Pre-load models at startup
from deepface import DeepFace
DeepFace.build_model("ArcFace")
```

#### Request Rate Limiting

```nginx
limit_req_zone $binary_remote_addr zone=deepface:10m rate=10r/s;
```

#### Multiple Instances

```yaml
# docker-compose.prod.yml
deepface-1:
  deploy:
    replicas: 2
```

## 📈 Monitoring

### Laravel Metrics

```php
// Add to AppServiceProvider
use Illuminate\Support\Facades\DB;

DB::listen(function ($query) {
    if ($query->time > 100) { // Log queries > 100ms
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time,
        ]);
    }
});
```

### Database Performance

```sql
-- Enable pg_stat_statements
CREATE EXTENSION IF NOT EXISTS pg_stat_statements;

-- Check cache hit ratio (should be > 99%)
SELECT 
    sum(heap_blks_hit) / (sum(heap_blks_hit) + sum(heap_blks_read)) * 100 
    as cache_hit_ratio
FROM pg_statio_user_tables;
```

### Redis Monitoring

```bash
# Check memory usage
redis-cli INFO memory

# Check hit ratio
redis-cli INFO stats | grep keyspace
```

### Nginx Metrics

```bash
# Enable status module
location /nginx_status {
    stub_status on;
    allow 127.0.0.1;
    deny all;
}
```

## ⚡ Quick Wins

1. **Enable OPcache** - 2-5x faster PHP execution
2. **Use Redis for sessions** - Remove database load
3. **Cache config/routes** - Faster bootstrap
4. **Enable Gzip** - 70% smaller responses
5. **Use CDN** - Reduce server load for static assets
6. **Database indexes** - Already added in migrations
7. **Connection pooling** - Reuse database connections

## 🔍 Troubleshooting

### Slow API Responses

```bash
# Check slow query log
tail -f /var/log/postgresql/slow.log

# Check PHP-FPM status
curl localhost/fpm-status
```

### High Memory Usage

```bash
# Check container memory
docker stats

# Check PHP memory per request
php -r "echo memory_get_peak_usage(true);"
```

### Database Connection Issues

```bash
# Check active connections
SELECT count(*) FROM pg_stat_activity;

# Check connection limits
SHOW max_connections;
```

## 📊 Benchmarks

### API Endpoint Benchmarks

```bash
# Install wrk
apt install wrk

# Test API performance
wrk -t12 -c400 -d30s http://localhost/api/v1/health

# Test with authentication
wrk -t12 -c100 -d30s -H "Authorization: Bearer TOKEN" \
    http://localhost/api/v1/attendances
```

### Expected Results (4 Core VPS)

| Endpoint | RPS | Latency |
|----------|-----|---------|
| /health | 10,000+ | <1ms |
| /api/v1/locations | 2,000+ | <10ms |
| /api/v1/attendances | 500+ | <50ms |
| Face verification | 30+ | <3s |

## 🔄 Scaling

### Horizontal Scaling

```yaml
# Scale backend
docker-compose up -d --scale backend=3

# Scale DeepFace
docker-compose up -d --scale deepface-1=3
```

### Vertical Scaling

- Upgrade VPS RAM for PostgreSQL
- Upgrade CPU for DeepFace
- Add SSD storage for faster I/O
