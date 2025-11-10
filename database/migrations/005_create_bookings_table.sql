-- Bookings Table
CREATE TABLE bookings (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_number VARCHAR(20) UNIQUE NOT NULL,
  customer_id UUID REFERENCES users(id),
  vendor_id UUID REFERENCES vendors(id),
  service_id UUID REFERENCES services(id),

  -- Event Details
  event_date DATE NOT NULL,
  event_time TIME,
  event_type VARCHAR(100),
  guest_count INTEGER,
  venue_name VARCHAR(255),
  venue_address TEXT,

  -- Pricing
  service_price DECIMAL(10, 2) NOT NULL,
  extras_price DECIMAL(10, 2) DEFAULT 0.00,
  total_price DECIMAL(10, 2) NOT NULL,
  currency VARCHAR(10) DEFAULT 'EUR',
  commission_rate DECIMAL(5, 2) DEFAULT 10.00,
  commission_amount DECIMAL(10, 2),

  -- Payment
  deposit_amount DECIMAL(10, 2),
  deposit_paid BOOLEAN DEFAULT false,
  deposit_paid_at TIMESTAMP,
  balance_amount DECIMAL(10, 2),
  balance_paid BOOLEAN DEFAULT false,
  balance_paid_at TIMESTAMP,

  -- Status
  status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'completed', 'cancelled', 'refunded')),

  -- Additional Info
  special_requests TEXT,
  cancellation_reason TEXT,
  cancelled_by UUID REFERENCES users(id),
  cancelled_at TIMESTAMP,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_bookings_customer ON bookings(customer_id);
CREATE INDEX idx_bookings_vendor ON bookings(vendor_id);
CREATE INDEX idx_bookings_service ON bookings(service_id);
CREATE INDEX idx_bookings_date ON bookings(event_date);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_number ON bookings(booking_number);
