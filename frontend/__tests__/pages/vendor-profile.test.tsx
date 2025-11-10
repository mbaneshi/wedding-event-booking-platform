import React from 'react'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import * as api from '@/lib/api'

// Mock the API module
jest.mock('@/lib/api', () => ({
  vendorApi: {
    getById: jest.fn(),
  },
  reviewApi: {
    getByVendor: jest.fn(),
  },
}))

const VendorProfilePage = ({ vendorId }: { vendorId: string }) => {
  const [vendor, setVendor] = React.useState<any>(null)
  const [reviews, setReviews] = React.useState<any[]>([])
  const [loading, setLoading] = React.useState(true)
  const [selectedService, setSelectedService] = React.useState<string | null>(
    null
  )
  const [showBookingModal, setShowBookingModal] = React.useState(false)

  React.useEffect(() => {
    loadVendorData()
  }, [vendorId])

  const loadVendorData = async () => {
    try {
      setLoading(true)
      const [vendorData, reviewsData] = await Promise.all([
        (api as any).vendorApi.getById(vendorId),
        (api as any).reviewApi.getByVendor(vendorId),
      ])
      setVendor(vendorData)
      setReviews(reviewsData.data)
    } catch (error) {
      console.error('Failed to load vendor:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleBookService = (serviceId: string) => {
    setSelectedService(serviceId)
    setShowBookingModal(true)
  }

  if (loading) {
    return <div data-testid="loading">Loading...</div>
  }

  if (!vendor) {
    return <div data-testid="not-found">Vendor not found</div>
  }

  return (
    <div data-testid="vendor-profile">
      <div data-testid="vendor-header">
        <h1>{vendor.businessName}</h1>
        {vendor.verified && (
          <span data-testid="verified-badge">Verified</span>
        )}
        <div data-testid="rating">
          <span>{vendor.ratingAverage} stars</span>
          <span>({vendor.ratingCount} reviews)</span>
        </div>
        <div data-testid="location">
          {vendor.city}, {vendor.country}
        </div>
      </div>

      <div data-testid="vendor-description">
        <p>{vendor.description}</p>
      </div>

      <div data-testid="services-section">
        <h2>Services</h2>
        {vendor.services && vendor.services.length > 0 ? (
          vendor.services.map((service: any) => (
            <div key={service.id} data-testid={`service-${service.id}`}>
              <h3>{service.name}</h3>
              <p>{service.description}</p>
              <div data-testid={`price-${service.id}`}>
                {service.priceFrom} - {service.priceTo} {service.currency}
              </div>
              <button
                onClick={() => handleBookService(service.id)}
                data-testid={`book-${service.id}`}
              >
                Book Now
              </button>
            </div>
          ))
        ) : (
          <div data-testid="no-services">No services available</div>
        )}
      </div>

      <div data-testid="reviews-section">
        <h2>Reviews ({reviews.length})</h2>
        {reviews.length > 0 ? (
          reviews.map((review) => (
            <div key={review.id} data-testid={`review-${review.id}`}>
              <div data-testid={`review-rating-${review.id}`}>
                {review.rating} stars
              </div>
              <p>{review.comment}</p>
              <div>{review.customerName}</div>
            </div>
          ))
        ) : (
          <div data-testid="no-reviews">No reviews yet</div>
        )}
      </div>

      {showBookingModal && (
        <div data-testid="booking-modal">
          <h3>Book Service</h3>
          <p>Selected Service: {selectedService}</p>
          <button
            onClick={() => setShowBookingModal(false)}
            data-testid="close-modal"
          >
            Close
          </button>
        </div>
      )}
    </div>
  )
}

describe('VendorProfilePage', () => {
  const mockVendor = {
    id: 'vendor-123',
    businessName: 'Elite Events',
    description: 'Premium event planning services',
    city: 'New York',
    country: 'USA',
    verified: true,
    ratingAverage: 4.8,
    ratingCount: 45,
    services: [
      {
        id: 'service-1',
        name: 'Full Planning',
        description: 'Complete wedding planning',
        priceFrom: 5000,
        priceTo: 15000,
        currency: 'USD',
      },
      {
        id: 'service-2',
        name: 'Day Coordination',
        description: 'Coordination on the wedding day',
        priceFrom: 2000,
        priceTo: 5000,
        currency: 'USD',
      },
    ],
  }

  const mockReviews = [
    {
      id: 'review-1',
      rating: 5,
      comment: 'Excellent service!',
      customerName: 'John Doe',
    },
    {
      id: 'review-2',
      rating: 4,
      comment: 'Great experience overall',
      customerName: 'Jane Smith',
    },
  ]

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('should show loading state initially', () => {
    ;(api.vendorApi.getById as jest.Mock).mockImplementation(
      () => new Promise(() => {})
    )

    render(<VendorProfilePage vendorId="vendor-123" />)

    expect(screen.getByTestId('loading')).toBeInTheDocument()
  })

  it('should load and display vendor information', async () => {
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendor)
    ;(api.reviewApi.getByVendor as jest.Mock).mockResolvedValue({
      data: mockReviews,
    })

    render(<VendorProfilePage vendorId="vendor-123" />)

    await waitFor(() => {
      expect(screen.getByText('Elite Events')).toBeInTheDocument()
    })

    expect(
      screen.getByText('Premium event planning services')
    ).toBeInTheDocument()
    expect(screen.getByText('New York, USA')).toBeInTheDocument()
    expect(screen.getByTestId('verified-badge')).toBeInTheDocument()
  })

  it('should display rating information correctly', async () => {
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendor)
    ;(api.reviewApi.getByVendor as jest.Mock).mockResolvedValue({
      data: mockReviews,
    })

    render(<VendorProfilePage vendorId="vendor-123" />)

    await waitFor(() => {
      expect(screen.getByTestId('rating')).toBeInTheDocument()
    })

    expect(screen.getByTestId('rating')).toHaveTextContent('4.8 stars')
    expect(screen.getByTestId('rating')).toHaveTextContent('(45 reviews)')
  })

  it('should display all services', async () => {
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendor)
    ;(api.reviewApi.getByVendor as jest.Mock).mockResolvedValue({
      data: mockReviews,
    })

    render(<VendorProfilePage vendorId="vendor-123" />)

    await waitFor(() => {
      expect(screen.getByText('Full Planning')).toBeInTheDocument()
    })

    expect(screen.getByText('Day Coordination')).toBeInTheDocument()
    expect(screen.getByTestId('service-service-1')).toBeInTheDocument()
    expect(screen.getByTestId('service-service-2')).toBeInTheDocument()
  })

  it('should display service pricing correctly', async () => {
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendor)
    ;(api.reviewApi.getByVendor as jest.Mock).mockResolvedValue({
      data: mockReviews,
    })

    render(<VendorProfilePage vendorId="vendor-123" />)

    await waitFor(() => {
      expect(screen.getByTestId('price-service-1')).toBeInTheDocument()
    })

    expect(screen.getByTestId('price-service-1')).toHaveTextContent(
      '5000 - 15000 USD'
    )
    expect(screen.getByTestId('price-service-2')).toHaveTextContent(
      '2000 - 5000 USD'
    )
  })

  it('should open booking modal when book button is clicked', async () => {
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendor)
    ;(api.reviewApi.getByVendor as jest.Mock).mockResolvedValue({
      data: mockReviews,
    })

    render(<VendorProfilePage vendorId="vendor-123" />)

    await waitFor(() => {
      expect(screen.getByTestId('book-service-1')).toBeInTheDocument()
    })

    const bookButton = screen.getByTestId('book-service-1')
    fireEvent.click(bookButton)

    expect(screen.getByTestId('booking-modal')).toBeInTheDocument()
    expect(screen.getByText('Selected Service: service-1')).toBeInTheDocument()
  })

  it('should close booking modal when close button is clicked', async () => {
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendor)
    ;(api.reviewApi.getByVendor as jest.Mock).mockResolvedValue({
      data: mockReviews,
    })

    render(<VendorProfilePage vendorId="vendor-123" />)

    await waitFor(() => {
      expect(screen.getByTestId('book-service-1')).toBeInTheDocument()
    })

    fireEvent.click(screen.getByTestId('book-service-1'))
    expect(screen.getByTestId('booking-modal')).toBeInTheDocument()

    fireEvent.click(screen.getByTestId('close-modal'))
    expect(screen.queryByTestId('booking-modal')).not.toBeInTheDocument()
  })

  it('should display all reviews', async () => {
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendor)
    ;(api.reviewApi.getByVendor as jest.Mock).mockResolvedValue({
      data: mockReviews,
    })

    render(<VendorProfilePage vendorId="vendor-123" />)

    await waitFor(() => {
      expect(screen.getByText('Reviews (2)')).toBeInTheDocument()
    })

    expect(screen.getByTestId('review-review-1')).toBeInTheDocument()
    expect(screen.getByTestId('review-review-2')).toBeInTheDocument()
    expect(screen.getByText('Excellent service!')).toBeInTheDocument()
    expect(screen.getByText('Great experience overall')).toBeInTheDocument()
  })

  it('should show no reviews message when there are no reviews', async () => {
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendor)
    ;(api.reviewApi.getByVendor as jest.Mock).mockResolvedValue({
      data: [],
    })

    render(<VendorProfilePage vendorId="vendor-123" />)

    await waitFor(() => {
      expect(screen.getByTestId('no-reviews')).toBeInTheDocument()
    })

    expect(screen.getByText('No reviews yet')).toBeInTheDocument()
  })

  it('should show no services message when vendor has no services', async () => {
    const vendorNoServices = { ...mockVendor, services: [] }
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(vendorNoServices)
    ;(api.reviewApi.getByVendor as jest.Mock).mockResolvedValue({
      data: mockReviews,
    })

    render(<VendorProfilePage vendorId="vendor-123" />)

    await waitFor(() => {
      expect(screen.getByTestId('no-services')).toBeInTheDocument()
    })
  })

  it('should show not found message when vendor does not exist', async () => {
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(null)
    ;(api.reviewApi.getByVendor as jest.Mock).mockResolvedValue({
      data: [],
    })

    render(<VendorProfilePage vendorId="vendor-123" />)

    await waitFor(() => {
      expect(screen.getByTestId('not-found')).toBeInTheDocument()
    })
  })

  it('should handle API errors gracefully', async () => {
    const consoleError = jest.spyOn(console, 'error').mockImplementation()
    ;(api.vendorApi.getById as jest.Mock).mockRejectedValue(
      new Error('API Error')
    )
    ;(api.reviewApi.getByVendor as jest.Mock).mockResolvedValue({
      data: [],
    })

    render(<VendorProfilePage vendorId="vendor-123" />)

    await waitFor(() => {
      expect(screen.getByTestId('not-found')).toBeInTheDocument()
    })

    expect(consoleError).toHaveBeenCalled()
    consoleError.mockRestore()
  })
})
