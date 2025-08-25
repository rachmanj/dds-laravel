# DDS Laravel Application - IT Administrator Installation Guide

## 📋 **Overview**

This guide provides step-by-step instructions for IT administrators to install, configure, and deploy the Document Distribution System (DDS) Laravel application in a production environment.

## 🎯 **System Requirements**

### **Server Requirements**

-   **Operating System**: Ubuntu 20.04+ / CentOS 8+ / Windows Server 2019+
-   **PHP**: 8.1+ with required extensions
-   **Web Server**: Apache 2.4+ or Nginx 1.18+
-   **Database**: MySQL 8.0+ or MariaDB 10.5+
-   **Memory**: Minimum 4GB RAM, Recommended 8GB+
-   **Storage**: Minimum 50GB available space
-   **Network**: HTTPS support with valid SSL certificate

### **PHP Extensions Required**

```bash
# Required PHP extensions
php-bcmath
php-curl
php-dom
php-fileinfo
php-gd
php-intl
php-json
php-mbstring
php-mysql
php-opcache
php-pdo
php-sqlite3
php-xml
php-zip
```

### **Client Requirements**

-   **Browsers**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
-   **JavaScript**: Enabled (required for dashboard functionality)
-   **Screen Resolution**: Minimum 1024x768, Recommended 1920x1080+

## 🚀 **Installation Steps**

### **Step 1: Server Preparation**

#### **1.1 Update System Packages**

```bash
# Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y

# Windows Server
# Use Windows Update to ensure latest patches
```

#### **1.2 Install Required Software**

```bash
# Ubuntu/Debian
sudo apt install -y nginx mysql-server php8.1-fpm php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-gd php8.1-zip php8.1-bcmath php8.1-intl php8.1-opcache composer git unzip

# CentOS/RHEL
sudo yum install -y epel-release
sudo yum install -y nginx mysql-server php-fpm php-mysql php-xml php-mbstring php-curl php-gd php-zip php-bcmath php-intl php-opcache composer git unzip
```

#### **1.3 Configure PHP**

```bash
# Edit PHP configuration
sudo nano /etc/php/8.1/fpm/php.ini

# Recommended settings
memory_limit = 512M
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
max_input_vars = 3000
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
```

### **Step 2: Database Setup**

#### **2.1 Secure MySQL Installation**

```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
-- Create database
CREATE DATABASE dds_backend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user with limited privileges
CREATE USER 'dds_user'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON dds_backend.* TO 'dds_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### **2.2 Database Configuration File**

```bash
# Create database configuration
sudo nano /etc/dds/database.conf

# Add database connection details
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=dds_backend
DB_USERNAME=dds_user
DB_PASSWORD=StrongPassword123!
```

### **Step 3: Application Deployment**

#### **3.1 Clone Application Repository**

```bash
# Navigate to web directory
cd /var/www

# Clone the repository
sudo git clone https://github.com/your-organization/dds-laravel.git
sudo chown -R www-data:www-data dds-laravel
sudo chmod -R 755 dds-laravel
```

#### **3.2 Install Dependencies**

```bash
cd dds-laravel

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies (if using frontend build tools)
npm install
npm run build
```

#### **3.3 Environment Configuration**

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit environment file
nano .env
```

**Required Environment Variables:**

```env
APP_NAME="Document Distribution System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dds_backend
DB_USERNAME=dds_user
DB_PASSWORD=StrongPassword123!

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### **Step 4: Database Migration and Seeding**

#### **4.1 Run Database Migrations**

```bash
# Run migrations
php artisan migrate --force

# Seed initial data
php artisan db:seed --force

# Create admin user
php artisan make:admin
```

#### **4.2 Verify Database Setup**

```bash
# Check migration status
php artisan migrate:status

# Verify tables created
mysql -u dds_user -p dds_backend -e "SHOW TABLES;"
```

### **Step 5: Web Server Configuration**

#### **5.1 Nginx Configuration**

```bash
# Create Nginx site configuration
sudo nano /etc/nginx/sites-available/dds
```

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    root /var/www/dds-laravel/public;
    index index.php index.html index.htm;

    # Handle PHP files
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Handle static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Security: Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    location ~ /\.git {
        deny all;
    }

    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

#### **5.2 Enable Site and Restart Services**

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/dds /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo systemctl enable nginx
sudo systemctl enable php8.1-fpm
```

### **Step 6: SSL Certificate Setup**

#### **6.1 Install Certbot (Let's Encrypt)**

```bash
# Ubuntu/Debian
sudo apt install certbot python3-certbot-nginx

# CentOS/RHEL
sudo yum install certbot python3-certbot-nginx
```

#### **6.2 Obtain SSL Certificate**

```bash
# Obtain certificate
sudo certbot --nginx -d your-domain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### **Step 7: Application Configuration**

#### **7.1 Set Proper Permissions**

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/dds-laravel

# Set permissions
sudo chmod -R 755 /var/www/dds-laravel
sudo chmod -R 775 /var/www/dds-laravel/storage
sudo chmod -R 775 /var/www/dds-laravel/bootstrap/cache
```

#### **7.2 Configure Queue Worker (Optional)**

