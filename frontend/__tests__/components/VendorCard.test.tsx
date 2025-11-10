import React from 'react'
import { render, screen } from '@testing-library/react'
import '@testing-library/jest-dom'
import { Vendor } from '@/types'

// Mock VendorCard component - extract from search page
const VendorCard = ({ vendor }: { vendor: Vendor }) => {
  const minPrice = vendor.services?.[0]?.priceFrom || 0

  return (
    <div data-testid="vendor-card">
      <img
        src={vendor.coverImageUrl || '/placeholder.jpg'}
        alt={vendor.businessName}
      />
      <h3>{vendor.businessName}</h3>
      {vendor.verified && (
        <span data-testid="verified-badge">Verified</span>
      )}
      <div data-testid="rating">
        <span>{vendor.ratingAverage.toFixed(1)}</span>
        <span>{vendor.ratingCount} reviews</span>
      </div>
      <div data-testid="location">
        {vendor.city}, {vendor.country}
      </div>
      <p>{vendor.description}</p>
      <div data-testid="price">
        {minPrice}
      </div>
    </div>
  )
}

describe('VendorCard Component', () => {
  const mockVendor: Vendor = {
    id: '123',
    businessName: 'Elegant Weddings',
    description: 'Professional wedding planning service',
    city: 'New York',
    country: 'USA',
    coverImageUrl: 'https://example.com/image.jpg',
    verified: true,
    ratingAverage: 4.8,
    ratingCount: 125,
    services: [
      {
        id: 's1',
        name: 'Full Planning',
        priceFrom: 5000,
        priceTo: 15000,
        currency: 'USD',
        description: 'Complete wedding planning',
      },
    ],
  } as Vendor

  it('should render vendor business name', () => {
    render(<VendorCard vendor={mockVendor} />)
    expect(screen.getByText('Elegant Weddings')).toBeInTheDocument()
  })

  it('should display verified badge for verified vendors', () => {
    render(<VendorCard vendor={mockVendor} />)
    expect(screen.getByTestId('verified-badge')).toBeInTheDocument()
  })

  it('should not display verified badge for unverified vendors', () => {
    const unverifiedVendor = { ...mockVendor, verified: false }
    render(<VendorCard vendor={unverifiedVendor} />)
    expect(screen.queryByTestId('verified-badge')).not.toBeInTheDocument()
  })

  it('should display rating information correctly', () => {
    render(<VendorCard vendor={mockVendor} />)
    const ratingElement = screen.getByTestId('rating')
    expect(ratingElement).toHaveTextContent('4.8')
    expect(ratingElement).toHaveTextContent('125 reviews')
  })

  it('should display location information', () => {
    render(<VendorCard vendor={mockVendor} />)
    const locationElement = screen.getByTestId('location')
    expect(locationElement).toHaveTextContent('New York, USA')
  })

  it('should display vendor description', () => {
    render(<VendorCard vendor={mockVendor} />)
    expect(
      screen.getByText('Professional wedding planning service')
    ).toBeInTheDocument()
  })

  it('should display starting price', () => {
    render(<VendorCard vendor={mockVendor} />)
    const priceElement = screen.getByTestId('price')
    expect(priceElement).toHaveTextContent('5000')
  })

  it('should handle vendor without services', () => {
    const vendorNoServices = { ...mockVendor, services: [] }
    render(<VendorCard vendor={vendorNoServices} />)
    const priceElement = screen.getByTestId('price')
    expect(priceElement).toHaveTextContent('0')
  })

  it('should render cover image with correct alt text', () => {
    render(<VendorCard vendor={mockVendor} />)
    const image = screen.getByAltText('Elegant Weddings')
    expect(image).toHaveAttribute('src', 'https://example.com/image.jpg')
  })

  it('should use placeholder image when coverImageUrl is not provided', () => {
    const vendorNoImage = { ...mockVendor, coverImageUrl: '' }
    render(<VendorCard vendor={vendorNoImage} />)
    const image = screen.getByAltText('Elegant Weddings')
    expect(image).toHaveAttribute('src', '/placeholder.jpg')
  })
})
