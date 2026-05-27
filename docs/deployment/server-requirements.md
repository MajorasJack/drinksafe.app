# DrinkSafe Server Requirements

This document outlines the minimum and recommended server requirements for deploying DrinkSafe in a production environment.

---

## Minimum Requirements

### Server Specifications

- **CPU:** 2 vCPU cores
- **RAM:** 2GB minimum (4GB recommended)
- **Storage:** 10GB SSD minimum (20GB recommended)
- **Network:** 100Mbps connection
- **OS:** Ubuntu 22.04 LTS or newer (or equivalent)

### Software Requirements

#### Core Software

- **PHP:** 8.4 or higher
- **Composer:** 2.5 or higher
- **Node.js:** 20.x LTS or higher
- **npm:** 10.x or higher
- **Database:** PostgreSQL 15+ OR MySQL 8.0+
- **Redis:** 7.0 or higher
- **Web Server:** Nginx 1.18+ OR Apache 2.4+

#### PHP Extensions Required

The following PHP extensions must be installed and enabled:

```bash
# Core Laravel Extensions
- php8.4-cli
- php8.4-fpm
- php8.4-bcmath
- php8.4-ctype
- php8.4-curl
- php8.4-dom
- php8.4-fileinfo
- php8.4-json
- php8.4-mbstring
- php8.4-openssl
- php8.4-pdo
- php8.4-tokenizer
- php8.4-xml

# Database Extensions
- php8.4-pgsql  # For PostgreSQL
- php8.4-mysql  # For MySQL

# Additional Extensions
- php8.4-gd      # For image manipulation
- php8.4-intl    # For internationalization
- php8.4-zip     # For package management
- php8.4-redis   # For Redis cache/session
```

### Installation Commands (Ubuntu 22.04)

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update

# Install PHP 8.4 and extensions
sudo apt-get install -y php8.4 php8.4-fpm php8.4-cli php8.4-bcmath \
    php8.4-ctype php8.4-curl php8.4-dom php8.4-fileinfo php8.4-json \
    php8.4-mbstring php8.4-openssl php8.4-pdo php8.4-tokenizer \
    php8.4-xml php8.4-pgsql php8.4-gd php8.4-intl php8.4-zip php8.4-redis

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Node.js 20.x LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install PostgreSQL 15
sudo apt-get install -y postgresql-15 postgresql-contrib-15

# Install Redis
sudo apt-get install -y redis-server

