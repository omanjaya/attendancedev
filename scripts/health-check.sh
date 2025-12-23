#!/bin/bash
# Health check script for attendance system
# Run via cron every 5 minutes: */5 * * * * /opt/attendancedev/scripts/health-check.sh

cd /opt/attendancedev

LOG_FILE="/var/log/attendance-health.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

log() {
    echo "[$TIMESTAMP] $1" >> "$LOG_FILE"
}

# Check if website returns 200
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 https://absensi.patra.co.id/ 2>/dev/null)

if [ "$HTTP_CODE" != "200" ]; then
    log "ERROR: Website returned $HTTP_CODE - Restarting containers..."

    # Restart all containers
    docker compose up -d >> "$LOG_FILE" 2>&1

    sleep 10

    # Verify fix
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 https://absensi.patra.co.id/ 2>/dev/null)

    if [ "$HTTP_CODE" == "200" ]; then
        log "SUCCESS: Containers restarted, website now returns 200"
    else
        log "CRITICAL: Restart failed, website still returns $HTTP_CODE"
    fi
else
    # Only log every hour to reduce log size
    MINUTE=$(date '+%M')
    if [ "$MINUTE" == "00" ]; then
        log "OK: Website healthy (200)"
    fi
fi

# Copy icons if backend was recreated (check if heart icon exists)
if ! docker exec attendancedev-backend ls /var/www/html/resources/views/components/icons/heart.blade.php > /dev/null 2>&1; then
    log "WARNING: Icons missing, copying..."
    for icon in arrow-down-tray arrow-trending-up arrow-uturn-left banknotes calculator clipboard-document-list minus-circle signal user-circle user-plus heart; do
        docker cp /opt/attendancedev/backend/resources/views/components/icons/${icon}.blade.php attendancedev-backend:/var/www/html/resources/views/components/icons/${icon}.blade.php 2>/dev/null
    done
    docker exec attendancedev-backend mkdir -p /var/www/html/resources/views/components/forms 2>/dev/null
    docker cp /opt/attendancedev/backend/resources/views/components/forms/demo-form.blade.php attendancedev-backend:/var/www/html/resources/views/components/forms/demo-form.blade.php 2>/dev/null
    docker exec attendancedev-backend sh -c 'chown -R www-data:www-data /var/www/html/resources/views/components/' 2>/dev/null
    docker exec attendancedev-backend php artisan view:clear > /dev/null 2>&1
    log "Icons copied successfully"
fi
