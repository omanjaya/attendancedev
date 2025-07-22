# VPS Deployment Guide - Attendance Management System

Complete deployment guide for deploying the attendance management system on VPS IP **168.231.121.98** with domain **absensi.manufac.id**.

## 🚀 Quick Start

### Step 1: Upload Files to VPS
```bash
# Upload all project files to your VPS
scp -r /path/to/attendancedev/* root@168.231.121.98:/root/
```

### Step 2: Run VPS Setup Script
```bash
# Connect to VPS
ssh root@168.231.121.98

# Navigate to project directory
cd /root/attendancedev

# Make setup script executable
chmod +x setup-vps.sh

# Run complete VPS setup (this will take 15-20 minutes)
./setup-vps.sh
```

### Step 3: Deploy Application
```bash
# Switch to deploy user
su - deploy

# Navigate to application directory
cd /var/www/attendance-system

# Copy your application files here
cp -r /root/attendancedev/* .

# Run deployment script
./deploy.sh
```

### Step 4: Configure SSL Certificate
```bash
# Exit from deploy user back to root
exit

# Configure SSL with Let's Encrypt
certbot --nginx -d absensi.manufac.id

# Verify SSL is working
curl -I https://absensi.manufac.id
```

## 📋 What the Setup Script Installs

### System Components
- ✅ **Ubuntu 22.04 LTS** with latest updates
- ✅ **PHP 8.2** with all required extensions
- ✅ **PostgreSQL 15** with optimized configuration
- ✅ **Redis 7** for caching and session management
- ✅ **Nginx** with security headers and optimized configuration
- ✅ **Node.js 20** for frontend asset compilation
- ✅ **Composer** for PHP dependency management
- ✅ **Supervisor** for queue worker management

### Security Features
- ✅ **UFW Firewall** with restricted access
- ✅ **Fail2ban** for intrusion prevention
- ✅ **SSL/TLS** ready with Certbot
- ✅ **Security headers** in Nginx configuration
- ✅ **Deploy user** with restricted permissions
- ✅ **Secure file permissions** for application

### Database Configuration
- 📊 **Database Name**: `attendance_system`
- 👤 **Database User**: `attendance_user`
- 🔑 **Auto-generated secure passwords** for database and Redis
- 🏗️ **Optimized PostgreSQL settings** for performance

### Application Configuration
- 📁 **App Directory**: `/var/www/attendance-system`
- 🌐 **Domain**: `absensi.manufac.id`
- 📍 **VPS IP**: `168.231.121.98`
- 🔄 **Queue Workers**: 2 processes with auto-restart
- ⏰ **Scheduled Tasks**: Laravel scheduler via cron
- 📝 **Logs**: Centralized logging in `/var/log/attendance-system`

## 🔧 Manual Configuration Steps

### 1. Environment Configuration
After running the setup script, you may need to configure:

```bash
# Edit production environment file
nano /var/www/attendance-system/.env.production

# Configure email settings
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password

# Configure any additional settings as needed
```

### 2. Database Seeding (Optional)
```bash
# Switch to deploy user
su - deploy
cd /var/www/attendance-system

# Seed initial data
php artisan db:seed

# Create admin user
php artisan make:user --admin
```

### 3. Queue Worker Monitoring
```bash
# Check queue worker status
sudo supervisorctl status attendance-queue-worker:*

# View queue logs
tail -f /var/log/attendance-system/queue.log

# Restart queue workers if needed
sudo supervisorctl restart attendance-queue-worker:*
```

## 🔍 Verification Checklist

After deployment, verify these components are working:

### System Health
- [ ] **Web Server**: Visit `http://168.231.121.98` or `http://absensi.manufac.id`
- [ ] **Database**: Check PostgreSQL connection
- [ ] **Redis**: Verify caching is working
- [ ] **Queue Workers**: Confirm background jobs are processing
- [ ] **SSL Certificate**: HTTPS is properly configured

### Application Features
- [ ] **User Login**: Admin panel accessible
- [ ] **Face Recognition**: Camera access working
- [ ] **Database Migrations**: All tables created
- [ ] **File Uploads**: Storage directory writable
- [ ] **Email Notifications**: SMTP configured correctly