# Install Nginx
sudo apt-get install -y nginx
```

---

## Recommended Production Setup

### Application Server

**Option 1: PHP-FPM with Nginx (Recommended)**

- **PHP-FPM:** Process manager for PHP
- **Nginx:** Reverse proxy and static file server
- **Configuration:** Optimized for Laravel applications

**Option 2: PHP-FPM with Apache**

- **Apache:** Web server with mod_rewrite
- **Configuration:** .htaccess support built-in

### Database Server

**Recommended: PostgreSQL 15+**

Reasons:
- Better JSON support for complex queries
- Full-text search capabilities
- Geographic data types (PostGIS)
- ACID compliance
- Better concurrency handling

**Alternative: MySQL 8.0+**

Reasons:
- Wider hosting support
- Slightly faster for simple queries
- Good full-text search
- JSON support available

### Cache & Queue Server

**Required: Redis 7.0+**

Used for:
- Session storage
- Cache storage
- Queue jobs
- Rate limiting

---

## Process Management

### Queue Workers (Required)

DrinkSafe uses Laravel queues for background processing. You must run queue workers.

**Supervisor Configuration:**

```ini
# /etc/supervisor/conf.d/drinksafe-worker.conf
[program:drinksafe-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/drinksafe/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/drinksafe/storage/logs/worker.log
stopwaitsecs=3600
```

**Installation:**

```bash
sudo apt-get install -y supervisor
sudo cp drinksafe-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start drinksafe-worker:*
```

### Scheduled Tasks (Required)

Add Laravel scheduler to crontab:

```bash
# Edit crontab
sudo crontab -e -u www-data

# Add this line:
* * * * * cd /var/www/drinksafe && php artisan schedule:run >> /dev/null 2>&1
```

---

## Security Requirements

### SSL/TLS Certificate

**Required:** HTTPS is mandatory for production.

**Options:**

1. **Let's Encrypt (Free)**
   ```bash
   sudo apt-get install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d drinksafe.example.com
   ```

2. **Commercial Certificate**
   - Purchase from certificate authority
   - Install via Nginx/Apache configuration

### Firewall Configuration

**Required Ports:**

```bash
# Install UFW
sudo apt-get install -y ufw

# Allow SSH
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Deny direct access to PostgreSQL (only localhost)
sudo ufw deny 5432/tcp

# Deny direct access to Redis (only localhost)
sudo ufw deny 6379/tcp

# Enable firewall
sudo ufw enable
```

### File Permissions

```bash
# Set proper ownership
sudo chown -R www-data:www-data /var/www/drinksafe

# Set directory permissions
sudo find /var/www/drinksafe -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/drinksafe -type f -exec chmod 644 {} \;

# Make artisan executable
sudo chmod 755 /var/www/drinksafe/artisan

# Set storage and cache to writable
sudo chmod -R 775 /var/www/drinksafe/storage
sudo chmod -R 775 /var/www/drinksafe/bootstrap/cache
```

---

## Nginx Configuration

### Complete Nginx Virtual Host

```nginx
# /etc/nginx/sites-available/drinksafe
server {
    listen 80;
    listen [::]:80;
    server_name drinksafe.example.com;

    # Redirect all HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name drinksafe.example.com;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/drinksafe.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/drinksafe.example.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Document Root
    root /var/www/drinksafe/public;
    index index.php index.html;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(self), microphone=(), camera=()" always;

    # HSTS (uncomment after testing)
    # add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Logging
    access_log /var/log/nginx/drinksafe-access.log;
    error_log /var/log/nginx/drinksafe-error.log;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/json;

    # Character Set
    charset utf-8;

    # Main Location Block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;

        # Increase timeouts for slower queries
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Enable Site

```bash
# Create symlink
sudo ln -s /etc/nginx/sites-available/drinksafe /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## PHP-FPM Configuration

### Optimize PHP-FPM Pool

```ini
# /etc/php/8.4/fpm/pool.d/drinksafe.conf
[drinksafe]
user = www-data
group = www-data

listen = /var/run/php/php8.4-fpm-drinksafe.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 500

; PHP settings
php_admin_value[error_log] = /var/www/drinksafe/storage/logs/php-fpm.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 10M
php_admin_value[max_execution_time] = 300
```

### Restart PHP-FPM

```bash
sudo systemctl restart php8.4-fpm
```

---

## Database Configuration

### PostgreSQL Configuration

```bash
# Edit PostgreSQL configuration
sudo nano /etc/postgresql/15/main/postgresql.conf
```

**Recommended Settings:**

```ini
# Connection Settings
max_connections = 100
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
maintenance_work_mem = 64MB

# WAL Settings
wal_buffers = 8MB
checkpoint_completion_target = 0.9

# Query Planning
random_page_cost = 1.1
effective_io_concurrency = 200

# Logging
log_line_prefix = '%t [%p]: [%l-1] user=%u,db=%d,app=%a,client=%h '
log_checkpoints = on
log_connections = on
log_disconnections = on
log_lock_waits = on
```

### Create Database and User

```bash
sudo -u postgres psql

CREATE DATABASE drinksafe_production;
CREATE USER drinksafe WITH ENCRYPTED PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE drinksafe_production TO drinksafe;
\q
```

### Redis Configuration

```bash
# Edit Redis configuration
sudo nano /etc/redis/redis.conf
```

**Recommended Settings:**

```ini
# Network
bind 127.0.0.1
protected-mode yes
port 6379

# Memory
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence (optional for cache-only)
save ""

# Security
requirepass your_redis_password_here

# Performance
tcp-keepalive 60
timeout 300
```

---

## Performance Tuning

### PHP OpCache

Enable OpCache for significant performance improvements:

```ini
# /etc/php/8.4/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

### Linux Kernel Tuning

```bash
# Edit sysctl configuration
sudo nano /etc/sysctl.conf

# Add these settings
net.core.somaxconn = 65535
net.ipv4.tcp_max_syn_backlog = 65535
net.ipv4.ip_local_port_range = 1024 65535
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 15
```

Apply settings:

```bash
sudo sysctl -p
```

---

## Monitoring & Health Checks

### Health Check Endpoint

DrinkSafe provides a health check endpoint:

```
GET /health
```

**Expected Response:**

```json
{
    "status": "healthy",
    "database": "connected",
    "cache": "connected",
    "timestamp": "2026-05-15T10:30:00Z"
}
```

### Monitoring Tools

**Recommended:**

1. **Uptime Monitoring:** UptimeRobot, Pingdom
2. **Application Performance:** Laravel Pulse, New Relic, Datadog
3. **Error Tracking:** Sentry, Bugsnag
4. **Server Monitoring:** Netdata, Prometheus + Grafana
5. **Log Management:** Papertrail, Logtail, Logstash

---

## Backup Requirements

### Database Backups

**Daily automated backups required.**

```bash
# Create backup script
sudo nano /usr/local/bin/drinksafe-backup.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/drinksafe"
DB_NAME="drinksafe_production"
DB_USER="drinksafe"

# Create backup directory
mkdir -p $BACKUP_DIR

# Dump database
pg_dump -U $DB_USER -F c $DB_NAME > $BACKUP_DIR/drinksafe_$DATE.dump

# Keep only last 30 days
find $BACKUP_DIR -name "drinksafe_*.dump" -mtime +30 -delete
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/drinksafe-backup.sh

# Add to crontab
sudo crontab -e

# Run daily at 2 AM
0 2 * * * /usr/local/bin/drinksafe-backup.sh
```

### Application Files Backup

Backup these directories:

- `/var/www/drinksafe/storage/app` (uploaded files)
- `/var/www/drinksafe/.env` (configuration)

---

## Hosting Recommendations

### Recommended Providers

**Cloud Platforms:**

1. **Laravel Cloud** (Recommended)
   - Optimized for Laravel
   - Automatic deployments
   - Built-in monitoring
   - Starting at $50/month

2. **DigitalOcean**
   - App Platform or Droplets
   - Easy to configure
   - Starting at $12/month

3. **AWS (Amazon Web Services)**
   - EC2 + RDS + ElastiCache
   - Highly scalable
   - Starting at $30/month

4. **Linode**
   - Simple VPS hosting
   - Good performance
   - Starting at $10/month

5. **Vultr**
   - High-performance VPS
   - Global locations
   - Starting at $12/month

### Managed Database Options

- **AWS RDS (PostgreSQL)**
- **DigitalOcean Managed Databases**
- **Supabase (PostgreSQL)**
- **PlanetScale (MySQL)**

---

## Scaling Considerations

### Horizontal Scaling

When traffic grows, consider:

1. **Load Balancer:** Distribute traffic across multiple app servers
2. **Separate Database Server:** Move PostgreSQL to dedicated server
3. **Redis Cluster:** Separate cache and queue to different Redis instances
4. **CDN:** Use CloudFlare or AWS CloudFront for static assets
5. **Object Storage:** Move file uploads to S3-compatible storage

### Vertical Scaling Thresholds

Upgrade server when:

- **CPU Usage >70%** consistently
- **Memory Usage >80%** consistently
- **Response Time >200ms** on average
- **Database Queries >100ms** on average

---

## Support & Troubleshooting

### Common Issues

**Issue:** 500 Internal Server Error
**Solution:** Check `/var/www/drinksafe/storage/logs/laravel.log`

**Issue:** Permission Denied errors
**Solution:** Run `sudo chown -R www-data:www-data /var/www/drinksafe`

**Issue:** Queue jobs not processing
**Solution:** Restart supervisor: `sudo supervisorctl restart drinksafe-worker:*`

**Issue:** Database connection errors
**Solution:** Check `.env` database credentials and PostgreSQL service status

---

**End of Server Requirements Documentation**
