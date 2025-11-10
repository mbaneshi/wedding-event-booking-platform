import React from 'react'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import * as api from '@/lib/api'

// Mock the API module
jest.mock('@/lib/api', () => ({
  vendorApi: {
    search: jest.fn(),
    getById: jest.fn(),
  },
  bookingApi: {
    create: jest.fn(),
  },
  authApi: {
    getCurrentUser: jest.fn(),
  },
}))

// Mock booking flow integration
const BookingFlowIntegration = () => {
  const [step, setStep] = React.useState<
    'search' | 'vendor' | 'booking' | 'confirmation'
  >('search')
  const [selectedVendor, setSelectedVendor] = React.useState<any>(null)
  const [selectedService, setSelectedService] = React.useState<any>(null)
  const [booking, setBooking] = React.useState<any>(null)
  const [user, setUser] = React.useState<any>(null)
  const [vendors, setVendors] = React.useState<any[]>([])

  React.useEffect(() => {
    loadUser()
  }, [])

  const loadUser = async () => {
    try {
      const userData = await (api as any).authApi.getCurrentUser()
      setUser(userData)
    } catch (error) {
      console.error('Not authenticated')
    }
  }

  const searchVendors = async (query: string) => {
    const result = await (api as any).vendorApi.search({ query })
    setVendors(result.data)
  }

  const selectVendor = async (vendorId: string) => {
    const vendor = await (api as any).vendorApi.getById(vendorId)
    setSelectedVendor(vendor)
    setStep('vendor')
  }

  const selectService = (service: any) => {
    setSelectedService(service)
    setStep('booking')
  }

  const createBooking = async (bookingData: any) => {
    const newBooking = await (api as any).bookingApi.create(bookingData)
    setBooking(newBooking)
    setStep('confirmation')
  }

  if (!user) {
    return (
      <div data-testid="login-required">
        Please log in to make a booking
      </div>
    )
  }

  return (
    <div data-testid="booking-flow">
      {step === 'search' && (
        <div data-testid="search-step">
          <input
            data-testid="search-input"
            placeholder="Search vendors..."
            onBlur={(e) => searchVendors(e.target.value)}
          />
          <div data-testid="vendor-results">
            {vendors.map((vendor) => (
              <div key={vendor.id} data-testid={`vendor-result-${vendor.id}`}>
                <h3>{vendor.businessName}</h3>
                <button onClick={() => selectVendor(vendor.id)}>
                  View Details
                </button>
              </div>
            ))}
          </div>
        </div>
      )}

      {step === 'vendor' && selectedVendor && (
        <div data-testid="vendor-step">
          <h2>{selectedVendor.businessName}</h2>
          <div data-testid="services-list">
            {selectedVendor.services?.map((service: any) => (
              <div key={service.id} data-testid={`service-${service.id}`}>
                <h3>{service.name}</h3>
                <button onClick={() => selectService(service)}>
                  Book This Service
                </button>
              </div>
            ))}
          </div>
          <button onClick={() => setStep('search')}>Back to Search</button>
        </div>
      )}

      {step === 'booking' && selectedService && (
        <div data-testid="booking-step">
          <h2>Book: {selectedService.name}</h2>
          <form
            onSubmit={(e) => {
              e.preventDefault()
              const formData = new FormData(e.currentTarget)
              createBooking({
                serviceId: selectedService.id,
                vendorId: selectedVendor.id,
                eventDate: formData.get('eventDate'),
                guestCount: Number(formData.get('guestCount')),
                notes: formData.get('notes'),
              })
            }}
          >
            <input
              name="eventDate"
              type="date"
              required
              data-testid="event-date-input"
            />
            <input
              name="guestCount"
              type="number"
              required
              min="1"
              data-testid="guest-count-input"
            />
            <textarea name="notes" data-testid="notes-input" />
            <button type="submit" data-testid="submit-booking">
              Submit Booking
            </button>
            <button type="button" onClick={() => setStep('vendor')}>
              Back
            </button>
          </form>
        </div>
      )}

      {step === 'confirmation' && booking && (
        <div data-testid="confirmation-step">
          <h2>Booking Confirmed!</h2>
          <div data-testid="booking-number">
            Booking #{booking.bookingNumber}
          </div>
          <div data-testid="booking-status">Status: {booking.status}</div>
          <button onClick={() => setStep('search')}>
            Make Another Booking
          </button>
        </div>
      )}
    </div>
  )
}

