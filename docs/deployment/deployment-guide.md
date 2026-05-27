# DrinkSafe Deployment Guide

This guide provides step-by-step instructions for deploying DrinkSafe to a production environment.

---

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Server Preparation](#server-preparation)
3. [Deployment Steps](#deployment-steps)
4. [Post-Deployment Verification](#post-deployment-verification)
5. [Rollback Procedure](#rollback-procedure)
6. [Troubleshooting](#troubleshooting)

---

## Pre-Deployment Checklist

Before deploying, ensure the following requirements are met:

### Infrastructure Requirements

- [ ] Server meets minimum requirements (see `server-requirements.md`)
- [ ] Domain name configured and DNS propagated
- [ ] SSL certificate obtained (Let's Encrypt or commercial)
- [ ] Database server installed and configured
- [ ] Redis server installed and configured
- [ ] Firewall rules configured

### Application Requirements

- [ ] All tests passing locally (`php artisan test`)
- [ ] Code linted and formatted (`vendor/bin/pint`)
- [ ] Environment variables documented
- [ ] Database migrations reviewed
- [ ] Seeders prepared (if needed)
- [ ] Backup strategy in place

### Access Requirements

- [ ] SSH access to production server
- [ ] Database credentials
- [ ] Redis password (if applicable)
- [ ] Email/SMTP credentials
- [ ] S3 credentials (if using AWS storage)

---

## Server Preparation

### Step 1: Initial Server Setup

```bash
# Connect to server via SSH
ssh root@your-server-ip

# Update system packages
sudo apt-get update
sudo apt-get upgrade -y

# Set timezone
sudo timedatectl set-timezone UTC

# Create deployment user (if not using root)
sudo adduser drinksafe
sudo usermod -aG sudo drinksafe
sudo su - drinksafe
```

### Step 2: Install Required Software

Follow the installation commands in `server-requirements.md`:

```bash
# Install PHP 8.4 and extensions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update
sudo apt-get install -y php8.4 php8.4-fpm php8.4-cli php8.4-bcmath \
    php8.4-ctype php8.4-curl php8.4-dom php8.4-fileinfo php8.4-json \
    php8.4-mbstring php8.4-openssl php8.4-pdo php8.4-tokenizer \
    php8.4-xml php8.4-pgsql php8.4-gd php8.4-intl php8.4-zip php8.4-redis

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install PostgreSQL 15
sudo apt-get install -y postgresql-15 postgresql-contrib-15

# Install Redis
sudo apt-get install -y redis-server

# Install Nginx
sudo apt-get install -y nginx

# Install Supervisor (for queue workers)
sudo apt-get install -y supervisor

# Install Git
sudo apt-get install -y git
```

### Step 3: Configure Database

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE drinksafe_production;
CREATE USER drinksafe WITH ENCRYPTED PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE drinksafe_production TO drinksafe;
ALTER DATABASE drinksafe_production OWNER TO drinksafe;
\q

# Test connection
psql -U drinksafe -d drinksafe_production -h localhost
# Enter password when prompted
# Type \q to exit
```

### Step 4: Configure Redis

```bash
# Edit Redis configuration
sudo nano /etc/redis/redis.conf

# Set a password (uncomment and modify)
requirepass your_redis_password_here

# Restart Redis
sudo systemctl restart redis-server

# Test connection
redis-cli
AUTH your_redis_password_here
PING
# Should return "PONG"
exit
```

### Step 5: Configure Firewall

```bash
# Install UFW if not already installed
sudo apt-get install -y ufw

# Allow SSH (important - do this first!)
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw enable

# Check status
sudo ufw status
```

---

## Deployment Steps

### Step 1: Clone Repository

```bash
# Create web directory
sudo mkdir -p /var/www
cd /var/www

# Clone repository (using HTTPS or SSH)
# Option 1: HTTPS
sudo git clone https://github.com/your-org/drinksafe.git drinksafe

# Option 2: SSH (if deploy key configured)
sudo git clone git@github.com:your-org/drinksafe.git drinksafe

# Set ownership
sudo chown -R www-data:www-data /var/www/drinksafe
cd /var/www/drinksafe
```

### Step 2: Install Dependencies

```bash
# Install PHP dependencies (production only)
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies
sudo -u www-data npm ci --production=false

# Build frontend assets
sudo -u www-data npm run build
```

### Step 3: Configure Environment

```bash
# Copy production environment file
sudo -u www-data cp .env.production.example .env

# Edit environment file
sudo -u www-data nano .env
```

**Configure these critical settings:**

```env
APP_NAME="DrinkSafe"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://drinksafe.example.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=drinksafe_production
DB_USERNAME=drinksafe
DB_PASSWORD=your_database_password_here

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password_here
REDIS_PORT=6379

# Add mail configuration if needed
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_FROM_ADDRESS="noreply@drinksafe.example.com"
```

### Step 4: Generate Application Key

```bash
# Generate app key
sudo -u www-data php artisan key:generate

# Verify key was generated
grep APP_KEY .env
```

### Step 5: Run Database Migrations

```bash
# IMPORTANT: Always backup database before migrations
# (Skip on first deployment)

# Run migrations
sudo -u www-data php artisan migrate --force

# Verify migrations
sudo -u www-data php artisan migrate:status
```

### Step 6: Seed Database (First Deployment Only)

```bash
# Run seeders for initial data
sudo -u www-data php artisan db:seed --force

# Or seed specific seeders
sudo -u www-data php artisan db:seed --class=VenueSeeder --force
```

### Step 7: Optimize Application

```bash
# Cache configuration
sudo -u www-data php artisan config:cache

# Cache routes
sudo -u www-data php artisan route:cache

# Cache views
sudo -u www-data php artisan view:cache

# Cache events
sudo -u www-data php artisan event:cache
```

### Step 8: Set File Permissions

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

### Step 9: Configure Nginx

```bash
# Copy Nginx configuration from server-requirements.md
sudo nano /etc/nginx/sites-available/drinksafe

# Create symlink
sudo ln -s /etc/nginx/sites-available/drinksafe /etc/nginx/sites-enabled/

# Remove default site
sudo rm /etc/nginx/sites-enabled/default

# Test Nginx configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### Step 10: Configure SSL Certificate

```bash
# Install Certbot
sudo apt-get install -y certbot python3-certbot-nginx

# Obtain certificate (follow prompts)
sudo certbot --nginx -d drinksafe.example.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### Step 11: Configure Queue Workers

```bash
# Create supervisor configuration
sudo nano /etc/supervisor/conf.d/drinksafe-worker.conf
```

**Add this configuration:**

```ini
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

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start drinksafe-worker:*

# Check worker status
sudo supervisorctl status
```

### Step 12: Configure Cron (Laravel Scheduler)

```bash
# Edit crontab for www-data user
sudo crontab -e -u www-data

# Add this line:
* * * * * cd /var/www/drinksafe && php artisan schedule:run >> /dev/null 2>&1
```

### Step 13: Create Storage Link

```bash
# Create symbolic link for storage
sudo -u www-data php artisan storage:link
```

---

## Post-Deployment Verification

### Automated Checks

Run the verification script:

```bash
cd /var/www/drinksafe

# Check application status
php artisan about

# Run health checks
php artisan health:check

# Test database connection
php artisan db:show

# Verify cache connection
php artisan tinker --execute "Cache::put('test', 'value', 60); echo Cache::get('test');"
```

### Manual Verification Checklist

- [ ] **Homepage loads:** Visit `https://drinksafe.example.com`
- [ ] **No JavaScript errors:** Check browser console (F12)
- [ ] **Map displays:** Verify Leaflet map loads venues
- [ ] **SSL valid:** Check for green padlock in browser
- [ ] **API endpoints work:** Test `GET /api/venues`
- [ ] **Submit report form:** Create a test report
- [ ] **Database connected:** Verify data displays
- [ ] **Redis connected:** Check sessions work (login/logout if applicable)
- [ ] **Queue workers running:** Check `sudo supervisorctl status`
- [ ] **Logs are writing:** Check `storage/logs/laravel.log`

### Performance Checks

```bash
# Check response time for homepage
curl -w "@/dev/stdin" -o /dev/null -s https://drinksafe.example.com <<EOF
    time_namelookup:  %{time_namelookup}\n
       time_connect:  %{time_connect}\n
    time_appconnect:  %{time_appconnect}\n
   time_pretransfer:  %{time_pretransfer}\n
      time_redirect:  %{time_redirect}\n
 time_starttransfer:  %{time_starttransfer}\n
                    ----------\n
         time_total:  %{time_total}\n
EOF

# Check API response time
curl -w "Time: %{time_total}s\n" -o /dev/null -s https://drinksafe.example.com/api/venues
```

Target response times:
- Homepage: <1.5 seconds
- API endpoints: <200ms

### Security Verification

```bash
# Check file permissions
ls -la /var/www/drinksafe/storage
ls -la /var/www/drinksafe/.env

# Verify firewall
sudo ufw status

# Check SSL certificate
openssl s_client -connect drinksafe.example.com:443 -servername drinksafe.example.com

# Check security headers
curl -I https://drinksafe.example.com | grep -i "x-frame-options\|x-content-type-options\|x-xss-protection"
```

---

## Rollback Procedure

If deployment fails, follow these steps to rollback:

### Step 1: Identify Previous Version

```bash
cd /var/www/drinksafe

# View git history
git log --oneline -10

# Note the commit hash of the previous working version
```

### Step 2: Rollback Code

```bash
# Checkout previous version
sudo -u www-data git checkout <previous-commit-hash>

# Reinstall dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci
sudo -u www-data npm run build
```

### Step 3: Rollback Database (If Migrations Were Run)

```bash
# Check migration history
sudo -u www-data php artisan migrate:status

# Rollback last batch of migrations
sudo -u www-data php artisan migrate:rollback --force

# Or rollback specific number of migrations
sudo -u www-data php artisan migrate:rollback --step=5 --force
```

### Step 4: Clear Caches

```bash
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan cache:clear

# Rebuild caches
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### Step 5: Restart Services

```bash
sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
sudo supervisorctl restart drinksafe-worker:*
```

### Step 6: Verify Rollback

- Visit homepage and verify it loads
- Test critical functionality
- Check logs for errors

---

## Updating Production

For subsequent deployments, follow these abbreviated steps:

### Quick Update Procedure

```bash
cd /var/www/drinksafe

# Pull latest changes
sudo -u www-data git pull origin main

# Update dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci
sudo -u www-data npm run build

# Run migrations (if any)
sudo -u www-data php artisan migrate --force

# Clear and rebuild caches
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan cache:clear

sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Restart services
sudo systemctl restart php8.4-fpm
sudo supervisorctl restart drinksafe-worker:*
```

### Zero-Downtime Deployment (Advanced)

For zero-downtime deployments, consider:

1. **Blue-Green Deployment:** Use two identical environments
2. **Laravel Envoyer:** Automated zero-downtime deployment tool
3. **Laravel Forge:** Managed deployment platform
4. **Deployer:** PHP deployment tool

---

## Troubleshooting

### Common Issues

#### Issue 1: 500 Internal Server Error

**Symptoms:** White screen, 500 error in browser

**Solutions:**

```bash
# Check Laravel logs
sudo tail -f /var/www/drinksafe/storage/logs/laravel.log

# Check Nginx error logs
sudo tail -f /var/log/nginx/drinksafe-error.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.4-fpm.log

# Common fixes:
sudo chown -R www-data:www-data /var/www/drinksafe
sudo chmod -R 775 /var/www/drinksafe/storage
sudo chmod -R 775 /var/www/drinksafe/bootstrap/cache
```

#### Issue 2: Database Connection Error

**Symptoms:** SQLSTATE errors, "could not connect to server"

**Solutions:**

```bash
# Test database connection
psql -U drinksafe -d drinksafe_production -h localhost

# Check PostgreSQL service
sudo systemctl status postgresql

# Restart PostgreSQL
sudo systemctl restart postgresql

# Verify .env credentials
grep DB_ /var/www/drinksafe/.env
```

#### Issue 3: Queue Jobs Not Processing

**Symptoms:** Reports not being processed, jobs stuck in queue

**Solutions:**

```bash
# Check supervisor status
sudo supervisorctl status

# View worker logs
sudo tail -f /var/www/drinksafe/storage/logs/worker.log

# Restart workers
sudo supervisorctl restart drinksafe-worker:*

# Check failed jobs
sudo -u www-data php artisan queue:failed
```

#### Issue 4: CSS/JS Not Loading

**Symptoms:** Unstyled page, JavaScript errors

**Solutions:**

```bash
# Rebuild assets
sudo -u www-data npm run build

# Clear view cache
sudo -u www-data php artisan view:clear

# Check Nginx is serving public directory
ls -la /var/www/drinksafe/public/build/

# Verify Nginx configuration
sudo nginx -t
```

#### Issue 5: Redis Connection Error

**Symptoms:** Session errors, cache errors

**Solutions:**

```bash
# Test Redis connection
redis-cli
AUTH your_redis_password_here
PING

# Check Redis service
sudo systemctl status redis-server

# Restart Redis
sudo systemctl restart redis-server

# Verify .env credentials
grep REDIS /var/www/drinksafe/.env
```

#### Issue 6: Permission Denied Errors

**Symptoms:** Unable to write logs, cache errors

**Solutions:**

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/drinksafe

# Fix permissions
sudo chmod -R 775 /var/www/drinksafe/storage
sudo chmod -R 775 /var/www/drinksafe/bootstrap/cache

# Verify web server user
ps aux | grep nginx
ps aux | grep php-fpm
```

### Debug Mode (Temporary Only)

**WARNING:** Never enable debug mode on production for extended periods.

```bash
# Temporarily enable debug to see error details
sudo -u www-data nano /var/www/drinksafe/.env

# Change:
APP_DEBUG=true

# View error in browser
# Then IMMEDIATELY disable:
APP_DEBUG=false

# Clear config cache
sudo -u www-data php artisan config:cache
```

### Getting Help

If issues persist:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check Nginx logs: `/var/log/nginx/drinksafe-error.log`
3. Check PHP-FPM logs: `/var/log/php8.4-fpm.log`
4. Check system logs: `sudo journalctl -xe`
5. Run health checks: `php artisan health:check`

---

## Maintenance Commands

### Clear All Caches

```bash
sudo -u www-data php artisan optimize:clear
```

### View Application Information

```bash
sudo -u www-data php artisan about
```

### Check Queue Status

```bash
sudo -u www-data php artisan queue:work --once
sudo -u www-data php artisan queue:failed
```

### Database Maintenance

```bash
# Vacuum PostgreSQL database (reclaim storage)
sudo -u postgres vacuumdb --all --analyze

# Check database size
sudo -u postgres psql -c "SELECT pg_database.datname, pg_size_pretty(pg_database_size(pg_database.datname)) AS size FROM pg_database;"
```

---

## Security Best Practices

1. **Never commit `.env` files** to version control
2. **Use strong passwords** for database and Redis
3. **Keep packages updated** regularly (`composer update`, `npm update`)
4. **Monitor logs** for suspicious activity
5. **Enable firewall** and restrict unnecessary ports
6. **Use HTTPS only** - redirect all HTTP to HTTPS
7. **Set APP_DEBUG=false** in production
8. **Backup regularly** - database and application files
9. **Limit SSH access** to specific IP addresses if possible
10. **Keep server updated** with security patches

---

## Next Steps

After successful deployment:

1. Set up monitoring (see `monitoring.md`)
2. Configure automated backups
3. Set up error tracking (Sentry)
4. Configure uptime monitoring (UptimeRobot)
5. Review and optimize based on performance metrics
6. Document any environment-specific configurations

---

**End of Deployment Guide**
