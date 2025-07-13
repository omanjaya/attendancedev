# VPS Deployment Guide

Complete guide for deploying the Attendance Management System to a VPS.

## Prerequisites

- Fresh Ubuntu 22.04 VPS
- Domain name pointed to VPS IP
- Root access to VPS
- Git repository access

## Step 1: VPS Initial Setup

1. **Connect to your VPS:**
```bash
ssh root@your-vps-ip
```

2. **Download and run the setup script:**
```bash
wget https://raw.githubusercontent.com/omanjaya/attendance-system/master/setup-vps.sh
chmod +x setup-vps.sh
sudo bash setup-vps.sh
```

3. **Add deployment key to your Git repository:**
```bash
cat /home/attendance/.ssh/id_ed25519.pub
```
Copy this key and add it to your GitHub repository's Deploy Keys.

## Step 2: Clone and Configure Application

1. **Switch to attendance user:**
```bash
sudo su - attendance
```

2. **Clone repository:**
```bash
git clone git@github.com:omanjaya/attendance-system.git /var/www/attendance-system
cd /var/www/attendance-system
```

3. **Copy and configure environment:**
```bash
cp .env.production.example .env
nano .env
```

Update these key values in `.env`:
```env
APP_URL=https://your-domain.com
DB_PASSWORD=your_secure_password
MAIL_HOST=your-smtp-host
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
```

4. **Generate application key:**
```bash
php artisan key:generate
```

## Step 3: Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm ci --production

# Build frontend assets
npm run build
```

## Step 4: Database Setup

```bash
# Run migrations
php artisan migrate

# Seed initial data (admin user, roles, permissions)
php artisan db:seed

# Create storage link
php artisan storage:link
```

## Step 5: Set Permissions

```bash
# Set proper ownership
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Set application directory ownership
sudo chown -R attendance:www-data /var/www/attendance-system
```

## Step 6: Configure SSL

```bash
# Install SSL certificate with Certbot
sudo certbot --nginx -d your-domain.com

# Test SSL renewal
sudo certbot renew --dry-run
```

## Step 7: Update Nginx Configuration

Edit `/etc/nginx/sites-available/attendance` and update:
- Replace `your-domain.com` with your actual domain
- Update SSL certificate paths if different

```bash
sudo nano /etc/nginx/sites-available/attendance
sudo nginx -t
sudo systemctl reload nginx
```

## Step 8: Start Services

```bash
# Restart all services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo systemctl restart redis-server
sudo supervisorctl restart all
```

## Step 9: Verify Deployment

1. **Check health endpoint:**
```bash
curl https://your-domain.com/api/health
```

2. **Access the application:**
Open `https://your-domain.com` in your browser

3. **Default admin login:**
- Email: `admin@admin.com`
- Password: `password`

**⚠️ Important: Change the default admin password immediately!**

## Step 10: Post-Deployment Configuration

1. **Create admin user:**
```bash
php artisan make:admin-user
```

2. **Configure queue workers:**
```bash
sudo supervisorctl status
```

3. **Set up log rotation:**
Logs are automatically rotated. Check `/etc/logrotate.d/attendance`

4. **Configure backups:**
```bash
# Test backup
php artisan backup:run

# Schedule daily backups in crontab
crontab -e
# Add: 0 2 * * * cd /var/www/attendance-system && php artisan backup:run
```

## Ongoing Deployment

For future updates, use the deployment script:

```bash
cd /var/www/attendance-system
./deploy.sh
```

## Monitoring and Maintenance

### Health Checks

- Health endpoint: `https://your-domain.com/api/health`
- Ping endpoint: `https://your-domain.com/api/ping`

### Log Files

- Application logs: `/var/log/attendance/`
- Nginx logs: `/var/log/nginx/attendance_*.log`
- PHP-FPM logs: `/var/log/attendance/php-error.log`

### Performance Monitoring

```bash
# Check queue status
php artisan queue:work --once

# Monitor worker processes
sudo supervisorctl status

# Check database performance
php artisan monitor:db

# Check Redis memory
redis-cli info memory
```

### Backup and Recovery

```bash
# Manual backup
php artisan backup:run

# List backups
php artisan backup:list

# Restore from backup
php artisan backup:restore backup_name.zip
```

## Troubleshooting

### Common Issues

1. **500 Error:**
   - Check PHP-FPM logs: `tail -f /var/log/attendance/php-error.log`
   - Check permissions: `sudo chown -R www-data:www-data storage bootstrap/cache`

2. **Database Connection Error:**
   - Verify PostgreSQL is running: `sudo systemctl status postgresql`
   - Check database credentials in `.env`

3. **Queue Jobs Not Processing:**
   - Check worker status: `sudo supervisorctl status`
   - Restart workers: `sudo supervisorctl restart attendance-worker:*`

4. **SSL Certificate Issues:**
   - Renew certificate: `sudo certbot renew`
   - Check certificate status: `sudo certbot certificates`

### Performance Optimization

1. **Enable OPcache:**
```bash
# Add to PHP configuration
echo "opcache.enable=1" >> /etc/php/8.2/fpm/conf.d/99-opcache.ini
echo "opcache.memory_consumption=256" >> /etc/php/8.2/fpm/conf.d/99-opcache.ini
sudo systemctl restart php8.2-fpm
```

2. **Optimize database:**
```bash
php artisan optimize:db
```

3. **Monitor resource usage:**
```bash
htop
iotop
```

## Security Checklist

- [ ] Changed default admin password
- [ ] SSL certificate installed and working
- [ ] Firewall configured (only ports 22, 80, 443 open)
- [ ] Database password is strong
- [ ] Regular backups scheduled
- [ ] Log monitoring configured
- [ ] Security headers enabled in Nginx
- [ ] PHP version is up to date
- [ ] Application key generated
- [ ] Debug mode disabled in production

## Support

For issues or questions:

1. Check logs first
2. Review this deployment guide
3. Check the application documentation
4. Create an issue in the repository

## Maintenance Schedule

- **Daily:** Automated backups
- **Weekly:** Check logs and system resources
- **Monthly:** Update system packages and review security
- **Quarterly:** Review and update SSL certificates