describe('Booking Flow Integration', () => {
  const mockUser = {
    id: 'user-123',
    email: 'test@example.com',
    name: 'Test User',
  }

  const mockVendors = [
    {
      id: 'vendor-1',
      businessName: 'Dream Weddings',
      services: [
        {
          id: 'service-1',
          name: 'Full Planning',
          priceFrom: 5000,
        },
      ],
    },
    {
      id: 'vendor-2',
      businessName: 'Perfect Events',
      services: [
        {
          id: 'service-2',
          name: 'Day Coordination',
          priceFrom: 2000,
        },
      ],
    },
  ]

  const mockBooking = {
    id: 'booking-123',
    bookingNumber: 'BK-2024-001',
    status: 'pending',
    vendorId: 'vendor-1',
    serviceId: 'service-1',
  }

  beforeEach(() => {
    jest.clearAllMocks()
    ;(api.authApi.getCurrentUser as jest.Mock).mockResolvedValue(mockUser)
  })

  it('should require user authentication', async () => {
    ;(api.authApi.getCurrentUser as jest.Mock).mockRejectedValue(
      new Error('Not authenticated')
    )

    render(<BookingFlowIntegration />)

    await waitFor(() => {
      expect(screen.getByTestId('login-required')).toBeInTheDocument()
    })
  })

  it('should complete full booking flow from search to confirmation', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendors[0])
    ;(api.bookingApi.create as jest.Mock).mockResolvedValue(mockBooking)

    render(<BookingFlowIntegration />)

    await waitFor(() => {
      expect(screen.getByTestId('search-step')).toBeInTheDocument()
    })

    // Step 1: Search for vendors
    const searchInput = screen.getByTestId('search-input')
    fireEvent.blur(searchInput, { target: { value: 'wedding' } })

    await waitFor(() => {
      expect(screen.getByText('Dream Weddings')).toBeInTheDocument()
    })

    // Step 2: Select a vendor
    const viewDetailsButton = screen.getAllByText('View Details')[0]
    fireEvent.click(viewDetailsButton)

    await waitFor(() => {
      expect(screen.getByTestId('vendor-step')).toBeInTheDocument()
    })

    // Step 3: Select a service
    const bookServiceButton = screen.getByText('Book This Service')
    fireEvent.click(bookServiceButton)

    await waitFor(() => {
      expect(screen.getByTestId('booking-step')).toBeInTheDocument()
    })

    // Step 4: Fill booking form
    const eventDateInput = screen.getByTestId('event-date-input')
    const guestCountInput = screen.getByTestId('guest-count-input')
    const notesInput = screen.getByTestId('notes-input')

    fireEvent.change(eventDateInput, { target: { value: '2024-12-25' } })
    fireEvent.change(guestCountInput, { target: { value: '100' } })
    fireEvent.change(notesInput, {
      target: { value: 'Special requirements' },
    })

    // Step 5: Submit booking
    const submitButton = screen.getByTestId('submit-booking')
    fireEvent.click(submitButton)

    await waitFor(() => {
      expect(screen.getByTestId('confirmation-step')).toBeInTheDocument()
    })

    expect(screen.getByText('Booking Confirmed!')).toBeInTheDocument()
    expect(screen.getByTestId('booking-number')).toHaveTextContent(
      'Booking #BK-2024-001'
    )
    expect(screen.getByTestId('booking-status')).toHaveTextContent(
      'Status: pending'
    )
  })

  it('should allow navigation back through steps', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendors[0])

    render(<BookingFlowIntegration />)

    await waitFor(() => {
      expect(screen.getByTestId('search-step')).toBeInTheDocument()
    })

    // Go to vendor step
    const searchInput = screen.getByTestId('search-input')
    fireEvent.blur(searchInput, { target: { value: 'wedding' } })

    await waitFor(() => {
      expect(screen.getByText('Dream Weddings')).toBeInTheDocument()
    })

    fireEvent.click(screen.getAllByText('View Details')[0])

    await waitFor(() => {
      expect(screen.getByTestId('vendor-step')).toBeInTheDocument()
    })

    // Go to booking step
    fireEvent.click(screen.getByText('Book This Service'))

    await waitFor(() => {
      expect(screen.getByTestId('booking-step')).toBeInTheDocument()
    })

    // Go back to vendor step
    fireEvent.click(screen.getByText('Back'))

    await waitFor(() => {
      expect(screen.getByTestId('vendor-step')).toBeInTheDocument()
    })

    // Go back to search step
    fireEvent.click(screen.getByText('Back to Search'))

    await waitFor(() => {
      expect(screen.getByTestId('search-step')).toBeInTheDocument()
    })
  })

  it('should allow starting a new booking after confirmation', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendors[0])
    ;(api.bookingApi.create as jest.Mock).mockResolvedValue(mockBooking)

    render(<BookingFlowIntegration />)

    // Complete full flow
    await waitFor(() => {
      expect(screen.getByTestId('search-step')).toBeInTheDocument()
    })

    const searchInput = screen.getByTestId('search-input')
    fireEvent.blur(searchInput, { target: { value: 'wedding' } })

    await waitFor(() => {
      expect(screen.getByText('Dream Weddings')).toBeInTheDocument()
    })

    fireEvent.click(screen.getAllByText('View Details')[0])

    await waitFor(() => {
      expect(screen.getByTestId('vendor-step')).toBeInTheDocument()
    })

    fireEvent.click(screen.getByText('Book This Service'))

    await waitFor(() => {
      expect(screen.getByTestId('booking-step')).toBeInTheDocument()
    })

    fireEvent.change(screen.getByTestId('event-date-input'), {
      target: { value: '2024-12-25' },
    })
    fireEvent.change(screen.getByTestId('guest-count-input'), {
      target: { value: '100' },
    })

    fireEvent.click(screen.getByTestId('submit-booking'))

    await waitFor(() => {
      expect(screen.getByTestId('confirmation-step')).toBeInTheDocument()
    })

    // Start new booking
    fireEvent.click(screen.getByText('Make Another Booking'))

    await waitFor(() => {
      expect(screen.getByTestId('search-step')).toBeInTheDocument()
    })
  })

  it('should pass correct data when creating booking', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })
    ;(api.vendorApi.getById as jest.Mock).mockResolvedValue(mockVendors[0])
    ;(api.bookingApi.create as jest.Mock).mockResolvedValue(mockBooking)

    render(<BookingFlowIntegration />)

    await waitFor(() => {
      expect(screen.getByTestId('search-step')).toBeInTheDocument()
    })

    const searchInput = screen.getByTestId('search-input')
    fireEvent.blur(searchInput, { target: { value: 'wedding' } })

    await waitFor(() => {
      expect(screen.getByText('Dream Weddings')).toBeInTheDocument()
    })

    fireEvent.click(screen.getAllByText('View Details')[0])

    await waitFor(() => {
      expect(screen.getByTestId('vendor-step')).toBeInTheDocument()
    })

    fireEvent.click(screen.getByText('Book This Service'))

    await waitFor(() => {
      expect(screen.getByTestId('booking-step')).toBeInTheDocument()
    })

    fireEvent.change(screen.getByTestId('event-date-input'), {
      target: { value: '2024-12-25' },
    })
    fireEvent.change(screen.getByTestId('guest-count-input'), {
      target: { value: '150' },
    })
    fireEvent.change(screen.getByTestId('notes-input'), {
      target: { value: 'Outdoor ceremony' },
    })

    fireEvent.click(screen.getByTestId('submit-booking'))

    await waitFor(() => {
      expect(api.bookingApi.create).toHaveBeenCalledWith({
        serviceId: 'service-1',
        vendorId: 'vendor-1',
        eventDate: '2024-12-25',
        guestCount: 150,
        notes: 'Outdoor ceremony',
      })
    })
  })
})
