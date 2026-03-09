# Complete Setup Guide - Wedding & Event Booking Platform

This guide walks you through setting up the complete wedding booking platform from scratch.

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Quick Start with Docker](#quick-start-docker)
3. [Manual Installation](#manual-installation)
4. [Database Setup](#database-setup)
5. [Configuration](#configuration)
6. [Running the Application](#running-the-application)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

## System Requirements

### Minimum Requirements
- **CPU:** 2 cores
- **RAM:** 4GB
- **Disk:** 10GB free space
- **OS:** Ubuntu 20.04+, macOS 12+, or Windows 10+ with WSL2

### Software Requirements
- Node.js 20.x or higher
- PHP 8.2 or higher
- PostgreSQL 16.x or higher
- Redis 7.x or higher
- Composer 2.x
- npm or yarn

## Quick Start with Docker

The fastest way to get started is using Docker Compose:

### 1. Install Docker
- **macOS/Windows:** Install [Docker Desktop](https://www.docker.com/products/docker-desktop)
- **Linux:** Install Docker Engine and Docker Compose

### 2. Clone and Start
```bash
cd wedding-event-booking-platform

# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Access services:
# - Frontend: http://localhost:3000
# - Backend API: http://localhost:8000
# - PostgreSQL: localhost:5432
# - Redis: localhost:6379
```

### 3. Initialize Database
```bash
# Run migrations
docker-compose exec backend php artisan migrate

# Seed sample data
docker-compose exec backend php artisan db:seed
```

### 4. Stop Services
```bash
docker-compose down
```

## Manual Installation

### Step 1: Install Prerequisites

#### macOS
```bash
# Install Homebrew
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install dependencies
brew install node php@8.2 postgresql@16 redis composer

# Start services
brew services start postgresql@16
brew services start redis
```

#### Ubuntu/Debian
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install PHP 8.2
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-pgsql \
  php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-redis

# Install PostgreSQL 16
sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget -qO- https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
sudo apt update
sudo apt install -y postgresql-16

# Install Redis
sudo apt install -y redis-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 2: Clone Repository
```bash
cd wedding-event-booking-platform
```

### Step 3: Setup Backend

```bash
cd backend

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file
nano .env
```

Configure these values in `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=wedding_platform
DB_USERNAME=your_username
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

STRIPE_KEY=pk_test_your_key
STRIPE_SECRET=sk_test_your_secret
STRIPE_WEBHOOK_SECRET=whsec_your_secret

GOOGLE_MAPS_API_KEY=your_google_maps_key
```

### Step 4: Setup Frontend

```bash
cd ../frontend

# Install dependencies
npm install

# Copy environment file
cp .env.example .env.local

# Edit .env.local
nano .env.local
```

Configure:
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_your_key
NEXT_PUBLIC_GOOGLE_MAPS_API_KEY=your_google_maps_key
```

## Database Setup

### Option 1: Using PostgreSQL Directly

```bash
# Create database
createdb wedding_platform

# Run migrations
cd wedding-event-booking-platform/database/migrations

psql wedding_platform < 001_create_users_table.sql
psql wedding_platform < 002_create_categories_table.sql
psql wedding_platform < 003_create_vendors_table.sql
psql wedding_platform < 004_create_services_table.sql
psql wedding_platform < 005_create_bookings_table.sql
psql wedding_platform < 006_create_payments_table.sql
psql wedding_platform < 007_create_reviews_table.sql
psql wedding_platform < 008_create_additional_tables.sql
```

### Option 2: Using Laravel Migrations (Recommended)

First, create Laravel migration files based on the SQL files:

```bash
cd backend

# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed
```

### Verify Database

```bash
psql wedding_platform

# Check tables
\dt

# Expected tables:
# - users
# - vendors
# - categories
# - services
# - bookings
# - payments
# - reviews
# - availability
# - media
# - messages
# - favorites
```

## Configuration

### 1. Stripe Setup

1. Create account at [stripe.com](https://stripe.com)
2. Get API keys from Dashboard > Developers > API keys
3. Add to `.env` files (both backend and frontend)
4. Setup webhook endpoint (see below)

### 2. Google Maps API

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create new project
3. Enable Maps JavaScript API and Places API
4. Create API key
5. Add to `.env.local` in frontend

### 3. Email Service (Optional)

For development, use [Mailtrap](https://mailtrap.io):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

For production, use SendGrid or Mailgun.

### 4. File Storage

For local development:
```env
FILESYSTEM_DISK=local
```

For production with AWS S3:
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name
```

## Running the Application

### Start Backend

```bash
cd backend

# Start PHP server
php artisan serve

# In another terminal, start queue worker
php artisan queue:work

# Backend will be available at http://localhost:8000
```

### Start Frontend

```bash
cd frontend

# Development mode
npm run dev

# Frontend will be available at http://localhost:3000
```

### Access the Application

- **Frontend:** http://localhost:3000
- **Backend API:** http://localhost:8000/api
- **API Documentation:** http://localhost:8000/api/documentation (if setup)

## Testing

### Backend Tests

```bash
cd backend

# Run all tests
php artisan test

# Run specific test
php artisan test --filter BookingTest

# With coverage
php artisan test --coverage
```

### Frontend Tests

```bash
cd frontend

# Run tests
npm test

# Run with coverage
npm test -- --coverage

# Run in watch mode
npm test -- --watch
```

### Manual Testing Checklist

#### User Registration & Login
- [ ] Register as customer
- [ ] Register as vendor
- [ ] Login with valid credentials
- [ ] Login with invalid credentials (should fail)
- [ ] Logout

#### Vendor Search
- [ ] Search without filters
- [ ] Filter by category
- [ ] Filter by location
- [ ] Filter by price range
- [ ] Filter by availability date
- [ ] Sort by rating
- [ ] Sort by price

#### Vendor Profile
- [ ] View vendor details
- [ ] View photo gallery
- [ ] View reviews
- [ ] Check availability calendar
- [ ] Add to favorites

#### Booking Flow
- [ ] Select service
- [ ] Choose event date
- [ ] Enter event details
- [ ] Review booking summary
- [ ] Process payment (use test card: 4242 4242 4242 4242)
- [ ] Receive confirmation email
- [ ] View booking in dashboard

#### Vendor Dashboard
- [ ] View upcoming bookings
- [ ] View revenue stats
- [ ] Manage services
- [ ] Update availability
- [ ] Respond to reviews
- [ ] Update profile

#### Admin Dashboard
- [ ] View pending vendors
- [ ] Approve vendor
- [ ] Reject vendor
- [ ] View all bookings
- [ ] View analytics

### Stripe Test Cards

For testing payments:
- **Success:** 4242 4242 4242 4242
- **Decline:** 4000 0000 0000 0002
- **Insufficient funds:** 4000 0000 0000 9995

Use any future expiration date and any 3-digit CVC.

## Troubleshooting

### Database Connection Failed

```bash
# Check PostgreSQL is running
psql --version
pg_isready

# Test connection
psql -h localhost -U your_username -d wedding_platform

# If password authentication fails, edit pg_hba.conf
# Change "peer" to "md5" for local connections
```

### Redis Connection Failed

```bash
# Check Redis is running
redis-cli ping
# Should return: PONG

# Start Redis if not running
redis-server

# Or on macOS with Homebrew
brew services start redis
```

### Frontend Build Errors

```bash
# Clear cache and rebuild
cd frontend
rm -rf .next node_modules
npm install
npm run build
```

### Backend Errors

```bash
# Clear Laravel cache
cd backend
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerate autoload files
composer dump-autoload
```

### Port Already in Use

```bash
# Find process using port
lsof -i :3000  # Frontend
lsof -i :8000  # Backend

# Kill process
kill -9 <PID>

# Or use different ports
# Frontend: npm run dev -- -p 3001
# Backend: php artisan serve --port=8001
```

### Stripe Webhook Testing

```bash
# Install Stripe CLI
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Forward webhooks to local server
stripe listen --forward-to localhost:8000/api/stripe/webhook

# In another terminal, trigger test event
stripe trigger payment_intent.succeeded
```

## Sample Data

### Create Test Admin User

```bash
cd backend
php artisan tinker

# In tinker console:
User::create([
    'email' => 'admin@test.com',
    'password' => Hash::make('password'),
    'first_name' => 'Admin',
    'last_name' => 'User',
    'role' => 'admin',
    'email_verified_at' => now()
]);
```

### Create Test Categories

```sql
INSERT INTO categories (name, slug, icon, sort_order) VALUES
('Wedding Venues', 'venues', '🏛️', 1),
('Photographers', 'photographers', '📷', 2),
('Catering', 'catering', '🍽️', 3),
('Florists', 'florists', '💐', 4),
('Music & DJ', 'music', '🎵', 5),
('Decorators', 'decorators', '🎨', 6);
```

## Next Steps

After successful setup:

1. **Customize branding:**
   - Update colors in `tailwind.config.ts`
   - Replace logo in `/public` folder
   - Update site name in layouts

2. **Configure email templates:**
   - Create email views in `backend/resources/views/emails`
   - Customize notification content

3. **Setup analytics:**
   - Add Google Analytics tracking code
   - Setup Sentry for error tracking

4. **Security:**
   - Change default passwords
   - Setup firewall rules
   - Configure CORS properly

5. **Performance:**
   - Enable Redis caching
   - Setup CDN for static assets
   - Optimize images

6. **Deployment:**
   - Follow [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
   - Setup CI/CD pipeline
   - Configure production environment

## Getting Help

- **Documentation:** See [REQUIREMENTS.md](REQUIREMENTS.md)
- **API Docs:** See [PROJECT_README.md](PROJECT_README.md)
- **Deployment:** See [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

## Common Commands Reference

### Backend
```bash
php artisan serve              # Start server
php artisan queue:work         # Start queue worker
php artisan migrate            # Run migrations
php artisan migrate:rollback   # Rollback last migration
php artisan db:seed            # Seed database
php artisan cache:clear        # Clear cache
php artisan tinker             # Interactive console
```

### Frontend
```bash
npm run dev         # Start development server
npm run build       # Build for production
npm run start       # Start production server
npm run lint        # Run linter
npm test            # Run tests
```

### Docker
```bash
docker-compose up -d           # Start all services
docker-compose down            # Stop all services
docker-compose logs -f         # View logs
docker-compose exec backend bash    # Access backend container
docker-compose exec frontend sh     # Access frontend container
```

## Success Indicators

Your setup is successful when:

✅ Frontend loads at http://localhost:3000
✅ Backend API responds at http://localhost:8000/api
✅ Database tables are created
✅ Redis is connected
✅ You can register a user
✅ You can search vendors
✅ Test payment works

Happy coding!
