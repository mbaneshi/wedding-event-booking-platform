-- Vendors Table
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
  status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'suspended', 'rejected')),
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

CREATE INDEX idx_vendors_user ON vendors(user_id);
CREATE INDEX idx_vendors_category ON vendors(category_id);
CREATE INDEX idx_vendors_city ON vendors(city);
CREATE INDEX idx_vendors_status ON vendors(status);
CREATE INDEX idx_vendors_slug ON vendors(slug);
CREATE INDEX idx_vendors_location ON vendors USING GIST (point(latitude, longitude));
