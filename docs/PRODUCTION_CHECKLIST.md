# Production Deployment Checklist

## Pre-Deployment

### Code Quality

- [ ] All tests passing (frontend, backend, E2E)
- [ ] Code coverage ≥80%
- [ ] No TODO comments remaining
- [ ] Code reviewed and approved
- [ ] No console.log or debug statements
- [ ] Linting passes without errors
- [ ] TypeScript/PHPStan checks pass

### Security

- [ ] Environment variables configured (no hardcoded secrets)
- [ ] API keys rotated and secured
- [ ] HTTPS/SSL certificates installed
- [ ] CORS properly configured
- [ ] Rate limiting enabled
- [ ] Input validation on all endpoints
- [ ] SQL injection prevention verified
- [ ] XSS protection enabled
- [ ] CSRF protection enabled
- [ ] Security headers configured
- [ ] Dependencies updated (no known vulnerabilities)
- [ ] File upload restrictions in place

### Configuration

- [ ] `.env.production` file configured
- [ ] Database credentials set
- [ ] Redis credentials set
- [ ] Elasticsearch credentials set
- [ ] Stripe API keys (production mode)
- [ ] Email SMTP settings configured
- [ ] AWS S3/storage credentials set
- [ ] Sentry DSN configured
- [ ] Frontend API URL updated
- [ ] Google Maps API key set
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] Trusted proxies configured

### Database

