# Monitoring & Observability Guide

## Overview

This guide covers monitoring, logging, and observability for the Wedding Booking Platform.

## Error Tracking - Sentry

### Setup

1. Create a Sentry project at [sentry.io](https://sentry.io)
2. Add DSN to environment variables:

```env
SENTRY_LARAVEL_DSN=https://xxx@xxx.ingest.sentry.io/xxx
SENTRY_TRACES_SAMPLE_RATE=0.2
```

3. Install Sentry SDK:

```bash
composer require sentry/sentry-laravel
npm install @sentry/react @sentry/tracing
```

### Backend Configuration

Configuration file: `/backend/config/sentry.php`

Key features:
- Automatic error capture
- Performance monitoring
- Breadcrumbs for context
- PII filtering for privacy

### Frontend Configuration

```typescript
// frontend/lib/sentry.ts
import * as Sentry from '@sentry/react'

Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  environment: process.env.NODE_ENV,
  tracesSampleRate: 0.2,
  integrations: [
    new Sentry.BrowserTracing(),
    new Sentry.Replay()
  ]
})
```

### What Gets Tracked

**Automatically:**
- Unhandled exceptions
- Promise rejections
- HTTP errors (4xx, 5xx)
- Database query errors
- Queue job failures

**Manually:**
```php
// Backend
Sentry\captureException($exception);
Sentry\captureMessage('Custom message', 'warning');

// Frontend
Sentry.captureException(error);
Sentry.captureMessage('Custom message');
```

## Application Logging

### Backend Logging

Laravel logging configuration: `/backend/config/logging.php`

**Log Channels:**
- `stack`: Multi-channel logging
- `daily`: Daily rotating files
- `slack`: Critical errors to Slack
- `sentry`: Errors to Sentry

**Usage:**
```php
use Illuminate\Support\Facades\Log;

Log::info('User logged in', ['user_id' => $user->id]);
Log::warning('Slow query detected', ['duration' => $duration]);
Log::error('Payment failed', ['booking_id' => $id, 'error' => $e->getMessage()]);
```

### Request Logging

Middleware: `/backend/app/Http/Middleware/RequestLogging.php`

Logs all API requests with:
- Method, URL, IP address
- User ID (if authenticated)
- Response status code
- Request duration
- Payload (development only)

Slow requests (>1000ms) are flagged automatically.

### Frontend Logging

```typescript
// frontend/lib/logger.ts
import * as Sentry from '@sentry/react'

export const logger = {
  info: (message: string, context?: object) => {
    console.info(message, context)
    Sentry.addBreadcrumb({ level: 'info', message, data: context })
  },

  error: (message: string, error?: Error) => {
    console.error(message, error)
    if (error) Sentry.captureException(error)
  }
}
```

## Performance Monitoring

### Backend Performance

**Laravel Telescope** (Development):
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access at: `http://localhost:8000/telescope`

Monitors:
- Requests
- Commands
- Database queries
- Queue jobs
- Cache operations
- Redis commands

**Database Query Monitoring:**
```php
DB::listen(function ($query) {
    if ($query->time > 1000) {
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time
        ]);
    }
});
```

### Frontend Performance

**Web Vitals Tracking:**
```typescript
import { getCLS, getFID, getFCP, getLCP, getTTFB } from 'web-vitals'

function sendToAnalytics(metric) {
  const body = JSON.stringify(metric)
  navigator.sendBeacon('/api/analytics', body)
}

getCLS(sendToAnalytics)
getFID(sendToAnalytics)
getFCP(sendToAnalytics)
getLCP(sendToAnalytics)
getTTFB(sendToAnalytics)
```

**React Performance Profiler:**
```typescript
import { Profiler } from 'react'

<Profiler id="VendorList" onRender={onRenderCallback}>
  <VendorList />
</Profiler>
```

## Application Metrics

### Key Metrics to Monitor

**Backend:**
- Request rate (requests/second)
- Response time (p50, p95, p99)
- Error rate (%)
- Database query time
- Queue job processing time
- Active users
- API endpoint usage

**Frontend:**
- Page load time
- Time to interactive
- Bounce rate
- User journey completion rate
- Client-side errors

### Metrics Collection

**Custom Metrics:**
```php
// Backend
Metrics::increment('bookings.created');
Metrics::timing('payment.processing', $duration);
Metrics::gauge('users.active', $count);
```

## Health Checks

### Backend Health Endpoint

```http
GET /api/health
```

Response:
```json
{
  "status": "healthy",
  "checks": {
    "database": "ok",
    "redis": "ok",
    "elasticsearch": "ok",
    "storage": "ok"
  },
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### Automated Monitoring

**Uptime Monitoring:**
- Use services like UptimeRobot, Pingdom, or StatusCake
- Monitor: `/api/health` endpoint
- Alert on: 3 consecutive failures
- Check frequency: 1 minute

**Response Time Monitoring:**
- Alert if p95 response time > 2000ms
- Alert if p99 response time > 5000ms

## Alerting

### Alert Channels

1. **Email** - Critical errors
2. **Slack** - All errors and warnings
3. **PagerDuty** - Production incidents
4. **SMS** - Critical production failures

### Alert Rules

**Critical (Immediate):**
- API downtime
- Database connection failures
- Payment processing errors
- 50x error rate > 5%

**Warning (15 minutes):**
- Slow response times (>2s)
- High memory usage (>80%)
- Disk space low (<20%)
- Queue backlog growing

**Info:**
- Deployment completed
- Scheduled maintenance
- New user signups milestone

### Slack Notifications

```php
// config/logging.php
'slack' => [
    'driver' => 'slack',
    'url' => env('LOG_SLACK_WEBHOOK_URL'),
    'username' => 'Wedding Platform',
    'emoji' => ':boom:',
    'level' => 'error',
],
```

## Log Aggregation

### ELK Stack (Recommended)

**Elasticsearch**: Log storage and search
**Logstash**: Log processing and transformation
**Kibana**: Visualization and dashboards

**Setup:**
```yaml
# docker-compose.yml
elasticsearch:
  image: elasticsearch:8.5.0
  ports:
    - "9200:9200"

logstash:
  image: logstash:8.5.0
  volumes:
    - ./logstash.conf:/usr/share/logstash/pipeline/logstash.conf

kibana:
  image: kibana:8.5.0
  ports:
    - "5601:5601"
```

### Alternative: CloudWatch / DataDog

For AWS deployments, use CloudWatch:
```bash
composer require aws/aws-sdk-php
```

Configuration:
```php
'cloudwatch' => [
    'driver' => 'custom',
    'via' => CloudWatchLogger::class,
    'sdk' => [
        'region' => env('AWS_DEFAULT_REGION'),
        'version' => 'latest',
    ],
    'retention' => 14,
],
```

## Dashboard Setup

### Kibana Dashboards

Create dashboards for:
1. **API Performance**: Request rate, response time, errors
2. **Business Metrics**: Bookings, revenue, user signups
3. **Errors**: Error rate by type, affected users
4. **Infrastructure**: CPU, memory, disk usage

### Grafana Dashboards

For infrastructure monitoring:
```yaml
# Prometheus metrics endpoint
GET /api/metrics
```

Sample metrics:
- `http_requests_total`
- `http_request_duration_seconds`
- `database_connections_active`
- `queue_jobs_processed_total`

## Debugging in Production

### Enable Debug Mode Temporarily

**NEVER enable** `APP_DEBUG=true` in production!

Instead, use Laravel Telescope or Sentry for debugging.

### Log Levels

```
DEBUG    - Detailed debug information
INFO     - Interesting events
WARNING  - Exceptional occurrences that are not errors
ERROR    - Runtime errors that don't require immediate action
CRITICAL - Critical conditions
```

### Viewing Logs

```bash
# Tail logs in real-time
tail -f storage/logs/laravel.log

# Search for errors
grep "ERROR" storage/logs/laravel.log

# View last 100 lines
tail -n 100 storage/logs/laravel.log
```

## Security Monitoring

Monitor for:
- Failed login attempts (>5 in 5 minutes)
- SQL injection attempts
- XSS attempts
- Unusual API usage patterns
- Unauthorized access attempts
- Rate limit violations

**Alert immediately** on suspicious activity.

## Performance Optimization

### Identifying Bottlenecks

1. **Slow Database Queries**
   - Use Laravel Debugbar or Telescope
   - Add database indexes
   - Optimize N+1 queries

2. **Memory Issues**
   - Profile with Blackfire
   - Check for memory leaks
   - Optimize image processing

3. **Slow API Responses**
   - Enable caching
   - Use CDN for static assets
   - Implement pagination

### Caching Strategy

```php
// Cache vendor search results
$vendors = Cache::remember('vendors.search.' . $hash, 300, function () {
    return Vendor::where(...)->get();
});
```

## Incident Response

### When Alerts Fire

1. **Acknowledge** the alert
2. **Assess** the severity and impact
3. **Communicate** status to team
4. **Investigate** logs and metrics
5. **Fix** the issue
6. **Document** the incident
7. **Post-mortem** review

### Runbooks

Create runbooks for common incidents:
- Database connection failures
- Payment gateway issues
- Email delivery problems
- High traffic events

## Maintenance Windows

Schedule regular maintenance:
- **Database backups**: Daily at 2 AM
- **Log rotation**: Daily
- **Security updates**: Weekly
- **Performance review**: Monthly

Notify users 24 hours before planned maintenance.

## Resources

- [Sentry Documentation](https://docs.sentry.io/)
- [Laravel Logging](https://laravel.com/docs/logging)
- [ELK Stack](https://www.elastic.co/elastic-stack)
- [Grafana](https://grafana.com/)
- [New Relic](https://newrelic.com/)
