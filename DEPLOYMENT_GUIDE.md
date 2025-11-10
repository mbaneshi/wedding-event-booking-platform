# Deployment Guide - Wedding & Event Booking Platform

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Frontend Deployment (Vercel)](#frontend-deployment)
3. [Backend Deployment (DigitalOcean)](#backend-deployment)
4. [Database Setup](#database-setup)
5. [Environment Configuration](#environment-configuration)
6. [SSL Setup](#ssl-setup)
7. [Monitoring & Maintenance](#monitoring)

## Prerequisites

### Required Services
- Domain name (e.g., weddingplatform.com)
- Vercel account (free tier available)
- DigitalOcean/AWS account
- Stripe account (production mode)
- AWS S3 bucket for media storage
- SendGrid/Mailgun account for emails
- Google Maps API key

### Tools Needed
- Git
- Node.js 20+
- PHP 8.2+
- Composer
- PostgreSQL client

## Frontend Deployment (Vercel)

### 1. Connect GitHub Repository

```bash
# Push your code to GitHub
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/yourusername/wedding-platform.git
git push -u origin main
```

### 2. Deploy to Vercel

1. Go to [vercel.com](https://vercel.com)
2. Click "New Project"
3. Import your GitHub repository
4. Configure project:
   - Framework Preset: Next.js
   - Root Directory: `frontend`
   - Build Command: `npm run build`
   - Output Directory: `.next`

### 3. Environment Variables

Add these in Vercel dashboard:

```
NEXT_PUBLIC_API_URL=https://api.yourplatform.com/api
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_live_...
NEXT_PUBLIC_GOOGLE_MAPS_API_KEY=AIza...
```

### 4. Custom Domain Setup

1. Go to Project Settings > Domains
2. Add your domain: `www.yourplatform.com`
3. Update DNS records as instructed by Vercel
4. Wait for SSL certificate provisioning (automatic)

## Backend Deployment (DigitalOcean)

### 1. Create Droplet

```bash
# Create Ubuntu 22.04 droplet
# Recommended: $12/month (2GB RAM, 1 CPU)
# Choose datacenter closest to your users
```

### 2. Server Setup

SSH into your server:

```bash
ssh root@your-server-ip
```

Install required software:

```bash
# Update system
apt update && apt upgrade -y

# Install PHP 8.2
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php
apt update
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-pgsql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-redis php8.2-bcmath

# Install PostgreSQL
apt install -y postgresql postgresql-contrib

# Install Redis
apt install -y redis-server

# Install Nginx
apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install Certbot for SSL
apt install -y certbot python3-certbot-nginx
```

### 3. Database Setup

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE wedding_platform;
CREATE USER wedding_user WITH PASSWORD 'secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE wedding_platform TO wedding_user;
\q
```

### 4. Deploy Application

```bash
# Create app directory
mkdir -p /var/www/wedding-platform
cd /var/www/wedding-platform

# Clone repository
git clone https://github.com/yourusername/wedding-platform.git .

# Navigate to backend
cd backend

# Install dependencies
composer install --optimize-autoloader --no-dev

# Set permissions
chown -R www-data:www-data /var/www/wedding-platform
chmod -R 755 /var/www/wedding-platform/backend/storage
chmod -R 755 /var/www/wedding-platform/backend/bootstrap/cache
```

### 5. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Edit environment
nano .env
```

Set production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourplatform.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=wedding_platform
DB_USERNAME=wedding_user
DB_PASSWORD=secure_password_here

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=wedding-platform-media

MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG...
MAIL_FROM_ADDRESS=noreply@yourplatform.com

FRONTEND_URL=https://www.yourplatform.com
```

### 6. Run Migrations

```bash
php artisan migrate --force
php artisan db:seed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Nginx Configuration

```bash
nano /etc/nginx/sites-available/wedding-platform
```

Add configuration:

```nginx
server {
    listen 80;
    server_name api.yourplatform.com;
    root /var/www/wedding-platform/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:

```bash
ln -s /etc/nginx/sites-available/wedding-platform /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

### 8. SSL Certificate

```bash
certbot --nginx -d api.yourplatform.com
```

### 9. Setup Queue Worker

Create systemd service:

```bash
nano /etc/systemd/system/wedding-queue.service
```

```ini
[Unit]
Description=Wedding Platform Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/wedding-platform/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

Start service:

```bash
systemctl enable wedding-queue
systemctl start wedding-queue
```

### 10. Setup Cron Jobs

```bash
crontab -e -u www-data
```

Add:

```
* * * * * cd /var/www/wedding-platform/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Stripe Webhook Setup

1. Go to Stripe Dashboard > Developers > Webhooks
2. Add endpoint: `https://api.yourplatform.com/api/stripe/webhook`
3. Select events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`
4. Copy webhook signing secret to `.env`

## Database Backups

### Automated Daily Backups

```bash
nano /usr/local/bin/backup-db.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/postgresql"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

pg_dump -U wedding_user wedding_platform | gzip > $BACKUP_DIR/wedding_platform_$TIMESTAMP.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete
```

```bash
chmod +x /usr/local/bin/backup-db.sh
```

Add to crontab:

```
0 2 * * * /usr/local/bin/backup-db.sh
```

## Monitoring & Maintenance

### 1. Setup Monitoring

Install monitoring tools:

```bash
# Laravel Telescope (development only)
composer require laravel/telescope --dev
php artisan telescope:install

# Error tracking with Sentry
composer require sentry/sentry-laravel
```

### 2. Performance Monitoring

- Setup New Relic or DataDog
- Monitor Redis memory usage
- Track PostgreSQL query performance
- Monitor disk space

### 3. Log Management

```bash
# Rotate logs
nano /etc/logrotate.d/wedding-platform
```

```
/var/www/wedding-platform/backend/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    missingok
}
```

### 4. Security Updates

```bash
# Weekly security updates
apt update
apt upgrade -y
systemctl restart nginx
systemctl restart php8.2-fpm
```

## Deployment Checklist

- [ ] Domain DNS configured
- [ ] SSL certificates installed
- [ ] Database created and migrated
- [ ] Environment variables set
- [ ] Stripe webhooks configured
- [ ] Queue worker running
- [ ] Cron jobs configured
- [ ] Backups automated
- [ ] Monitoring setup
- [ ] Error tracking configured
- [ ] Email service configured
- [ ] AWS S3 bucket created
- [ ] Google Maps API enabled
- [ ] Firewall configured (UFW)
- [ ] Redis secured
- [ ] PostgreSQL secured
- [ ] Test payment flow (Stripe)
- [ ] Test email notifications
- [ ] Test booking flow end-to-end

## Rollback Procedure

If deployment fails:

```bash
cd /var/www/wedding-platform/backend
git checkout previous-stable-tag
composer install --no-dev
php artisan migrate:rollback
php artisan config:clear
php artisan cache:clear
systemctl restart wedding-queue
```

## Support & Troubleshooting

### Common Issues

1. **500 Error**: Check Laravel logs at `storage/logs/laravel.log`
2. **Queue not processing**: Check `systemctl status wedding-queue`
3. **Database connection failed**: Verify PostgreSQL credentials
4. **Stripe webhook failing**: Check webhook signing secret

### Useful Commands

```bash
# View logs
tail -f /var/www/wedding-platform/backend/storage/logs/laravel.log

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restart services
systemctl restart nginx
systemctl restart php8.2-fpm
systemctl restart wedding-queue
systemctl restart redis
```

## Production Maintenance

### Daily Tasks
- Monitor error logs
- Check queue processing
- Verify backups completed

### Weekly Tasks
- Review performance metrics
- Check disk space
- Update dependencies (if needed)

### Monthly Tasks
- Security updates
- Database optimization
- Review and archive old logs
- Test backup restoration

## Scaling Considerations

As your platform grows:

1. **Database**: Migrate to managed PostgreSQL (DigitalOcean Managed Database)
2. **Caching**: Use Redis cluster
3. **Load Balancing**: Add multiple app servers behind load balancer
4. **CDN**: Use CloudFlare for static assets
5. **Queue**: Use dedicated worker servers
6. **Search**: Implement Meilisearch or Elasticsearch

## Cost Estimation

### Monthly Costs
- DigitalOcean Droplet: $12-24/month
- Domain: $1-2/month
- Vercel: Free (hobby) or $20/month (pro)
- AWS S3: $5-20/month
- Email service: $10-50/month
- Stripe fees: 2.9% + $0.20 per transaction
- Google Maps API: $0-100/month

**Total**: ~$50-200/month depending on usage

## Success Metrics

Track these KPIs post-deployment:
- Uptime > 99.9%
- Page load time < 3 seconds
- API response time < 500ms
- Payment success rate > 98%
- Zero critical security issues
