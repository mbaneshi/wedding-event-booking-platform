# Wedding & Event Booking Platform - Technical Requirements

## 1. TECHNICAL SPECIFICATIONS

### 1.1 Technology Stack (Recommended)

**Frontend:**
- **Framework:** Next.js 14.x with React 18
- **Language:** TypeScript 5.x
- **Styling:** Tailwind CSS 3.x
- **UI Components:** shadcn/ui or Material-UI
- **State Management:** Zustand or React Context
- **Forms:** React Hook Form + Zod validation
- **Maps:** Google Maps JavaScript API

**Backend:**
- **Option A (Recommended):** Laravel 10.x (PHP 8.2+)
- **Option B:** Node.js 20.x with NestJS 10.x
- **Database:** PostgreSQL 16.x or MySQL 8.x
- **ORM:** Eloquent (Laravel) or Prisma (Node.js)
- **Cache:** Redis 7.x
- **Search:** (Optional) Meilisearch or Elasticsearch

**Payment Processing:**
- Stripe API (primary)
- PayPal integration (secondary)

**Storage:**
- AWS S3 or Cloudinary for images/videos
- CDN: CloudFlare

**Infrastructure:**
- Docker & Docker Compose
- CI/CD: GitHub Actions
- Hosting: AWS, DigitalOcean, or Vercel (frontend)

### 1.2 Project Structure (Next.js + Laravel)

**Frontend (Next.js):**
```
app/
├── (public)/
│   ├── page.tsx                 # Homepage
│   ├── search/
│   │   └── page.tsx              # Search results
│   ├── vendors/
│   │   └── [id]/
│   │       └── page.tsx          # Vendor profile
│   ├── booking/
│   │   └── [vendorId]/
│   │       └── page.tsx          # Booking flow
│   └── about/
│       └── page.tsx              # About page
├── (dashboard)/
│   ├── customer/
│   │   ├── bookings/
│   │   ├── favorites/
│   │   └── profile/
│   ├── vendor/
│   │   ├── dashboard/
│   │   ├── listings/
│   │   ├── bookings/
│   │   ├── calendar/
│   │   └── analytics/
│   └── admin/
│       ├── vendors/
│       ├── bookings/
│       ├── payments/
│       └── analytics/
├── api/
│   └── (backend proxy routes)
├── components/
│   ├── search/
│   ├── vendor/
│   ├── booking/
│   └── ui/
└── lib/
    ├── api.ts
    ├── auth.ts
    └── utils.ts
```

**Backend (Laravel):**
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── VendorController.php
│   │   ├── BookingController.php
│   │   ├── PaymentController.php
│   │   └── AdminController.php
│   └── Middleware/
│       ├── EnsureVendorVerified.php
│       └── CheckAdminRole.php
├── Models/
│   ├── User.php
│   ├── Vendor.php
│   ├── Service.php
│   ├── Booking.php
│   ├── Payment.php
│   ├── Review.php
│   └── Category.php
├── Services/
│   ├── BookingService.php
│   ├── PaymentService.php
│   ├── SearchService.php
│   └── NotificationService.php
└── Jobs/
    ├── ProcessPayment.php
    └── SendBookingReminder.php

database/
├── migrations/
└── seeders/
```

### 1.3 Database Schema (PostgreSQL)

```sql
-- Users (customers, vendors, admins)
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  phone VARCHAR(20),
  role VARCHAR(20) NOT NULL, -- customer, vendor, admin
  email_verified_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories
CREATE TABLE categories (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) UNIQUE NOT NULL,
  icon VARCHAR(255), -- Icon URL or name
  parent_id UUID REFERENCES categories(id), -- For subcategories
  sort_order INTEGER DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vendors
CREATE TABLE vendors (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  business_name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  description TEXT,
  logo_url VARCHAR(255),
  cover_image_url VARCHAR(255),
  category_id UUID REFERENCES categories(id),

  -- Contact Info
  phone VARCHAR(20),
  email VARCHAR(255),
  website VARCHAR(255),

  -- Location
  address TEXT,
  city VARCHAR(100),
  region VARCHAR(100),
  country VARCHAR(100) DEFAULT 'Montenegro',
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),

  -- Business Info
  years_in_business INTEGER,
  license_number VARCHAR(100),

  -- Status
  status VARCHAR(50) DEFAULT 'pending', -- pending, approved, suspended, rejected
  verified BOOLEAN DEFAULT false,
  featured BOOLEAN DEFAULT false,

  -- Ratings
  rating_average DECIMAL(3, 2) DEFAULT 0.00,
  rating_count INTEGER DEFAULT 0,

  -- Metadata
  view_count INTEGER DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services (vendor offerings)
