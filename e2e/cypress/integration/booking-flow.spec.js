describe('Booking Flow E2E', () => {
  beforeEach(() => {
    cy.visit('/')
  })

  it('completes full booking flow from search to confirmation', () => {
    // Step 1: Search for vendors
    cy.visit('/search')
    cy.get('[data-testid="search-input"]').type('photographer')
    cy.get('[data-testid="location-input"]').type('New York')
    cy.contains('button', 'Search').click()

    // Step 2: Wait for results and click on a vendor
    cy.get('[data-testid="vendor-list"]', { timeout: 10000 }).should('be.visible')
    cy.get('[data-testid^="vendor-"]').first().within(() => {
      cy.contains('View Details').click()
    })

    // Step 3: View vendor profile
    cy.url().should('include', '/vendors/')
    cy.get('[data-testid="vendor-header"]').should('be.visible')
    cy.get('[data-testid="services-section"]').should('be.visible')

    // Step 4: Select a service
    cy.get('[data-testid^="book-"]').first().click()

    // Step 5: Fill booking form
    cy.get('[data-testid="booking-modal"]').should('be.visible')
    cy.get('#eventDate').type('2024-12-25')
    cy.get('#guestCount').type('100')
    cy.get('#eventType').select('wedding')
    cy.get('#notes').type('Outdoor ceremony preferred')

    // Step 6: Submit booking
    cy.get('[data-testid="submit-button"]').click()

    // Step 7: Verify confirmation
    cy.get('[data-testid="confirmation-step"]', { timeout: 10000 }).should('be.visible')
    cy.contains('Booking Confirmed').should('be.visible')
    cy.get('[data-testid="booking-number"]').should('contain', 'BK-')
  })

  it('filters search results correctly', () => {
    cy.visit('/search')

    // Apply category filter
    cy.get('[data-testid="category-filter"]').select('venues')

    // Apply price range
    cy.get('#priceMin').type('1000')
    cy.get('#priceMax').type('5000')

    // Apply rating filter
    cy.get('[data-testid="rating-filter"]').select('4')

    // Apply filters
    cy.get('[data-testid="apply-filters-button"]').click()

    // Verify filtered results
    cy.get('[data-testid="vendor-list"]', { timeout: 10000 }).should('be.visible')
    cy.get('[data-testid^="vendor-"]').should('have.length.at.least', 1)
  })

  it('shows login prompt for unauthenticated users', () => {
    cy.visit('/search')
    cy.get('[data-testid^="vendor-"]').first().within(() => {
      cy.contains('View Details').click()
    })

    cy.url().should('include', '/vendors/')
    cy.get('[data-testid^="book-"]').first().click()

    // Should redirect to login or show login modal
    cy.url().should('match', /\/(login|auth)/)
  })

  it('allows canceling a booking', () => {
    cy.login('customer@example.com', 'password123')
    cy.visit('/dashboard/bookings')

    // Find a pending booking
    cy.get('[data-testid="booking-list"]').within(() => {
      cy.get('[data-testid^="booking-"]').first().within(() => {
        cy.contains('Cancel').click()
      })
    })

    // Confirm cancellation
    cy.get('[data-testid="cancel-modal"]').within(() => {
      cy.get('#reason').type('Change of plans')
      cy.contains('Confirm Cancellation').click()
    })

    // Verify cancellation
    cy.contains('Booking cancelled successfully').should('be.visible')
  })

  it('handles payment flow', () => {
    cy.login('customer@example.com', 'password123')
    cy.visit('/dashboard/bookings')

    // Find booking awaiting payment
    cy.get('[data-testid^="booking-"]').first().within(() => {
      cy.contains('Pay Deposit').click()
    })

    // Payment page
    cy.url().should('include', '/payment')
    cy.get('[data-testid="payment-amount"]').should('be.visible')

    // Fill Stripe payment form (mock)
    cy.get('#card-element').should('be.visible')
    cy.get('[data-testid="pay-button"]').click()

    // Verify payment success
    cy.contains('Payment Successful', { timeout: 15000 }).should('be.visible')
  })
})