- [ ] Database backups configured
- [ ] Database migrations tested
- [ ] Seeders reviewed (don't seed test data!)
- [ ] Indexes optimized
- [ ] Foreign key constraints verified
- [ ] Database connection pooling configured
- [ ] Read replicas configured (if applicable)

### Infrastructure

- [ ] Server resources adequate (CPU, RAM, Disk)
- [ ] Load balancer configured
- [ ] Auto-scaling rules set
- [ ] CDN configured for static assets
- [ ] Firewall rules configured
- [ ] Backup server/region available
- [ ] DNS records configured
- [ ] SSL certificates valid and auto-renewing

## Deployment

### Backend Deployment

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Run migrations
php artisan migrate --force

# 5. Restart services
php artisan queue:restart
php artisan octane:reload  # If using Octane
```

### Frontend Deployment

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
npm ci --production

# 3. Build
npm run build

# 4. Deploy to hosting
# (Vercel, Netlify, or custom server)
```

### Docker Deployment

```bash
# 1. Build images
docker-compose build

# 2. Start services
docker-compose up -d

# 3. Run migrations
docker-compose exec backend php artisan migrate --force

# 4. Verify all services running
docker-compose ps
```

## Post-Deployment Verification

### Smoke Tests

- [ ] Homepage loads
- [ ] Search functionality works
- [ ] User registration works
- [ ] User login works
- [ ] Vendor listing displays
- [ ] Booking creation works
- [ ] Payment processing works
- [ ] Email notifications sent
- [ ] API health check passes
- [ ] Admin panel accessible

### Performance Tests

- [ ] Page load time <3 seconds
- [ ] API response time <500ms (p95)
- [ ] Database query time acceptable
- [ ] No N+1 query issues
- [ ] CDN serving static assets
- [ ] Images optimized and loading fast

### Monitoring Setup

- [ ] Sentry receiving errors
- [ ] Logs being aggregated
- [ ] Health check monitoring active
- [ ] Uptime monitoring configured
- [ ] Alert channels working (email, Slack)
- [ ] Performance metrics tracked
- [ ] Database monitoring active

### Security Verification

- [ ] HTTPS enforced (no mixed content)
- [ ] Security headers present
- [ ] Rate limiting working
- [ ] CORS properly restricted
- [ ] File uploads restricted
- [ ] SQL injection tests pass
- [ ] XSS tests pass

## Environment Variables

### Backend (.env)

```env
APP_NAME="Wedding Booking Platform"
APP_ENV=production
APP_KEY=base64:xxx
APP_DEBUG=false
APP_URL=https://api.wedding-platform.com

DB_CONNECTION=mysql
DB_HOST=xxx
DB_PORT=3306
DB_DATABASE=wedding_platform
DB_USERNAME=xxx
DB_PASSWORD=xxx

REDIS_HOST=xxx
REDIS_PASSWORD=xxx
REDIS_PORT=6379

ELASTICSEARCH_HOST=xxx:9200

STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxx
MAIL_PASSWORD=xxx
MAIL_FROM_ADDRESS=noreply@wedding-platform.com

AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=wedding-platform-media

SENTRY_LARAVEL_DSN=https://xxx@xxx.ingest.sentry.io/xxx
SENTRY_TRACES_SAMPLE_RATE=0.2

PUSHER_APP_ID=xxx
PUSHER_APP_KEY=xxx
PUSHER_APP_SECRET=xxx
PUSHER_APP_CLUSTER=mt1
```

### Frontend (.env.production)

```env
NEXT_PUBLIC_API_URL=https://api.wedding-platform.com/api
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_live_xxx
NEXT_PUBLIC_GOOGLE_MAPS_API_KEY=xxx
NEXT_PUBLIC_SENTRY_DSN=https://xxx@xxx.ingest.sentry.io/xxx
```

## SSL Configuration

### Let's Encrypt (Free SSL)

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d wedding-platform.com -d www.wedding-platform.com

# Auto-renewal (cron)
0 0 * * * certbot renew --quiet
```

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name wedding-platform.com;

    ssl_certificate /etc/letsencrypt/live/wedding-platform.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/wedding-platform.com/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000" always;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name wedding-platform.com;
    return 301 https://$server_name$request_uri;
}
```

## Rollback Plan

### Quick Rollback

```bash
# 1. Revert to previous version
git reset --hard HEAD~1

# 2. Reinstall dependencies
composer install --no-dev
npm ci --production

# 3. Rebuild
npm run build

# 4. Restart services
php artisan queue:restart
```

### Database Rollback

```bash
# Rollback last migration
php artisan migrate:rollback

# Rollback specific migration
php artisan migrate:rollback --step=1

# Restore from backup (if needed)
mysql -u root -p wedding_platform < backup_2024_01_15.sql
```

## Maintenance Mode

### Enable Maintenance Mode

```bash
# Backend
php artisan down --message="Scheduled maintenance" --retry=60

# Or with secret to bypass
php artisan down --secret="maintenance-bypass-token"
# Access: https://wedding-platform.com/maintenance-bypass-token
```

### Disable Maintenance Mode

```bash
php artisan up
```

## Performance Optimization

### Backend Optimizations

```bash
# Optimize autoloader
composer dump-autoload --optimize

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue configuration
php artisan queue:restart

# Optimize images
php artisan media:optimize
```

### Frontend Optimizations

- Enable compression (gzip/brotli)
- Minify CSS/JS
- Optimize images (WebP format)
- Lazy load images
- Code splitting
- CDN for static assets
- Browser caching headers

## Backup Strategy

### Automated Backups

```bash
# Daily database backup (cron)
0 2 * * * mysqldump -u root -p wedding_platform | gzip > /backups/db_$(date +\%Y\%m\%d).sql.gz

# Weekly full backup
0 3 * * 0 tar -czf /backups/full_$(date +\%Y\%m\%d).tar.gz /var/www/wedding-platform

# Backup retention: 30 days
find /backups -mtime +30 -delete
```

### Backup Verification

- [ ] Test database restore monthly
- [ ] Verify backup integrity
- [ ] Store backups off-site
- [ ] Document restore procedures

## Post-Launch Monitoring

### Week 1
- Monitor errors closely (hourly)
- Check performance metrics (daily)
- Review user feedback
- Watch for unusual patterns

### Week 2-4
- Analyze user behavior
- Identify optimization opportunities
- Address reported issues
- Plan improvements

### Ongoing
- Monthly security audits
- Quarterly performance reviews
- Regular dependency updates
- Continuous optimization

## Emergency Contacts

- **DevOps Lead**: devops@company.com / +1-xxx-xxx-xxxx
- **Database Admin**: dba@company.com / +1-xxx-xxx-xxxx
- **Security Team**: security@company.com / +1-xxx-xxx-xxxx
- **On-Call Engineer**: oncall@company.com / +1-xxx-xxx-xxxx
- **Hosting Provider**: support@provider.com / 24/7 Support
- **CDN Provider**: support@cdn.com / 24/7 Support

## Success Criteria

- [ ] Zero downtime during deployment
- [ ] All tests passing post-deployment
- [ ] Error rate <0.1%
- [ ] API response time <500ms (p95)
- [ ] Page load time <3 seconds
- [ ] No critical security vulnerabilities
- [ ] Monitoring and alerts functional
- [ ] Backup system operational
- [ ] Team trained on deployment process
- [ ] Documentation up to date

## Sign-Off

- [ ] **Tech Lead**: _________________ Date: _______
- [ ] **DevOps**: _________________ Date: _______
- [ ] **Security**: _________________ Date: _______
- [ ] **QA**: _________________ Date: _______
- [ ] **Product Manager**: _________________ Date: _______

---

**Deployment Date**: _________________

**Deployed By**: _________________

**Notes**: _________________
