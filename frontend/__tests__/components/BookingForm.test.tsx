import React from 'react'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import '@testing-library/jest-dom'

// Mock BookingForm component
interface BookingFormProps {
  vendorId: string
  serviceId: string
  onSubmit: (data: any) => Promise<void>
  onCancel?: () => void
}

const BookingForm: React.FC<BookingFormProps> = ({
  vendorId,
  serviceId,
  onSubmit,
  onCancel,
}) => {
  const [formData, setFormData] = React.useState({
    eventDate: '',
    guestCount: '',
    eventType: '',
    notes: '',
  })
  const [loading, setLoading] = React.useState(false)
  const [error, setError] = React.useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')

    if (!formData.eventDate || !formData.guestCount) {
      setError('Please fill in all required fields')
      return
    }

    try {
      setLoading(true)
      await onSubmit({
        ...formData,
        vendorId,
        serviceId,
        guestCount: Number(formData.guestCount),
      })
    } catch (err: any) {
      setError(err.message || 'Failed to create booking')
    } finally {
      setLoading(false)
    }
  }

  return (
    <form onSubmit={handleSubmit} data-testid="booking-form">
      <h2>Book This Service</h2>

      {error && (
        <div role="alert" data-testid="error-message">
          {error}
        </div>
      )}

      <div>
        <label htmlFor="eventDate">Event Date *</label>
        <input
          id="eventDate"
          type="date"
          required
          value={formData.eventDate}
          onChange={(e) =>
            setFormData({ ...formData, eventDate: e.target.value })
          }
        />
      </div>

      <div>
        <label htmlFor="guestCount">Number of Guests *</label>
        <input
          id="guestCount"
          type="number"
          required
          min="1"
          value={formData.guestCount}
          onChange={(e) =>
            setFormData({ ...formData, guestCount: e.target.value })
          }
        />
      </div>

      <div>
        <label htmlFor="eventType">Event Type</label>
        <select
          id="eventType"
          value={formData.eventType}
          onChange={(e) =>
            setFormData({ ...formData, eventType: e.target.value })
          }
        >
          <option value="">Select event type</option>
          <option value="wedding">Wedding</option>
          <option value="engagement">Engagement</option>
          <option value="anniversary">Anniversary</option>
          <option value="other">Other</option>
        </select>
      </div>

      <div>
        <label htmlFor="notes">Additional Notes</label>
        <textarea
          id="notes"
          rows={4}
          value={formData.notes}
          onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
          placeholder="Any special requirements or questions?"
        />
      </div>

      <div>
        <button type="submit" disabled={loading} data-testid="submit-button">
          {loading ? 'Submitting...' : 'Submit Booking Request'}
        </button>
        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            data-testid="cancel-button"
          >
            Cancel
          </button>
        )}
      </div>
    </form>
  )
}

describe('BookingForm Component', () => {
  const mockOnSubmit = jest.fn()
  const mockOnCancel = jest.fn()
  const defaultProps = {
    vendorId: 'vendor-123',
    serviceId: 'service-456',
    onSubmit: mockOnSubmit,
  }

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('should render booking form with all fields', () => {
    render(<BookingForm {...defaultProps} />)

    expect(screen.getByLabelText(/Event Date/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Number of Guests/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Event Type/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/Additional Notes/i)).toBeInTheDocument()
    expect(screen.getByTestId('submit-button')).toBeInTheDocument()
  })

  it('should show validation error for empty required fields', async () => {
    render(<BookingForm {...defaultProps} />)

    const submitButton = screen.getByTestId('submit-button')
    fireEvent.click(submitButton)

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent(
        'Please fill in all required fields'
      )
    })

    expect(mockOnSubmit).not.toHaveBeenCalled()
  })

  it('should submit form with valid data', async () => {
    mockOnSubmit.mockResolvedValue(undefined)

    render(<BookingForm {...defaultProps} />)

    const eventDateInput = screen.getByLabelText(/Event Date/i)
    const guestCountInput = screen.getByLabelText(/Number of Guests/i)
    const eventTypeSelect = screen.getByLabelText(/Event Type/i)
    const notesTextarea = screen.getByLabelText(/Additional Notes/i)

    fireEvent.change(eventDateInput, { target: { value: '2024-12-25' } })
    fireEvent.change(guestCountInput, { target: { value: '100' } })
    fireEvent.change(eventTypeSelect, { target: { value: 'wedding' } })
    fireEvent.change(notesTextarea, {
      target: { value: 'Outdoor ceremony preferred' },
    })

    const submitButton = screen.getByTestId('submit-button')
    fireEvent.click(submitButton)

    await waitFor(() => {
      expect(mockOnSubmit).toHaveBeenCalledWith({
        eventDate: '2024-12-25',
        guestCount: 100,
        eventType: 'wedding',
        notes: 'Outdoor ceremony preferred',
        vendorId: 'vendor-123',
        serviceId: 'service-456',
      })
    })
  })

  it('should disable submit button while loading', async () => {
    mockOnSubmit.mockImplementation(
      () => new Promise((resolve) => setTimeout(resolve, 100))
    )

    render(<BookingForm {...defaultProps} />)

    fireEvent.change(screen.getByLabelText(/Event Date/i), {
      target: { value: '2024-12-25' },
    })
    fireEvent.change(screen.getByLabelText(/Number of Guests/i), {
      target: { value: '50' },
    })

    const submitButton = screen.getByTestId('submit-button')
    fireEvent.click(submitButton)

    await waitFor(() => {
      expect(submitButton).toBeDisabled()
      expect(submitButton).toHaveTextContent('Submitting...')
    })
  })

  it('should display error message on submission failure', async () => {
    mockOnSubmit.mockRejectedValue(new Error('Network error'))

    render(<BookingForm {...defaultProps} />)

    fireEvent.change(screen.getByLabelText(/Event Date/i), {
      target: { value: '2024-12-25' },
    })
    fireEvent.change(screen.getByLabelText(/Number of Guests/i), {
      target: { value: '50' },
    })

    const submitButton = screen.getByTestId('submit-button')
    fireEvent.click(submitButton)

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent('Network error')
    })
  })

  it('should call onCancel when cancel button is clicked', () => {
    render(<BookingForm {...defaultProps} onCancel={mockOnCancel} />)

    const cancelButton = screen.getByTestId('cancel-button')
    fireEvent.click(cancelButton)

    expect(mockOnCancel).toHaveBeenCalledTimes(1)
  })

  it('should not render cancel button when onCancel is not provided', () => {
    render(<BookingForm {...defaultProps} />)

    expect(screen.queryByTestId('cancel-button')).not.toBeInTheDocument()
  })

  it('should have all event type options', () => {
    render(<BookingForm {...defaultProps} />)

    const eventTypeSelect = screen.getByLabelText(/Event Type/i)
    const options = eventTypeSelect.querySelectorAll('option')

    expect(options).toHaveLength(5)
    expect(options[0]).toHaveTextContent('Select event type')
    expect(options[1]).toHaveTextContent('Wedding')
    expect(options[2]).toHaveTextContent('Engagement')
    expect(options[3]).toHaveTextContent('Anniversary')
    expect(options[4]).toHaveTextContent('Other')
  })

  it('should validate guest count is a positive number', () => {
    render(<BookingForm {...defaultProps} />)

    const guestCountInput = screen.getByLabelText(
      /Number of Guests/i
    ) as HTMLInputElement

    expect(guestCountInput).toHaveAttribute('type', 'number')
    expect(guestCountInput).toHaveAttribute('min', '1')
  })
})
