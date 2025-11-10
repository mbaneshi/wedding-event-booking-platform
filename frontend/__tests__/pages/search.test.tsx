import React from 'react'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import '@testing-library/jest-dom'
import * as api from '@/lib/api'

// Mock the API module
jest.mock('@/lib/api', () => ({
  vendorApi: {
    search: jest.fn(),
  },
}))

// Mock the search page component
const SearchPage = () => {
  const [vendors, setVendors] = React.useState<any[]>([])
  const [loading, setLoading] = React.useState(true)
  const [filters, setFilters] = React.useState<any>({
    sortBy: 'rating',
  })

  React.useEffect(() => {
    loadVendors()
  }, [filters])

  const loadVendors = async () => {
    try {
      setLoading(true)
      const data = await (api as any).vendorApi.search(filters)
      setVendors(data.data)
    } catch (error) {
      console.error('Failed to load vendors:', error)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div>
      <div data-testid="search-header">
        <input
          type="text"
          placeholder="Search vendors..."
          data-testid="search-input"
          onChange={(e) => setFilters({ ...filters, query: e.target.value })}
        />
        <input
          type="text"
          placeholder="Location"
          data-testid="location-input"
          onChange={(e) => setFilters({ ...filters, city: e.target.value })}
        />
        <button>Search</button>
      </div>

      <div data-testid="filters-sidebar">
        <select
          data-testid="category-filter"
          onChange={(e) => setFilters({ ...filters, category: e.target.value })}
        >
          <option value="">All Categories</option>
          <option value="venues">Venues</option>
          <option value="photographers">Photographers</option>
        </select>

        <select
          data-testid="sort-select"
          value={filters.sortBy}
          onChange={(e) => setFilters({ ...filters, sortBy: e.target.value })}
        >
          <option value="rating">Highest Rated</option>
          <option value="price">Price: Low to High</option>
          <option value="popularity">Most Popular</option>
        </select>

        <button onClick={loadVendors} data-testid="apply-filters-button">
          Apply Filters
        </button>
      </div>

      <div data-testid="results-section">
        <h1>{vendors.length} Vendors Found</h1>

        {loading ? (
          <div data-testid="loading">Loading...</div>
        ) : vendors.length === 0 ? (
          <div data-testid="no-results">
            No vendors found. Try adjusting your filters.
          </div>
        ) : (
          <div data-testid="vendor-list">
            {vendors.map((vendor) => (
              <div key={vendor.id} data-testid={`vendor-${vendor.id}`}>
                <h3>{vendor.businessName}</h3>
                <p>{vendor.description}</p>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

describe('SearchPage', () => {
  const mockVendors = [
    {
      id: '1',
      businessName: 'Elegant Venues',
      description: 'Beautiful wedding venues',
      ratingAverage: 4.8,
      city: 'New York',
    },
    {
      id: '2',
      businessName: 'Perfect Photos',
      description: 'Professional photography',
      ratingAverage: 4.9,
      city: 'Los Angeles',
    },
  ]

  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('should render search page with all sections', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })

    render(<SearchPage />)

    expect(screen.getByTestId('search-header')).toBeInTheDocument()
    expect(screen.getByTestId('filters-sidebar')).toBeInTheDocument()
    expect(screen.getByTestId('results-section')).toBeInTheDocument()
  })

  it('should show loading state initially', () => {
    ;(api.vendorApi.search as jest.Mock).mockImplementation(
      () => new Promise(() => {})
    )

    render(<SearchPage />)

    expect(screen.getByTestId('loading')).toBeInTheDocument()
  })

  it('should load and display vendors on mount', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })

    render(<SearchPage />)

    await waitFor(() => {
      expect(screen.getByText('2 Vendors Found')).toBeInTheDocument()
    })

    expect(screen.getByText('Elegant Venues')).toBeInTheDocument()
    expect(screen.getByText('Perfect Photos')).toBeInTheDocument()
  })

  it('should show no results message when no vendors found', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: [],
    })

    render(<SearchPage />)

    await waitFor(() => {
      expect(screen.getByTestId('no-results')).toBeInTheDocument()
    })

    expect(screen.getByText(/No vendors found/i)).toBeInTheDocument()
  })

  it('should update filters when search input changes', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })

    render(<SearchPage />)

    const searchInput = screen.getByTestId('search-input')
    fireEvent.change(searchInput, { target: { value: 'photographer' } })

    await waitFor(() => {
      expect(api.vendorApi.search).toHaveBeenCalledWith(
        expect.objectContaining({
          query: 'photographer',
        })
      )
    })
  })

  it('should update filters when location input changes', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })

    render(<SearchPage />)

    const locationInput = screen.getByTestId('location-input')
    fireEvent.change(locationInput, { target: { value: 'New York' } })

    await waitFor(() => {
      expect(api.vendorApi.search).toHaveBeenCalledWith(
        expect.objectContaining({
          city: 'New York',
        })
      )
    })
  })

  it('should update filters when category is changed', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })

    render(<SearchPage />)

    const categoryFilter = screen.getByTestId('category-filter')
    fireEvent.change(categoryFilter, { target: { value: 'venues' } })

    await waitFor(() => {
      expect(api.vendorApi.search).toHaveBeenCalledWith(
        expect.objectContaining({
          category: 'venues',
        })
      )
    })
  })

  it('should update sort order when changed', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })

    render(<SearchPage />)

    await waitFor(() => {
      expect(screen.getByTestId('sort-select')).toBeInTheDocument()
    })

    const sortSelect = screen.getByTestId('sort-select')
    fireEvent.change(sortSelect, { target: { value: 'price' } })

    await waitFor(() => {
      expect(api.vendorApi.search).toHaveBeenCalledWith(
        expect.objectContaining({
          sortBy: 'price',
        })
      )
    })
  })

  it('should reload vendors when apply filters is clicked', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })

    render(<SearchPage />)

    await waitFor(() => {
      expect(screen.getByTestId('apply-filters-button')).toBeInTheDocument()
    })

    const initialCallCount = (api.vendorApi.search as jest.Mock).mock.calls.length

    const applyButton = screen.getByTestId('apply-filters-button')
    fireEvent.click(applyButton)

    await waitFor(() => {
      expect(api.vendorApi.search).toHaveBeenCalledTimes(
        initialCallCount + 1
      )
    })
  })

  it('should handle API errors gracefully', async () => {
    const consoleError = jest.spyOn(console, 'error').mockImplementation()
    ;(api.vendorApi.search as jest.Mock).mockRejectedValue(
      new Error('API Error')
    )

    render(<SearchPage />)

    await waitFor(() => {
      expect(screen.getByTestId('no-results')).toBeInTheDocument()
    })

    expect(consoleError).toHaveBeenCalled()
    consoleError.mockRestore()
  })

  it('should render correct number of vendor cards', async () => {
    ;(api.vendorApi.search as jest.Mock).mockResolvedValue({
      data: mockVendors,
    })

    render(<SearchPage />)

    await waitFor(() => {
      expect(screen.getByTestId('vendor-1')).toBeInTheDocument()
      expect(screen.getByTestId('vendor-2')).toBeInTheDocument()
    })
  })
})
