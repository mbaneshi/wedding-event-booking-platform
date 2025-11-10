import React from 'react'
import { render, screen, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import '@testing-library/jest-dom'

// Mock SearchFilters component
interface SearchFiltersProps {
  filters: {
    category?: string
    priceMin?: number
    priceMax?: number
    minRating?: number
    date?: string
  }
  onFilterChange: (filters: any) => void
  onApply: () => void
}

const SearchFilters: React.FC<SearchFiltersProps> = ({
  filters,
  onFilterChange,
  onApply,
}) => {
  return (
    <div data-testid="search-filters">
      <h3>Filters</h3>

      <div>
        <label htmlFor="category">Category</label>
        <select
          id="category"
          value={filters.category || ''}
          onChange={(e) =>
            onFilterChange({ ...filters, category: e.target.value })
          }
        >
          <option value="">All Categories</option>
          <option value="venues">Venues</option>
          <option value="photographers">Photographers</option>
          <option value="catering">Catering</option>
          <option value="florists">Florists</option>
        </select>
      </div>

      <div>
        <label htmlFor="priceMin">Min Price</label>
        <input
          id="priceMin"
          type="number"
          placeholder="Min"
          value={filters.priceMin || ''}
          onChange={(e) =>
            onFilterChange({ ...filters, priceMin: Number(e.target.value) })
          }
        />
      </div>

      <div>
        <label htmlFor="priceMax">Max Price</label>
        <input
          id="priceMax"
          type="number"
          placeholder="Max"
          value={filters.priceMax || ''}
          onChange={(e) =>
            onFilterChange({ ...filters, priceMax: Number(e.target.value) })
          }
        />
      </div>

      <div>
        <label htmlFor="minRating">Minimum Rating</label>
        <select
          id="minRating"
          value={filters.minRating || ''}
          onChange={(e) =>
            onFilterChange({ ...filters, minRating: Number(e.target.value) })
          }
        >
          <option value="">Any Rating</option>
          <option value="4">4+ Stars</option>
          <option value="4.5">4.5+ Stars</option>
        </select>
      </div>

      <div>
        <label htmlFor="eventDate">Event Date</label>
        <input
          id="eventDate"
          type="date"
          value={filters.date || ''}
          onChange={(e) =>
            onFilterChange({ ...filters, date: e.target.value })
          }
        />
      </div>

      <button onClick={onApply} data-testid="apply-filters">
        Apply Filters
      </button>
    </div>
  )
}

describe('SearchFilters Component', () => {
  const mockOnFilterChange = jest.fn()
  const mockOnApply = jest.fn()
  const defaultFilters = {}

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('should render all filter options', () => {
    render(
      <SearchFilters
        filters={defaultFilters}
        onFilterChange={mockOnFilterChange}
        onApply={mockOnApply}
      />
    )

    expect(screen.getByLabelText('Category')).toBeInTheDocument()
    expect(screen.getByLabelText('Min Price')).toBeInTheDocument()
    expect(screen.getByLabelText('Max Price')).toBeInTheDocument()
    expect(screen.getByLabelText('Minimum Rating')).toBeInTheDocument()
    expect(screen.getByLabelText('Event Date')).toBeInTheDocument()
  })

  it('should call onFilterChange when category is selected', () => {
    render(
      <SearchFilters
        filters={defaultFilters}
        onFilterChange={mockOnFilterChange}
        onApply={mockOnApply}
      />
    )

    const categorySelect = screen.getByLabelText('Category')
    fireEvent.change(categorySelect, { target: { value: 'venues' } })

    expect(mockOnFilterChange).toHaveBeenCalledWith({
      category: 'venues',
    })
  })

  it('should call onFilterChange when price range is set', () => {
    render(
      <SearchFilters
        filters={defaultFilters}
        onFilterChange={mockOnFilterChange}
        onApply={mockOnApply}
      />
    )

    const minPriceInput = screen.getByLabelText('Min Price')
    const maxPriceInput = screen.getByLabelText('Max Price')

    fireEvent.change(minPriceInput, { target: { value: '1000' } })
    expect(mockOnFilterChange).toHaveBeenCalledWith({ priceMin: 1000 })

    fireEvent.change(maxPriceInput, { target: { value: '5000' } })
    expect(mockOnFilterChange).toHaveBeenCalledWith({ priceMax: 5000 })
  })

  it('should call onFilterChange when minimum rating is selected', () => {
    render(
      <SearchFilters
        filters={defaultFilters}
        onFilterChange={mockOnFilterChange}
        onApply={mockOnApply}
      />
    )

    const ratingSelect = screen.getByLabelText('Minimum Rating')
    fireEvent.change(ratingSelect, { target: { value: '4.5' } })

    expect(mockOnFilterChange).toHaveBeenCalledWith({ minRating: 4.5 })
  })

  it('should call onFilterChange when date is selected', () => {
    render(
      <SearchFilters
        filters={defaultFilters}
        onFilterChange={mockOnFilterChange}
        onApply={mockOnApply}
      />
    )

    const dateInput = screen.getByLabelText('Event Date')
    fireEvent.change(dateInput, { target: { value: '2024-12-25' } })

    expect(mockOnFilterChange).toHaveBeenCalledWith({ date: '2024-12-25' })
  })

  it('should call onApply when apply button is clicked', () => {
    render(
      <SearchFilters
        filters={defaultFilters}
        onFilterChange={mockOnFilterChange}
        onApply={mockOnApply}
      />
    )

    const applyButton = screen.getByTestId('apply-filters')
    fireEvent.click(applyButton)

    expect(mockOnApply).toHaveBeenCalledTimes(1)
  })

  it('should display current filter values', () => {
    const currentFilters = {
      category: 'photographers',
      priceMin: 2000,
      priceMax: 8000,
      minRating: 4,
      date: '2024-06-15',
    }

    render(
      <SearchFilters
        filters={currentFilters}
        onFilterChange={mockOnFilterChange}
        onApply={mockOnApply}
      />
    )

    expect(screen.getByLabelText('Category')).toHaveValue('photographers')
    expect(screen.getByLabelText('Min Price')).toHaveValue(2000)
    expect(screen.getByLabelText('Max Price')).toHaveValue(8000)
    expect(screen.getByLabelText('Minimum Rating')).toHaveValue('4')
    expect(screen.getByLabelText('Event Date')).toHaveValue('2024-06-15')
  })

  it('should have all category options available', () => {
    render(
      <SearchFilters
        filters={defaultFilters}
        onFilterChange={mockOnFilterChange}
        onApply={mockOnApply}
      />
    )

    const categorySelect = screen.getByLabelText('Category')
    const options = categorySelect.querySelectorAll('option')

    expect(options).toHaveLength(5)
    expect(options[0]).toHaveTextContent('All Categories')
    expect(options[1]).toHaveTextContent('Venues')
    expect(options[2]).toHaveTextContent('Photographers')
    expect(options[3]).toHaveTextContent('Catering')
    expect(options[4]).toHaveTextContent('Florists')
  })
})
