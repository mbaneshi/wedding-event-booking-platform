-- Services Table
CREATE TABLE services (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  vendor_id UUID REFERENCES vendors(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  price_from DECIMAL(10, 2),
  price_to DECIMAL(10, 2),
  currency VARCHAR(10) DEFAULT 'EUR',
  pricing_type VARCHAR(50) CHECK (pricing_type IN ('fixed', 'hourly', 'per_person', 'package')),
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_services_vendor ON services(vendor_id);
CREATE INDEX idx_services_active ON services(is_active);