### Performance
- [ ] **Page Load Times**: Under 2 seconds
- [ ] **Database Queries**: Optimized and cached
- [ ] **Static Assets**: Properly cached
- [ ] **Memory Usage**: Under 512MB for PHP processes

## 🛠️ Maintenance Commands

### Application Updates
```bash
# Switch to deploy user
su - deploy
cd /var/www/attendance-system

# Update application
git pull origin main
./deploy.sh
```

### Database Backup
```bash
# Create database backup
sudo -u postgres pg_dump attendance_system > /var/backups/attendance-system/db_backup_$(date +%Y%m%d).sql

# Restore from backup
sudo -u postgres psql attendance_system < /var/backups/attendance-system/db_backup_20240101.sql
```

### Log Management
```bash
# View application logs
tail -f /var/log/nginx/absensi.manufac.id.access.log
tail -f /var/log/nginx/absensi.manufac.id.error.log
tail -f /var/www/attendance-system/storage/logs/laravel.log

# Clear old logs
find /var/log/attendance-system -name "*.log" -mtime +30 -delete
```

### Service Management
```bash
# Restart all services
sudo systemctl restart nginx php8.2-fpm postgresql redis-server
sudo supervisorctl restart all

# Check service status
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status postgresql
sudo systemctl status redis-server
```

## 🔒 Security Best Practices

### Server Hardening
1. **Change default SSH port**
2. **Disable root SSH login** after setup
3. **Enable automatic security updates**
4. **Regular security audits**
5. **Monitor access logs**

### Application Security
1. **Regular updates** of dependencies
2. **Strong password policies**
3. **Two-factor authentication** enabled
4. **Regular database backups**
5. **Monitor for suspicious activity**

## 🆘 Troubleshooting

### Common Issues

#### 1. Permission Errors
```bash
# Fix file permissions
sudo chown -R deploy:www-data /var/www/attendance-system
sudo chmod -R 755 /var/www/attendance-system
sudo chmod -R 775 /var/www/attendance-system/storage
sudo chmod -R 775 /var/www/attendance-system/bootstrap/cache
```

#### 2. Database Connection Issues
```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Verify database credentials
sudo -u postgres psql -c "\l"

# Test connection
sudo -u deploy psql -h localhost -U attendance_user -d attendance_system
```

#### 3. Nginx Configuration Issues
```bash
# Test Nginx configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx

# Check error logs
tail -f /var/log/nginx/error.log
```

#### 4. PHP-FPM Issues
```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Check PHP error logs
tail -f /var/log/php8.2-fpm.log
```

### Performance Issues

#### 1. High Memory Usage
```bash
# Check memory usage
free -h
htop

# Optimize PHP-FPM settings
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

#### 2. Slow Database Queries
```bash
# Enable PostgreSQL query logging
sudo nano /etc/postgresql/15/main/postgresql.conf

# Add these lines:
# log_statement = 'all'
# log_duration = on
# log_min_duration_statement = 1000

# Restart PostgreSQL
sudo systemctl restart postgresql
```

## 📞 Support Information

### Generated Credentials
The setup script generates secure passwords that are displayed at the end of installation. **Make sure to save these credentials:**

- **Database Password**: Auto-generated 32-character password
- **Redis Password**: Auto-generated 32-character password
- **SSL Certificate**: Auto-configured with Let's Encrypt

### Important File Locations
- **Application**: `/var/www/attendance-system`
- **Nginx Config**: `/etc/nginx/sites-available/absensi.manufac.id`
- **Environment File**: `/var/www/attendance-system/.env.production`
- **Queue Config**: `/etc/supervisor/conf.d/attendance-queue.conf`
- **Logs**: `/var/log/attendance-system/`
- **Backups**: `/var/backups/attendance-system/`

### System Requirements Met
- ✅ **PHP 8.2+** with all required extensions
- ✅ **PostgreSQL 15+** for production database
- ✅ **Redis** for caching and sessions
- ✅ **Node.js 20+** for frontend builds
- ✅ **Nginx** as web server
- ✅ **SSL Certificate** for HTTPS
- ✅ **Supervisor** for background processes

This deployment setup provides a production-ready environment with enterprise-grade security, performance optimization, and monitoring capabilities for the attendance management system.