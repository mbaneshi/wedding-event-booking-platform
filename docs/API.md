# API Documentation

## Base URL

```
Production: https://api.wedding-platform.com
Development: http://localhost:8000/api
```

## Authentication

All authenticated endpoints require a Bearer token:

```
Authorization: Bearer {token}
```

### Obtain Token

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbG...",
  "user": {
    "id": "123",
    "name": "John Doe",
    "email": "user@example.com",
    "role": "customer"
  }
}
```

## Vendors

### Search Vendors

```http
GET /api/vendors/search?category=photographers&city=New York&priceMin=1000&priceMax=5000&minRating=4&sortBy=rating
```

Query Parameters:
- `query` (string): Search term
- `category` (string): Category slug
- `city` (string): City name
- `priceMin` (number): Minimum price
- `priceMax` (number): Maximum price
- `minRating` (number): Minimum rating (1-5)
- `sortBy` (string): `rating`, `price`, `popularity`
- `page` (number): Page number

Response:
```json
{
  "data": [
    {
      "id": "vendor-123",
      "businessName": "Elite Photography",
      "description": "Professional wedding photography",
      "category": "Photographers",
      "city": "New York",
      "country": "USA",
      "ratingAverage": 4.8,
      "ratingCount": 125,
      "verified": true,
      "services": [...]
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 20
  }
}
```

### Get Vendor Details

```http
GET /api/vendors/{id}
```

Response:
```json
{
  "id": "vendor-123",
  "businessName": "Elite Photography",
  "description": "Professional wedding photography services",
  "email": "info@elitephoto.com",
  "phone": "+1234567890",
  "category": {
    "id": "cat-1",
    "name": "Photographers"
  },
  "city": "New York",
  "country": "USA",
  "address": "123 Main St",
  "ratingAverage": 4.8,
  "ratingCount": 125,
  "verified": true,
  "featured": false,
  "services": [...],
  "media": [...],
  "availability": [...]
}
```

### Create Vendor (Authenticated)

```http
POST /api/vendors
Authorization: Bearer {token}
Content-Type: application/json

{
  "business_name": "Dream Events",
  "category_id": "cat-1",
  "description": "Full-service wedding planning",
  "email": "info@dreamevents.com",
  "phone": "+1234567890",
  "city": "New York",
  "country": "USA",
  "address": "123 Main St"
}
```

## Bookings

### Create Booking (Authenticated)

```http
POST /api/bookings
Authorization: Bearer {token}
Content-Type: application/json

{
  "vendor_id": "vendor-123",
  "service_id": "service-456",
  "event_date": "2024-12-25",
  "event_time": "18:00",
  "guest_count": 100,
  "event_type": "wedding",
  "special_requests": "Outdoor ceremony preferred"
}
```

Response:
```json
{
  "data": {
    "id": "booking-789",
    "booking_number": "BK-2024-000123",
    "status": "pending",
    "customer_id": "user-1",
    "vendor_id": "vendor-123",
    "service_id": "service-456",
    "event_date": "2024-12-25",
    "total_price": 5000,
    "currency": "USD",
    "deposit_amount": 1500,
    "balance_amount": 3500,
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

### Get Bookings (Authenticated)

```http
GET /api/bookings?status=pending
Authorization: Bearer {token}
```

Query Parameters:
- `status` (string): `pending`, `confirmed`, `completed`, `cancelled`

### Confirm Booking (Vendor only)

```http
PUT /api/bookings/{id}/confirm
Authorization: Bearer {token}
```

### Cancel Booking (Authenticated)

```http
PUT /api/bookings/{id}/cancel
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Change of plans"
}
```

## Payments

### Create Payment Intent

```http
POST /api/payments/intent
Authorization: Bearer {token}
Content-Type: application/json

{
  "booking_id": "booking-789",
  "amount": 1500,
  "type": "deposit"
}
```

Response:
```json
{
  "client_secret": "pi_xxx_secret_yyy",
  "payment_intent_id": "pi_1234567890"
}
```

### Confirm Payment

```http
POST /api/payments/confirm
Authorization: Bearer {token}
Content-Type: application/json

{
  "booking_id": "booking-789",
  "payment_intent_id": "pi_1234567890",
  "type": "deposit"
}
```

## Reviews

### Create Review (Authenticated)

```http
POST /api/reviews
Authorization: Bearer {token}
Content-Type: application/json

{
  "booking_id": "booking-789",
  "vendor_id": "vendor-123",
  "rating": 5,
  "comment": "Excellent service!"
}
```

### Get Vendor Reviews

```http
GET /api/vendors/{id}/reviews?page=1
```

## Categories

### List Categories

```http
GET /api/categories
```

Response:
```json
{
  "data": [
    {
      "id": "cat-1",
      "name": "Photographers",
      "slug": "photographers",
      "description": "Professional photography services",
      "vendor_count": 150
    }
  ]
}
```

## Error Responses

### 400 Bad Request
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 401 Unauthorized
```json
{
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "message": "You do not have permission to perform this action"
}
```

### 404 Not Found
```json
{
  "message": "Resource not found"
}
```

### 429 Too Many Requests
```json
{
  "message": "Too many requests. Please try again later.",
  "retry_after": 60
}
```

### 500 Internal Server Error
```json
{
  "message": "An error occurred. Please try again later."
}
```

## Rate Limiting

- **General endpoints**: 60 requests per minute
- **Search endpoints**: 120 requests per minute
- **Authentication endpoints**: 10 requests per minute

Rate limit headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
```

## Pagination

List endpoints support pagination:

```http
GET /api/vendors?page=2&per_page=20
```

Response includes metadata:
```json
{
  "data": [...],
  "meta": {
    "current_page": 2,
    "from": 21,
    "to": 40,
    "total": 150,
    "last_page": 8,
    "per_page": 20
  },
  "links": {
    "first": "https://api.wedding-platform.com/vendors?page=1",
    "last": "https://api.wedding-platform.com/vendors?page=8",
    "prev": "https://api.wedding-platform.com/vendors?page=1",
    "next": "https://api.wedding-platform.com/vendors?page=3"
  }
}
```

## Webhooks

### Stripe Payment Webhook

```http
POST /api/webhooks/stripe
Content-Type: application/json
Stripe-Signature: {signature}

{
  "type": "payment_intent.succeeded",
  "data": {
    "object": {...}
  }
}
```

## OpenAPI Specification

Full OpenAPI 3.0 specification available at:
```
https://api.wedding-platform.com/api-docs
```

## SDKs

Official SDKs:
- JavaScript/TypeScript: `npm install @wedding-platform/sdk`
- PHP: `composer require wedding-platform/sdk`

## Support

For API support:
- Email: api-support@wedding-platform.com
- Documentation: https://docs.wedding-platform.com
- Status: https://status.wedding-platform.com
