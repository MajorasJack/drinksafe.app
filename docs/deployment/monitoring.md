# DrinkSafe Monitoring & Alerting

This document outlines the monitoring strategy for DrinkSafe production environments, including recommended tools, metrics to track, and alerting thresholds.

---

## Table of Contents

1. [Monitoring Philosophy](#monitoring-philosophy)
2. [Application Monitoring](#application-monitoring)
3. [Server Monitoring](#server-monitoring)
4. [Database Monitoring](#database-monitoring)
5. [Error Tracking](#error-tracking)
6. [Uptime Monitoring](#uptime-monitoring)
7. [Performance Monitoring](#performance-monitoring)
8. [Log Management](#log-management)
9. [Alerting Strategy](#alerting-strategy)
10. [Dashboards](#dashboards)

---

## Monitoring Philosophy

### Goals

1. **Proactive Detection:** Identify issues before users report them
2. **Fast Response:** Alert on critical issues within minutes
3. **Performance Visibility:** Understand application performance trends
4. **Capacity Planning:** Predict when scaling is needed
5. **User Experience:** Track real user experience metrics

### Key Metrics (The Four Golden Signals)

1. **Latency:** How long requests take to complete
2. **Traffic:** How many requests per second
3. **Errors:** Rate of failed requests
4. **Saturation:** How full resources are (CPU, memory, disk)

---

## Application Monitoring

### Laravel Pulse (Production Monitoring)

**Purpose:** Built-in Laravel real-time application monitoring dashboard

**Installation:**

```bash
composer require laravel/pulse
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan migrate
```

**Configuration (`config/pulse.php`):**

```php
return [
    'ingest' => [
        'enabled' => env('PULSE_ENABLED', true),
        'interval' => 5, // seconds
    ],

    'recorders' => [
        Recorders\Servers::class => [
            'directories' => ['/'],
        ],
        Recorders\Requests::class => [
            'sample_rate' => 1,
            'threshold' => 200, // ms
        ],
        Recorders\Exceptions::class => [],
        Recorders\Queues::class => [],
        Recorders\CacheInteractions::class => [],
        Recorders\SlowQueries::class => [
            'threshold' => 100, // ms
        ],
    ],
];
```

**Access Dashboard:**

```
https://drinksafe.example.com/pulse
```

**Metrics Tracked:**

- Request throughput and latency
- Slow requests (>200ms)
- Exceptions and error rates
- Queue jobs and failures
- Cache hit/miss ratios
- Slow database queries (>100ms)
- Server CPU, memory, and disk usage

### Laravel Telescope (Development/Staging)

**Purpose:** Debugging and development monitoring (NOT for production)

**Installation (Development/Staging Only):**

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Configuration:**

```env
# Only enable on non-production environments
TELESCOPE_ENABLED=false  # Production
TELESCOPE_ENABLED=true   # Development/Staging
```

---

## Server Monitoring

### Netdata (Recommended)

**Purpose:** Real-time server performance monitoring

**Installation:**

```bash
# Install Netdata
bash <(curl -Ss https://my-netdata.io/kickstart.sh)

# Access dashboard at http://your-server-ip:19999
```

**Metrics Tracked:**

- CPU usage (per core)
- Memory usage (RAM and swap)
- Disk I/O and usage
- Network traffic
- System load
- Process monitoring
- Nginx/PHP-FPM statistics

**Configuration:**

```bash
# Edit netdata configuration
sudo nano /etc/netdata/netdata.conf

# Secure with password
[web]
    bind to = 127.0.0.1
```

**Nginx Reverse Proxy for Netdata:**

```nginx
location /netdata/ {
    proxy_pass http://127.0.0.1:19999/;
    proxy_redirect off;
    proxy_set_header Host $host;
    auth_basic "Netdata";
    auth_basic_user_file /etc/nginx/.htpasswd;
}
```

### Alternative: Prometheus + Grafana

**Purpose:** Industrial-grade metrics collection and visualization

**Use Case:** Multi-server deployments, advanced metrics

**Components:**

1. **Prometheus:** Metrics collection
2. **Node Exporter:** Server metrics
3. **Grafana:** Visualization dashboards
4. **PHP-FPM Exporter:** PHP performance metrics

---

## Database Monitoring

### PostgreSQL Monitoring

**Key Metrics:**

- Connection count
- Query execution time
- Cache hit ratio
- Lock wait time
- Slow queries
- Database size growth

**Built-in Monitoring:**

```sql
-- Active connections
SELECT count(*) FROM pg_stat_activity;

-- Slow queries (queries taking >1 second)
SELECT pid, now() - query_start AS duration, query
FROM pg_stat_activity
WHERE state = 'active'
  AND now() - query_start > interval '1 second'
ORDER BY duration DESC;

-- Cache hit ratio (should be >95%)
SELECT
    sum(heap_blks_read) as heap_read,
    sum(heap_blks_hit) as heap_hit,
    sum(heap_blks_hit) / (sum(heap_blks_hit) + sum(heap_blks_read)) as ratio
FROM pg_statio_user_tables;

-- Database size
SELECT pg_size_pretty(pg_database_size('drinksafe_production'));

-- Table sizes
SELECT
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
LIMIT 10;
```

**PgBadger (Log Analysis):**

```bash
# Install pgbadger
sudo apt-get install -y pgbadger

# Enable PostgreSQL logging
sudo nano /etc/postgresql/15/main/postgresql.conf

# Add these lines:
log_line_prefix = '%t [%p]: [%l-1] user=%u,db=%d '
log_checkpoints = on
log_connections = on
log_disconnections = on
log_lock_waits = on
log_temp_files = 0
log_autovacuum_min_duration = 0
log_min_duration_statement = 100  # Log queries taking >100ms

# Restart PostgreSQL
sudo systemctl restart postgresql

# Generate report
pgbadger /var/log/postgresql/postgresql-15-main.log -o /tmp/pgbadger.html
```

### Redis Monitoring

**Key Metrics:**

- Memory usage
- Connected clients
- Commands per second
- Cache hit ratio
- Evicted keys

**Monitoring Commands:**

```bash
# Connect to Redis
redis-cli -a your_redis_password

# Server info
INFO

# Memory statistics
INFO memory

# Stats
INFO stats

# Monitor commands in real-time
MONITOR

# Get memory usage
INFO memory | grep used_memory_human

# Get hit rate
INFO stats | grep keyspace_hits
INFO stats | grep keyspace_misses
```

**Redis CLI One-liners:**

```bash
# Check connected clients
redis-cli -a password INFO clients | grep connected_clients

# Check memory usage
redis-cli -a password INFO memory | grep used_memory_human

# Check operations per second
redis-cli -a password INFO stats | grep instantaneous_ops_per_sec
```

---

## Error Tracking

### Sentry (Recommended)

**Purpose:** Real-time error tracking and exception monitoring

**Installation:**

```bash
composer require sentry/sentry-laravel
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

**Configuration:**

```env
SENTRY_LARAVEL_DSN=https://your-sentry-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=1.0  # 100% of transactions
```

**Features:**

- Real-time error notifications
- Error grouping and deduplication
- Stack traces with code context
- User context tracking
- Performance monitoring
- Release tracking
- Breadcrumbs (user actions before error)

**Alert Configuration:**

- Email on new error types
- Slack integration for critical errors
- PagerDuty for on-call escalation

### Alternative: Bugsnag

Similar features to Sentry, alternative error tracking service.

### Laravel Native Logging

**Configuration (`config/logging.php`):**

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],
        'ignore_exceptions' => false,
    ],

    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'DrinkSafe Bot',
        'emoji' => ':boom:',
        'level' => 'critical',
    ],
],
```

---

## Uptime Monitoring

### UptimeRobot (Recommended)

**Purpose:** External uptime monitoring and alerting

**Configuration:**

**Monitors to Create:**

1. **Main Website**
   - URL: `https://drinksafe.example.com`
   - Type: HTTP(S)
   - Interval: 5 minutes
   - Alert: Email, SMS, Slack

2. **Health Endpoint**
   - URL: `https://drinksafe.example.com/health`
   - Type: HTTP(S) with keyword monitoring
   - Keyword: `"status":"healthy"`
   - Interval: 5 minutes

3. **API Endpoint**
   - URL: `https://drinksafe.example.com/api/venues`
   - Type: HTTP(S)
   - Interval: 5 minutes

**Alert Thresholds:**

- Notify after: 1 minute down
- Escalate after: 5 minutes down
- Check frequency: Every 5 minutes

### Alternative Options

- **Pingdom:** Commercial uptime monitoring
- **StatusCake:** Free and paid plans
- **BetterUptime:** Modern uptime monitoring
- **Oh Dear:** Laravel-focused monitoring

---

## Performance Monitoring

### Application Performance Metrics

**Key Metrics to Track:**

| Metric | Target | Warning | Critical |
|--------|--------|---------|----------|
| Response Time (Homepage) | <1s | >2s | >5s |
| Response Time (API) | <200ms | >500ms | >1s |
| Database Query Time | <50ms | >100ms | >500ms |
| Cache Hit Ratio | >90% | <80% | <70% |
| Memory Usage | <70% | >80% | >90% |
| CPU Usage | <60% | >75% | >85% |
| Disk Usage | <70% | >80% | >90% |

### Real User Monitoring (RUM)

**Frontend Performance Tracking:**

```typescript
// Add to resources/js/app.ts
if (typeof window !== 'undefined' && 'performance' in window) {
    window.addEventListener('load', () => {
        const perfData = performance.timing;
        const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
        const connectTime = perfData.responseEnd - perfData.requestStart;
        const renderTime = perfData.domComplete - perfData.domLoading;

        console.log({
            pageLoadTime,
            connectTime,
            renderTime,
        });

        // Send to analytics endpoint
        fetch('/api/analytics/performance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pageLoadTime,
                connectTime,
                renderTime,
                url: window.location.pathname,
            }),
        });
    });
}
```

### Google Lighthouse CI

**Purpose:** Automated performance audits

```bash
# Install Lighthouse CI
npm install -g @lhci/cli

# Run audit
lhci autorun --collect.url=https://drinksafe.example.com
```

**Target Scores:**

- Performance: >90
- Accessibility: >95
- Best Practices: >95
- SEO: >90

---

## Log Management

### Log Aggregation

**Options:**

1. **Papertrail (Recommended for small-medium apps)**
   - Cloud-based log management
   - 100MB/month free tier
   - Real-time log tailing
   - Search and filtering

2. **Logtail**
   - Modern log management
   - SQL-based queries
   - Better Laravel integration

3. **ELK Stack (Elasticsearch, Logstash, Kibana)**
   - Self-hosted
   - Advanced search
   - Custom dashboards

### Papertrail Setup

```bash
# Install Papertrail remote_syslog2
wget -O /tmp/remote_syslog2.deb https://github.com/papertrail/remote_syslog2/releases/download/v0.20/remote-syslog2_0.20_amd64.deb
sudo dpkg -i /tmp/remote_syslog2.deb

# Configure
sudo nano /etc/log_files.yml
```

```yaml
files:
  - /var/www/drinksafe/storage/logs/laravel.log
  - /var/log/nginx/drinksafe-error.log
  - /var/log/nginx/drinksafe-access.log

destination:
  host: logs.papertrailapp.com
  port: YOUR_PAPERTRAIL_PORT
  protocol: tls
```

```bash
# Start service
sudo systemctl start remote_syslog2
sudo systemctl enable remote_syslog2
```

### Log Rotation

**Ensure logs don't fill disk:**

```bash
# Laravel handles log rotation automatically
# Configure in config/logging.php

'single' => [
    'driver' => 'single',
    'path' => storage_path('logs/laravel.log'),
    'level' => 'debug',
    'days' => 14,  # Keep logs for 14 days
],
```

---

## Alerting Strategy

### Alert Levels

**P0 - Critical (Immediate Action Required)**

- Website down (5+ minutes)
- Database connection errors
- Redis connection errors
- Error rate >10% of requests
- Response time >5 seconds
- Disk space >95%

**Alert Methods:** SMS, Phone Call, PagerDuty, Slack

**P1 - High (Action Required Within 1 Hour)**

- Slow response time (>2 seconds)
- Error rate >5% of requests
- Queue workers stopped
- Memory usage >85%
- CPU usage >85%
- Disk space >85%

**Alert Methods:** Email, Slack

**P2 - Medium (Action Required Within 24 Hours)**

- Slow queries (>500ms)
- Cache hit ratio <80%
- Failed queue jobs accumulating
- Memory usage >75%
- Disk space >75%

**Alert Methods:** Email, Daily digest

**P3 - Low (Monitoring/Informational)**

- Performance degradation trends
- Security scan findings
- Backup completion status
- Certificate expiration warnings (30 days)

**Alert Methods:** Email, Weekly digest

### Alert Configuration Examples

**Slack Webhook for Critical Errors:**

```php
// config/logging.php
'slack' => [
    'driver' => 'slack',
    'url' => env('LOG_SLACK_WEBHOOK_URL'),
    'username' => 'DrinkSafe Alerts',
    'emoji' => ':fire:',
    'level' => 'critical',
],
```

**Email Alerts for Errors:**

```php
// config/logging.php
'mail' => [
    'driver' => 'monolog',
    'handler' => Monolog\Handler\NativeMailerHandler::class,
    'handler_with' => [
        'to' => 'alerts@drinksafe.example.com',
        'subject' => 'DrinkSafe Production Error',
    ],
    'level' => 'error',
],
```

---

## Dashboards

### Main Operations Dashboard

**Key Widgets:**

1. **System Health**
   - Uptime percentage (24h, 7d, 30d)
   - Current status (UP/DOWN)
   - Response time graph

2. **Application Metrics**
   - Requests per minute
   - Average response time
   - Error rate
   - Active users

3. **Server Resources**
   - CPU usage
   - Memory usage
   - Disk usage
   - Network I/O

4. **Database**
   - Connection count
   - Slow query count
   - Cache hit ratio
   - Database size

5. **Queue**
   - Jobs processed per minute
   - Failed jobs count
   - Queue depth
   - Worker status

### Security Dashboard

**Key Widgets:**

1. **Failed Logins**
2. **Suspicious Activity**
3. **Rate Limit Violations**
4. **Security Scan Results**
5. **SSL Certificate Status**

### Business Metrics Dashboard

**Key Widgets:**

1. **Total Venues**
2. **Total Reports**
3. **Reports per Day**
4. **Top Cities by Reports**
5. **Report Submission Success Rate**

---

## Monitoring Checklist

### Daily Checks

- [ ] Review error logs for new issues
- [ ] Check uptime status (should be 99.9%+)
- [ ] Verify queue workers are running
- [ ] Review slow query log
- [ ] Check disk space usage

### Weekly Checks

- [ ] Review performance trends
- [ ] Check database growth
- [ ] Review cache hit ratios
- [ ] Check security scan results
- [ ] Review backup status

### Monthly Checks

- [ ] Review capacity planning metrics
- [ ] Check SSL certificate expiration
- [ ] Review and update alert thresholds
- [ ] Analyze traffic patterns
- [ ] Plan for scaling if needed

---

## Monitoring Tools Summary

| Tool | Purpose | Cost | Recommendation |
|------|---------|------|----------------|
| Laravel Pulse | Application monitoring | Free | ✅ Essential |
| Netdata | Server monitoring | Free | ✅ Essential |
| Sentry | Error tracking | Free tier | ✅ Highly Recommended |
| UptimeRobot | Uptime monitoring | Free tier | ✅ Essential |
| Papertrail | Log management | Free tier | ✅ Recommended |
| Lighthouse CI | Performance audit | Free | ✅ Recommended |
| Prometheus + Grafana | Advanced metrics | Free (self-hosted) | Optional for scale |
| New Relic | APM | Paid | Optional |
| Datadog | Full observability | Paid | Optional for enterprise |

---

## Getting Started

### Minimum Monitoring Setup (Day 1)

1. Enable Laravel Pulse
2. Install Netdata
3. Set up UptimeRobot monitors
4. Configure error logging to Slack

**Time Required:** 2-3 hours

### Recommended Setup (Week 1)

1. Complete minimum setup
2. Set up Sentry error tracking
3. Configure Papertrail log aggregation
4. Create main operations dashboard
5. Configure alert thresholds

**Time Required:** 1 day

### Advanced Setup (Month 1)

1. Complete recommended setup
2. Set up Prometheus + Grafana
3. Configure custom business metrics
4. Set up automated performance testing
5. Create comprehensive runbooks

**Time Required:** 1 week

---

**End of Monitoring & Alerting Documentation**
