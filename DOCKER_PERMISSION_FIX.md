# Docker Permission Fix

## Problem

Error saat mengakses `/admin/services`:
```
Server error: permission denied while trying to connect to the docker API at unix:///var/run/docker.sock
```

## Root Cause

Container backend tidak punya permission untuk mengakses Docker socket di host karena:
- Docker socket owned by `root:root` (GID 0)
- Container perlu akses dengan `root:docker` (GID 983)

## Solution 1: Fix Host Docker Socket Permissions (Recommended)

Ubah ownership docker socket di **host** (bukan di container):

```bash
# Check current permissions
ls -la /var/run/docker.sock
# Output: srw-rw---- 1 root root ...

# Fix ownership (run on HOST)
sudo chown root:docker /var/run/docker.sock
sudo chmod 660 /var/run/docker.sock

# Verify
ls -la /var/run/docker.sock
# Output: srw-rw---- 1 root docker ...
```

**Permanent Fix** - Add to system startup:

```bash
# Create systemd service
sudo nano /etc/systemd/system/docker-socket-perms.service
```

Paste content:
```ini
[Unit]
Description=Fix Docker Socket Permissions
After=docker.service
Requires=docker.service

[Service]
Type=oneshot
ExecStart=/bin/sh -c 'chown root:docker /var/run/docker.sock && chmod 660 /var/run/docker.sock'
RemainAfterExit=yes

[Install]
WantedBy=multi-user.target
```

Enable service:
```bash
sudo systemctl daemon-reload
sudo systemctl enable docker-socket-perms.service
sudo systemctl start docker-socket-perms.service
```

## Solution 2: Restart Backend Container

Setelah fix di host, restart container backend:

```bash
docker restart attendancedev-backend

# Verify docker access works
docker exec attendancedev-backend su www-data -s /bin/sh -c 'docker ps'
```

## Solution 3: Alternative - Run Container as Root (Not Recommended)

Update `docker-compose.yml`:

```yaml
backend:
  # ... existing config ...
  user: "0:983"  # root user, docker group
```

Then restart:
```bash
docker compose restart backend
```

**Warning**: Running as root is not recommended for security reasons.

## Verification

1. Check socket permissions inside container:
```bash
docker exec attendancedev-backend ls -la /var/run/docker.sock
# Should show: srw-rw---- 1 root docker ...
```

2. Test docker access:
```bash
docker exec attendancedev-backend su www-data -s /bin/sh -c 'docker ps'
# Should list containers (no permission error)
```

3. Test in browser:
- Navigate to `/admin/services`
- Should show container stats without errors

## Current Status

✅ **Error Handling Improved** (Updated 2025-12-10)
- All Docker command endpoints detect permission errors
- Shows clear error message: "Docker permission denied. See DOCKER_PERMISSION_FIX.md for solution."
- Returns HTTP 200 with `success: false` instead of HTTP 500 (graceful degradation)
- System continues to function normally (non-critical feature)
- Affected endpoints:
  - `/api/v1/admin/services` - Service status dashboard
  - `/api/v1/admin/services/{service}` - Single service status
  - `/api/v1/admin/services/{service}/logs` - Container logs
  - `/api/v1/admin/services/{service}/restart` - Restart container
  - `/api/v1/admin/services/{service}/start` - Start container
  - `/api/v1/admin/services/{service}/stop` - Stop container
  - `/api/v1/admin/services/{service}/metrics` - Container metrics
  - `/api/v1/admin/services/restart-all` - Restart all containers

❌ **Docker Monitoring Unavailable**
- Container stats not accessible
- Container logs unavailable
- System health checks limited
- Needs host-level permission fix

## Impact

- **Low**: System masih berfungsi normal
- **Only affects**: `/admin/services` monitoring dashboard
- **Workaround**: Use `docker ps` manual di terminal

## Files Modified

✅ `SystemMonitoringService.php` - Improved error detection for service status
✅ `SystemController.php` - Improved error handling for all Docker command endpoints
✅ `docker-entrypoint.sh` - Auto-fix GID mismatch at container startup
✅ `Dockerfile` - Adjusted docker group GID to match host
✅ Error messages now user-friendly with clear guidance

---

**Recommendation**: Gunakan **Solution 1** untuk permanent fix.
