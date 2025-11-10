# Wedding & Event Booking Platform

A comprehensive multi-sided marketplace platform connecting couples with wedding and event service vendors. Built with Next.js 14, Laravel 10, and PostgreSQL.

## Features

### Customer Features
- Search and filter vendors by category, location, price, and availability
- View detailed vendor profiles with galleries and reviews
- Real-time availability checking
- Secure booking with deposit and balance payments
- Booking management dashboard
- Leave reviews and ratings
- Favorites/wishlist functionality
- Direct messaging with vendors

### Vendor Features
- Comprehensive vendor dashboard
- Manage service listings and pricing
- Calendar with availability management
- Booking management and notifications
- Revenue and analytics tracking
- Respond to customer reviews
- Media gallery management
- Profile customization

### Admin Features
- Vendor approval workflow
- Platform-wide analytics
- Booking and payment oversight
- Review moderation
- Category management
- Commission tracking

## Tech Stack

### Frontend
- **Framework:** Next.js 14 with App Router
- **Language:** TypeScript 5
- **Styling:** Tailwind CSS 3
- **UI Components:** Radix UI
- **State Management:** Zustand
- **Forms:** React Hook Form + Zod
- **HTTP Client:** Axios
- **Maps:** Google Maps API

### Backend
- **Framework:** Laravel 10 (PHP 8.2+)
- **Database:** PostgreSQL 16
- **Authentication:** Laravel Sanctum
- **Payment:** Stripe API
- **Cache:** Redis
- **Storage:** AWS S3 / Cloudinary
- **Queue:** Redis

## Project Structure

```
/
├── frontend/               # Next.js frontend
│   ├── app/
│   │   ├── (public)/      # Public pages
│   │   ├── (dashboard)/   # Dashboard pages
│   │   ├── api/           # API routes
│   │   └── components/    # React components
│   ├── lib/               # Utilities
│   └── types/             # TypeScript types
│
├── backend/               # Laravel backend
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Jobs/
│   ├── database/migrations/
│   ├── routes/
│   └── config/
│
└── database/              # Database migrations
    └── migrations/        # SQL migration files
```

## Getting Started

### Prerequisites
- Node.js 20+
- PHP 8.2+
- PostgreSQL 16+
- Redis 7+
- Composer
- npm/yarn

### Installation

#### 1. Clone the repository
```bash
git clone <repository-url>
cd 11-wedding-event-booking-platform
```

#### 2. Setup Frontend
```bash
cd frontend
npm install
cp .env.example .env
```

Edit `.env` and configure:
```
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_...
NEXT_PUBLIC_GOOGLE_MAPS_API_KEY=...
```

Start development server:
```bash
npm run dev
```

Frontend will be available at `http://localhost:3000`

#### 3. Setup Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=wedding_platform
DB_USERNAME=postgres
DB_PASSWORD=your_password

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_BUCKET=...

GOOGLE_MAPS_API_KEY=...
```

#### 4. Setup Database
```bash
# Create PostgreSQL database
createdb wedding_platform

# Run migrations
cd database/migrations
psql wedding_platform < 001_create_users_table.sql
psql wedding_platform < 002_create_categories_table.sql
psql wedding_platform < 003_create_vendors_table.sql
psql wedding_platform < 004_create_services_table.sql
psql wedding_platform < 005_create_bookings_table.sql
psql wedding_platform < 006_create_payments_table.sql
psql wedding_platform < 007_create_reviews_table.sql
psql wedding_platform < 008_create_additional_tables.sql
```

Or using Laravel migrations:
```bash
cd backend
php artisan migrate
php artisan db:seed
```

#### 5. Start Backend Server
```bash
cd backend
php artisan serve
```

Backend API will be available at `http://localhost:8000`

#### 6. Start Queue Worker
```bash
php artisan queue:work
```

### Stripe Webhook Setup

1. Install Stripe CLI: https://stripe.com/docs/stripe-cli
2. Login: `stripe login`
3. Forward webhooks:
```bash
stripe listen --forward-to localhost:8000/api/stripe/webhook
```
4. Copy webhook signing secret to `.env`

## API Documentation

### Authentication
```
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me
```

### Vendors
```
GET    /api/vendors              # Search vendors
GET    /api/vendors/:id          # Get vendor details
POST   /api/vendors              # Create vendor
PUT    /api/vendors/:id          # Update vendor
GET    /api/vendors/:id/services # Get services
GET    /api/vendors/:id/reviews  # Get reviews
GET    /api/vendors/:id/availability # Get availability
```

### Bookings
```
GET    /api/bookings             # List bookings
GET    /api/bookings/:id         # Get booking details
POST   /api/bookings             # Create booking
POST   /api/bookings/:id/cancel  # Cancel booking
POST   /api/bookings/:id/confirm # Confirm booking
POST   /api/bookings/:id/complete # Complete booking
```

### Payments
```
POST   /api/payments/create-intent # Create payment intent
POST   /api/payments/confirm       # Confirm payment
POST   /api/stripe/webhook         # Stripe webhook
```

### Reviews
```
POST   /api/reviews                # Create review
POST   /api/reviews/:id/respond    # Vendor response
```

## Database Schema

See `REQUIREMENTS.md` for complete database schema with 15+ tables including:
- users
- vendors
- categories
- services
- bookings
- payments
- reviews
- availability
- media
- messages
- favorites

## Testing

### Frontend Tests
```bash
cd frontend
npm test
```

### Backend Tests
```bash
cd backend
php artisan test
```

## Deployment

### Frontend (Vercel)
```bash
cd frontend
vercel
```

### Backend (DigitalOcean/AWS)
1. Setup Ubuntu server
2. Install PHP, PostgreSQL, Redis, Nginx
3. Configure domain and SSL
4. Deploy with:
```bash
git pull
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

### Environment Variables
Ensure all production environment variables are set:
- Database credentials
- Stripe live keys
- AWS S3 credentials
- Google Maps API key
- Email service credentials

## Key Features Implementation

### Search & Filter System
- Category-based filtering
- Location-based search (city, radius)
- Price range filtering
- Availability checking
- Rating-based filtering
- Sort by relevance, price, rating

### Booking Flow
1. Select vendor and service
2. Choose event date (with availability check)
3. Enter event details
4. Review booking summary
5. Secure payment (Stripe)
6. Confirmation email
7. Vendor notification

### Payment Processing
- Stripe integration
- Deposit + balance payment model
- Automatic commission calculation (10%)
- Refund support
- Payment history tracking

### Vendor Dashboard
- Booking calendar
- Revenue analytics
- Service management
- Availability management
- Review responses
- Profile management

### Admin Dashboard
- Vendor approval workflow
- Platform analytics
- Booking oversight
- Payment monitoring
- Review moderation

## Security

- Laravel Sanctum authentication
- CSRF protection
- SQL injection prevention (prepared statements)
- XSS protection
- Rate limiting
- Secure payment handling (PCI-DSS compliant via Stripe)

## Performance Optimization

- Redis caching
- Database indexing
- Image optimization (CDN)
- Query optimization
- Lazy loading
- Code splitting (Next.js)

## Support

For issues and questions, please contact support@weddingplatform.com

## License

Proprietary - All rights reserved