```bash
# Create systemd service for queue worker
sudo nano /etc/systemd/system/dds-queue.service
```

```ini
[Unit]
Description=DDS Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/dds-laravel
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```bash
# Enable and start queue worker
sudo systemctl enable dds-queue
sudo systemctl start dds-queue
```

#### **7.3 Configure Cron Jobs**

```bash
# Edit crontab
sudo crontab -e

# Add Laravel scheduler
* * * * * cd /var/www/dds-laravel && php artisan schedule:run >> /dev/null 2>&1
```

### **Step 8: Security Configuration**

#### **8.1 Firewall Setup**

```bash
# Ubuntu/Debian (UFW)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# CentOS/RHEL (Firewalld)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

#### **8.2 Fail2ban Installation**

```bash
# Install Fail2ban
sudo apt install fail2ban

# Configure for Nginx
sudo nano /etc/fail2ban/jail.local
```

```ini
[nginx-http-auth]
enabled = true
filter = nginx-http-auth
logpath = /var/log/nginx/error.log
maxretry = 3
bantime = 3600
findtime = 600
```

```bash
# Restart Fail2ban
sudo systemctl restart fail2ban
sudo systemctl enable fail2ban
```

### **Step 9: Monitoring and Logging**

#### **9.1 Log Rotation**

```bash
# Configure log rotation
sudo nano /etc/logrotate.d/dds
```

```
/var/www/dds-laravel/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload nginx
    endscript
}
```

#### **9.2 Application Monitoring**

```bash
# Install monitoring tools
sudo apt install htop iotop nethogs

# Create monitoring script
sudo nano /usr/local/bin/dds-monitor.sh
```

```bash
#!/bin/bash
# DDS Application Monitor Script

echo "=== DDS Application Status ==="
echo "Date: $(date)"
echo ""

# Check services
echo "Service Status:"
systemctl is-active nginx
systemctl is-active php8.1-fpm
systemctl is-active mysql

# Check disk space
echo ""
echo "Disk Usage:"
df -h /var/www

# Check memory usage
echo ""
echo "Memory Usage:"
free -h

# Check application logs
echo ""
echo "Recent Application Errors:"
tail -n 10 /var/www/dds-laravel/storage/logs/laravel.log | grep ERROR
```

```bash
# Make script executable
sudo chmod +x /usr/local/bin/dds-monitor.sh

# Add to crontab for regular monitoring
sudo crontab -e
# Add: 0 */6 * * * /usr/local/bin/dds-monitor.sh >> /var/log/dds-monitor.log 2>&1
```

## 🔧 **Troubleshooting**

### **Common Issues and Solutions**

#### **1. Permission Denied Errors**

```bash
# Fix storage permissions
sudo chown -R www-data:www-data /var/www/dds-laravel/storage
sudo chmod -R 775 /var/www/dds-laravel/storage
```

#### **2. Database Connection Issues**

```bash
# Test database connection
php artisan tinker
# Try: DB::connection()->getPdo();

# Check MySQL service
sudo systemctl status mysql
```

#### **3. 500 Internal Server Error**

```bash
# Check Laravel logs
tail -f /var/www/dds-laravel/storage/logs/laravel.log

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.1-fpm.log
```

#### **4. SSL Certificate Issues**

```bash
# Check certificate status
sudo certbot certificates

# Renew certificate manually
sudo certbot renew
```

## 📊 **Performance Optimization**

### **1. PHP OPcache Configuration**

```bash
# Optimize OPcache settings
sudo nano /etc/php/8.1/fpm/conf.d/10-opcache.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### **2. Nginx Performance Tuning**

```bash
# Edit Nginx main configuration
sudo nano /etc/nginx/nginx.conf
```

```nginx
# Add to http block
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

# Worker processes
worker_processes auto;
worker_connections 1024;
```

### **3. MySQL Performance Tuning**

```bash
# Edit MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 128M
query_cache_type = 1
```

## 🔒 **Security Checklist**

-   [ ] HTTPS enabled with valid SSL certificate
-   [ ] Firewall configured and enabled
-   [ ] Fail2ban installed and configured
-   [ ] Database user has limited privileges
-   [ ] Application debug mode disabled
-   [ ] Strong passwords implemented
-   [ ] Regular security updates enabled
-   [ ] Log monitoring configured
-   [ ] Backup strategy implemented
-   [ ] Access logs enabled and monitored

## 📞 **Support and Maintenance**

### **Regular Maintenance Tasks**

-   **Daily**: Check application logs for errors
-   **Weekly**: Review system performance and disk usage
-   **Monthly**: Update system packages and security patches
-   **Quarterly**: Review and update SSL certificates
-   **Annually**: Full security audit and performance review

### **Contact Information**

-   **Technical Support**: support@your-company.com
-   **Emergency Contact**: +1-XXX-XXX-XXXX
-   **Documentation**: https://docs.your-company.com/dds
-   **Issue Tracker**: https://github.com/your-organization/dds-laravel/issues

---

**Document Version**: 1.0  
**Last Updated**: 2025-08-21  
**Maintained By**: IT Department  
**Next Review**: 2026-01-21
