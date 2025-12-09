---
description: Comprehensive project health check and maintenance workflow for attendancedev
---

# 🏥 Project Health Check Workflow

Gunakan workflow ini untuk memastikan project attendance tetap sehat, secure, dan production-ready.

---

## 📋 Quick Health Check Commands

```bash
# 1. Check Backend Health
docker exec attendancedev-backend php artisan about
docker exec attendancedev-backend php artisan route:list --path=api

# 2. Check Database
docker exec attendancedev-backend php artisan migrate:status

# 3. Check Frontend Build
docker exec attendancedev-frontend npm run build

# 4. Check TypeScript
docker exec attendancedev-frontend npx tsc --noEmit

# 5. Security Audit
docker exec attendancedev-backend composer audit
```

---

## 🔐 Security Checklist

### 1. Environment Variables (CRITICAL)

- [ ] `APP_DEBUG=false` di production
- [ ] `APP_ENV=production` di production
- [ ] `APP_KEY` unique dan secure
- [ ] Database credentials tidak hardcoded
- [ ] `DEEPFACE_THRESHOLD` set ke 0.68+ (recommended)

### 2. Authentication

- [ ] Password policy enforced (min 8 chars, mixed case, special chars)
- [ ] Rate limiting aktif untuk login (5 attempts/15 min)
- [ ] Session timeout configured (30 min idle)
- [ ] Concurrent sessions limited (max 3)

### 3. Face Recognition Security

- [ ] Liveness detection enabled (`DEEPFACE_LIVENESS_ENABLED=true`)
- [ ] Face data encrypted (`ENCRYPT_FACE_DATA=true`)
- [ ] Min confidence threshold 0.8+ (`FACE_DETECTION_MIN_CONFIDENCE`)

### 4. API Security

- [ ] HTTPS required (`API_REQUIRE_HTTPS=true`)
- [ ] CORS properly configured
- [ ] Rate limiting on API endpoints

### 5. File Upload Security

- [ ] MIME type validation enabled
- [ ] Max file size enforced (10MB)
- [ ] Suspicious files quarantined

---

## 🧪 Testing Checklist

### Backend Tests

```bash
# Run all tests
docker exec attendancedev-backend php artisan test

# Run specific suites
docker exec attendancedev-backend php artisan test --testsuite=Unit
docker exec attendancedev-backend php artisan test --testsuite=Feature

# Run with coverage
docker exec attendancedev-backend php artisan test --coverage
```

### Critical Test Files (MUST PASS)

- [ ] `tests/Feature/Auth/AuthenticationTest.php`
- [ ] `tests/Feature/AttendanceWorkflowTest.php`
- [ ] `tests/Feature/Api/AttendanceApiTest.php`
- [ ] `tests/Unit/Services/FaceRecognitionServiceTest.php`

### Frontend Tests

```bash
cd frontend && npm run test
cd frontend && npm run test:e2e
```

---

## 🔄 Maintenance Tasks

### Weekly

1. **Security Audit**

   ```bash
   docker exec attendancedev-backend composer audit
   cd frontend && npm audit
   ```

2. **Database Cleanup**

   ```bash
   # Clear expired sessions
   docker exec attendancedev-backend php artisan session:gc
   
   # Clear old logs
   docker exec attendancedev-backend php artisan log:clear
   ```

3. **Cache Management**

   ```bash
   # Clear and rebuild cache
   docker exec attendancedev-backend php artisan cache:clear
   docker exec attendancedev-backend php artisan config:cache
   docker exec attendancedev-backend php artisan route:cache
   docker exec attendancedev-backend php artisan view:cache
   ```

### Monthly

1. **Dependency Updates**

   ```bash
   # Check outdated packages
   docker exec attendancedev-backend composer outdated
   cd frontend && npm outdated
   ```

2. **Database Optimization**

   ```bash
   docker exec attendancedev-backend php artisan db:optimize
   ```

3. **Review Audit Logs**
   - Check for suspicious login attempts
   - Review permission changes
   - Check bypass activity logs

---

## 🚨 Production Deployment Checklist

### Pre-Deployment

- [ ] All tests passing on CI
- [ ] TypeScript build successful (no errors)
- [ ] Security audit passed
- [ ] Environment variables verified
- [ ] Database backup created

### Deployment Steps

```bash
# 1. Pull latest images
docker compose -f docker-compose.hub.yml pull

# 2. Backup database (CRITICAL)
docker exec attendancedev-postgres pg_dump -U postgres attendance > backup_$(date +%Y%m%d).sql

# 3. Deploy
docker compose -f docker-compose.hub.yml up -d

# 4. Run migrations
docker exec attendancedev-backend php artisan migrate --force

# 5. Clear caches
docker exec attendancedev-backend php artisan optimize:clear
docker exec attendancedev-backend php artisan optimize

# 6. Verify health
docker exec attendancedev-backend php artisan about
```

### Post-Deployment

- [ ] Check application logs for errors
- [ ] Verify critical features work
- [ ] Monitor for 15-30 minutes
- [ ] Check database connections

---

## 📊 Monitoring Points

### Application Health

| Metric | Expected | Alert If |
|--------|----------|----------|
| Response time | < 500ms | > 2s |
| Error rate | < 1% | > 5% |
| Database connections | < 80% | > 90% |
| Memory usage | < 70% | > 85% |
| Disk usage | < 70% | > 85% |

### Security Monitoring

| Event | Action |
|-------|--------|
| 10+ failed logins | Alert admin |
| New device login | Notify user |
| Permission change | Log with details |
| Face recognition failure spike | Review logs |

---

## 🐛 Troubleshooting Guide

### Common Issues

#### 1. 500 Error on API

```bash
# Check logs
docker logs attendancedev-backend --tail 100

# Clear cache
docker exec attendancedev-backend php artisan optimize:clear
```

#### 2. Database Connection Failed

```bash
# Check PostgreSQL
docker logs attendancedev-postgres

# Test connection
docker exec attendancedev-backend php artisan tinker --execute="DB::connection()->getPdo()"
```

#### 3. Face Recognition Failing

```bash
# Check DeepFace service
docker logs attendancedev-deepface

# Test health endpoint
curl http://localhost:8001/health
```

#### 4. Frontend Build Failed

```bash
# Check for TypeScript errors
docker exec attendancedev-frontend npx tsc --noEmit

# Check logs
docker logs attendancedev-frontend
```

---

## 📝 Notes

- Last updated: {{ current_date }}
- Maintained by: Development Team
- Emergency contact: Check `SECURITY_ADMIN_NOTIFICATION_EMAIL` in env