CREATE TABLE services (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  vendor_id UUID REFERENCES vendors(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  price_from DECIMAL(10, 2),
  price_to DECIMAL(10, 2),
  currency VARCHAR(10) DEFAULT 'EUR',
  pricing_type VARCHAR(50), -- fixed, hourly, per_person, package
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Media (photos/videos)
CREATE TABLE media (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  vendor_id UUID REFERENCES vendors(id) ON DELETE CASCADE,
  type VARCHAR(20) NOT NULL, -- photo, video
  url VARCHAR(255) NOT NULL,
  thumbnail_url VARCHAR(255),
  caption TEXT,
  sort_order INTEGER DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings
CREATE TABLE bookings (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_number VARCHAR(20) UNIQUE NOT NULL, -- e.g., BK-2025-001234
  customer_id UUID REFERENCES users(id),
  vendor_id UUID REFERENCES vendors(id),
  service_id UUID REFERENCES services(id),

  -- Event Details
  event_date DATE NOT NULL,
  event_time TIME,
  event_type VARCHAR(100), -- wedding, corporate, birthday, etc.
  guest_count INTEGER,
  venue_name VARCHAR(255),
  venue_address TEXT,

  -- Pricing
  service_price DECIMAL(10, 2) NOT NULL,
  extras_price DECIMAL(10, 2) DEFAULT 0.00,
  total_price DECIMAL(10, 2) NOT NULL,
  currency VARCHAR(10) DEFAULT 'EUR',
  commission_rate DECIMAL(5, 2) DEFAULT 10.00, -- Platform commission %
  commission_amount DECIMAL(10, 2),

  -- Payment
  deposit_amount DECIMAL(10, 2), -- If deposit required
  deposit_paid BOOLEAN DEFAULT false,
  deposit_paid_at TIMESTAMP,
  balance_amount DECIMAL(10, 2),
  balance_paid BOOLEAN DEFAULT false,
  balance_paid_at TIMESTAMP,

  -- Status
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  -- pending, confirmed, completed, cancelled, refunded

  -- Additional Info
  special_requests TEXT,
  cancellation_reason TEXT,
  cancelled_by UUID REFERENCES users(id),
  cancelled_at TIMESTAMP,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payments
CREATE TABLE payments (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_id UUID REFERENCES bookings(id),
  stripe_payment_intent_id VARCHAR(255),
  amount DECIMAL(10, 2) NOT NULL,
  currency VARCHAR(10) DEFAULT 'EUR',
  payment_method VARCHAR(50), -- card, paypal, bank_transfer
  status VARCHAR(50) NOT NULL, -- pending, succeeded, failed, refunded
  payment_type VARCHAR(50), -- deposit, balance, full

  -- Refund Info
  refunded BOOLEAN DEFAULT false,
  refund_amount DECIMAL(10, 2),
  refund_reason TEXT,
  refunded_at TIMESTAMP,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reviews
CREATE TABLE reviews (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_id UUID REFERENCES bookings(id),
  customer_id UUID REFERENCES users(id),
  vendor_id UUID REFERENCES vendors(id),
  rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
  title VARCHAR(255),
  comment TEXT,
  response TEXT, -- Vendor response
  response_at TIMESTAMP,
  status VARCHAR(50) DEFAULT 'published', -- published, hidden, flagged
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Availability (vendor calendar)
CREATE TABLE availability (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  vendor_id UUID REFERENCES vendors(id) ON DELETE CASCADE,
  date DATE NOT NULL,
  is_available BOOLEAN DEFAULT true,
  reason VARCHAR(255), -- If unavailable
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE(vendor_id, date)
);

-- Messages (customer-vendor communication)
CREATE TABLE messages (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_id UUID REFERENCES bookings(id),
  sender_id UUID REFERENCES users(id),
  receiver_id UUID REFERENCES users(id),
  message TEXT NOT NULL,
  read BOOLEAN DEFAULT false,
  read_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Favorites/Wishlist
CREATE TABLE favorites (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  vendor_id UUID REFERENCES vendors(id) ON DELETE CASCADE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE(user_id, vendor_id)
);

-- Indexes for performance
CREATE INDEX idx_vendors_category ON vendors(category_id);
CREATE INDEX idx_vendors_city ON vendors(city);
CREATE INDEX idx_vendors_status ON vendors(status);
CREATE INDEX idx_vendors_location ON vendors USING GIST (point(latitude, longitude));
CREATE INDEX idx_bookings_customer ON bookings(customer_id);
CREATE INDEX idx_bookings_vendor ON bookings(vendor_id);
CREATE INDEX idx_bookings_date ON bookings(event_date);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_reviews_vendor ON reviews(vendor_id);
CREATE INDEX idx_availability_vendor_date ON availability(vendor_id, date);
```

## 2. FUNCTIONAL REQUIREMENTS

### FR-001: Vendor Search & Filter

**User Story:** As a customer, I want to search and filter vendors to find the perfect fit for my event.

**Acceptance Criteria:**
- Search by vendor category (photographer, venue, florist, etc.)
- Filter by location (city, region, radius)
- Filter by price range
- Filter by availability (specific date)
- Filter by rating (minimum stars)
- Sort results (relevance, price, rating, popularity)
- Display 20 results per page with pagination

**Implementation:**
```php
// app/Services/SearchService.php
class SearchService {
    public function search(SearchRequest $request): LengthAwarePaginator
    {
        $query = Vendor::query()
            ->where('status', 'approved')
            ->where('is_active', true);

        // Category filter
        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        // Location filter
        if ($request->city) {
            $query->where('city', $request->city);
        }

        // Price range filter
        if ($request->price_min || $request->price_max) {
            $query->whereHas('services', function($q) use ($request) {
                if ($request->price_min) {
                    $q->where('price_from', '>=', $request->price_min);
                }
                if ($request->price_max) {
                    $q->where('price_to', '<=', $request->price_max);
                }
            });
        }

        // Availability filter
        if ($request->date) {
            $query->whereDoesntHave('availability', function($q) use ($request) {
                $q->where('date', $request->date)
                  ->where('is_available', false);
            });
        }

        // Rating filter
        if ($request->min_rating) {
            $query->where('rating_average', '>=', $request->min_rating);
        }

        // Sorting
        $query->orderBy($request->sort_by ?? 'rating_average', 'desc');

        return $query->with(['category', 'services', 'media'])
            ->paginate(20);
    }
}
```

### FR-002: Vendor Profile Display

**User Story:** As a customer, I want to view detailed vendor information.

**Acceptance Criteria:**
- Display business name, logo, cover image
- Show description and years in business
- Display photo/video gallery
- Show pricing information and packages
- Display ratings and reviews
- Show contact information
- Display location on map
- Show availability calendar
- "Request Quote" and "Book Now" buttons

### FR-003: Booking Flow

**User Story:** As a customer, I want to book a vendor easily.

**Acceptance Criteria:**
- Select service/package
- Choose event date
- Enter event details (type, guest count, venue)
- Add special requests
- Review booking summary
- Secure payment processing
- Receive booking confirmation email
- Vendor receives booking notification

**Implementation:**
```php
// app/Http/Controllers/BookingController.php
class BookingController extends Controller {
    public function store(CreateBookingRequest $request)
    {
        DB::beginTransaction();

        try {
            // 1. Check vendor availability
            $isAvailable = $this->checkAvailability(
                $request->vendor_id,
                $request->event_date
            );

            if (!$isAvailable) {
                return response()->json([
                    'error' => 'Vendor not available on selected date'
                ], 422);
            }

            // 2. Calculate pricing
            $service = Service::findOrFail($request->service_id);
            $total = $this->calculateTotal($service, $request->extras);
            $commission = $total * 0.10; // 10% platform fee

            // 3. Create booking
            $booking = Booking::create([
                'booking_number' => $this->generateBookingNumber(),
                'customer_id' => auth()->id(),
                'vendor_id' => $request->vendor_id,
                'service_id' => $request->service_id,
                'event_date' => $request->event_date,
                'event_type' => $request->event_type,
                'guest_count' => $request->guest_count,
                'total_price' => $total,
                'commission_rate' => 10.00,
                'commission_amount' => $commission,
                'deposit_amount' => $total * 0.30, // 30% deposit
                'balance_amount' => $total * 0.70,
                'status' => 'pending',
            ]);

            // 4. Process payment
            $payment = $this->processPayment($booking, $request->payment_method);

            if ($payment->status !== 'succeeded') {
                throw new Exception('Payment failed');
            }

            // 5. Update booking and availability
            $booking->update([
                'status' => 'confirmed',
                'deposit_paid' => true,
                'deposit_paid_at' => now(),
            ]);

            $this->markDateUnavailable($request->vendor_id, $request->event_date);

            // 6. Send notifications
            $this->sendBookingConfirmation($booking);
            $this->notifyVendor($booking);

            DB::commit();

            return response()->json([
                'booking' => $booking,
                'message' => 'Booking confirmed successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generateBookingNumber(): string
    {
        $year = date('Y');
        $count = Booking::whereYear('created_at', $year)->count() + 1;
        return sprintf('BK-%s-%06d', $year, $count);
    }
}
```

### FR-004: Payment Processing (Stripe)

**User Story:** As a customer, I want secure payment processing.

**Acceptance Criteria:**
- Stripe payment integration
- Support credit/debit cards
- Support deposit + balance payment
- Automatic receipt generation
- Refund capability
- Secure payment data handling (PCI-DSS)

**Implementation:**
```php
// app/Services/PaymentService.php
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentService {
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPaymentIntent(Booking $booking, float $amount): PaymentIntent
    {
        $paymentIntent = PaymentIntent::create([
            'amount' => $amount * 100, // Convert to cents
            'currency' => strtolower($booking->currency),
            'metadata' => [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
            ],
            'description' => "Booking #{$booking->booking_number}",
        ]);

        return $paymentIntent;
    }

    public function confirmPayment(string $paymentIntentId): Payment
    {
        $intent = PaymentIntent::retrieve($paymentIntentId);

        $payment = Payment::create([
            'booking_id' => $intent->metadata->booking_id,
            'stripe_payment_intent_id' => $intent->id,
            'amount' => $intent->amount / 100,
            'currency' => strtoupper($intent->currency),
            'payment_method' => 'card',
            'status' => $intent->status,
        ]);

        return $payment;
    }

    public function refund(Payment $payment, float $amount, string $reason): void
    {
        $refund = \Stripe\Refund::create([
            'payment_intent' => $payment->stripe_payment_intent_id,
            'amount' => $amount * 100,
            'reason' => 'requested_by_customer',
        ]);

        $payment->update([
            'refunded' => true,
            'refund_amount' => $amount,
            'refund_reason' => $reason,
            'refunded_at' => now(),
        ]);
    }
}
```

### FR-005: Vendor Dashboard

**User Story:** As a vendor, I want to manage my business on the platform.

**Acceptance Criteria:**
- View upcoming bookings calendar
- Manage service listings
- Update availability
- View earnings and revenue
- Respond to reviews
- Manage media gallery
- View analytics (views, conversion rate)

**Implementation:**
```tsx
// app/(dashboard)/vendor/dashboard/page.tsx
export default function VendorDashboard() {
  const { bookings, revenue, reviews } = useVendorData();

  return (
    <div className="grid gap-6">
      {/* Stats Cards */}
      <div className="grid grid-cols-4 gap-4">
        <StatsCard title="Upcoming Bookings" value={bookings.upcoming} />
        <StatsCard title="This Month Revenue" value={`€${revenue.thisMonth}`} />
        <StatsCard title="Average Rating" value={reviews.average} />
        <StatsCard title="Profile Views" value={analytics.views} />
      </div>

      {/* Upcoming Bookings */}
      <Card>
        <CardHeader>
          <CardTitle>Upcoming Bookings</CardTitle>
        </CardHeader>
        <CardContent>
          <BookingsTable bookings={bookings.data} />
        </CardContent>
      </Card>

      {/* Revenue Chart */}
      <Card>
        <CardHeader>
          <CardTitle>Revenue (Last 6 Months)</CardTitle>
        </CardHeader>
        <CardContent>
          <RevenueChart data={revenue.chartData} />
        </CardContent>
      </Card>
    </div>
  );
}
```

### FR-006: Admin Dashboard

**User Story:** As admin, I want to manage the entire platform.

**Acceptance Criteria:**
- View all vendors (pending, approved, suspended)
- Approve/reject vendor applications
- View all bookings
- Manage payments and commissions
- View platform analytics (GMV, commissions, users)
- Manage categories
- View and moderate reviews

### FR-007: Reviews & Ratings

**User Story:** As a customer, I want to leave reviews after my event.

**Acceptance Criteria:**
- Leave review only after event completion
- 1-5 star rating
- Written review (optional)
- Vendor can respond to reviews
- Reviews display on vendor profile
- Average rating calculated automatically

## 3. IMPLEMENTATION GUIDE

### 3.1 MVP Feature Prioritization

**Phase 1 (MVP - £7,000-£9,000):**
- User registration/authentication
- Vendor profiles (basic)
- Service listings
- Search and filter (basic)
- Inquiry system (no payment yet)
- Basic vendor dashboard
- Basic admin panel
- 5-6 core categories

**Phase 2 (£3,000):**
- Stripe payment integration
- Booking with payments
- Email notifications
- Reviews and ratings
- Advanced search (Meilisearch)

**Phase 3 (£2,000):**
- Messaging system
- Advanced analytics
- Mobile responsiveness improvements
- SEO optimization

### 3.2 Stripe Integration Setup

```php
// config/services.php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

```php
// routes/api.php
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
```

```php
// app/Http/Controllers/StripeWebhookController.php
class StripeWebhookController extends Controller {
    public function handle(Request $request)
    {
        $signature = $request->header('Stripe-Signature');
        $payload = $request->getContent();

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSuccess($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailure($event->data->object);
                    break;
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

## 4. TESTING REQUIREMENTS

### 4.1 Unit Tests
- Service layer: 80%+ coverage
- Payment processing
- Booking logic
- Search/filter logic

### 4.2 Integration Tests
- Complete booking flow (search → book → pay)
- Payment processing (Stripe)
- Email notifications
- Vendor approval workflow

### 4.3 Manual Testing Checklist
- [ ] User registration and login
- [ ] Vendor search with all filters
- [ ] View vendor profile
- [ ] Create booking
- [ ] Payment processing (test cards)
- [ ] Email notifications sent
- [ ] Vendor dashboard functional
- [ ] Admin dashboard functional
- [ ] Review submission
- [ ] Mobile responsiveness

## 5. DEPLOYMENT

### 5.1 Environment Variables

```bash
# .env
APP_NAME="Wedding Marketplace"
APP_ENV=production
APP_URL=https://yourplatform.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=wedding_platform
DB_USERNAME=user
DB_PASSWORD=password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

AWS_S3_BUCKET=your-bucket
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx

GOOGLE_MAPS_API_KEY=xxx

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxx
MAIL_PASSWORD=xxx
```

### 5.2 Deployment Checklist

- [ ] Domain purchased and DNS configured
- [ ] SSL certificate installed (Let's Encrypt)
- [ ] Database created and migrated
- [ ] Environment variables set
- [ ] Stripe account setup (live mode)
- [ ] Google Maps API enabled
- [ ] Email service configured (SendGrid/Mailgun)
- [ ] Storage bucket created (S3/Cloudinary)
- [ ] CDN configured (CloudFlare)
- [ ] Backup strategy implemented
- [ ] Monitoring setup (Sentry, LogRocket)
- [ ] Analytics setup (Google Analytics)
- [ ] Terms of Service and Privacy Policy published
- [ ] Customer support email configured

## 6. SUCCESS METRICS

- [ ] 20+ vendors onboarded (Montenegro)
- [ ] 50+ customer registrations
- [ ] 10+ bookings in first month
- [ ] <3 second page load time
- [ ] >95% uptime
- [ ] Payment success rate >98%
- [ ] Mobile traffic >50%
- [ ] Vendor satisfaction score >4/5

## 7. TIMELINE (16 weeks)

**Weeks 1-2:** Planning, design, database schema
**Weeks 3-4:** Authentication, user management
**Weeks 5-6:** Vendor profiles, listings
**Weeks 7-8:** Search, filter, vendor discovery
**Weeks 9-10:** Booking system (inquiry-based)
**Weeks 11-12:** Payment integration (Stripe)
**Weeks 13-14:** Vendor & admin dashboards
**Weeks 15:** Testing, bug fixes
**Week 16:** Deployment, launch

## 8. BUDGET REALITY CHECK

**Development:** £7,000-£11,700 (actual estimate)
**To fit £5,000-£10,000 budget:** Focus on Phase 1 MVP only

**Ongoing Costs:**
- Hosting: £30-100/month
- Domain: £10-20/year
- Stripe fees: 2.9% + £0.20 per transaction
- Email service: £10-50/month
- Storage: £10-30/month
- Google Maps API: £0-100/month

This platform is achievable within budget if focusing on MVP features first, with Montenegro as initial market, then expanding based on traction.
