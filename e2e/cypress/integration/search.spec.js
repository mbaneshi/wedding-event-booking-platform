describe('Search Functionality E2E', () => {
  beforeEach(() => {
    cy.visit('/search')
  })

  it('displays search page with filters', () => {
    cy.get('[data-testid="search-header"]').should('be.visible')
    cy.get('[data-testid="filters-sidebar"]').should('be.visible')
    cy.get('[data-testid="results-section"]').should('be.visible')
  })

  it('searches vendors by keyword', () => {
    cy.get('[data-testid="search-input"]').type('photographer')
    cy.contains('button', 'Search').click()

    cy.get('[data-testid="vendor-list"]', { timeout: 10000 }).should('be.visible')
    cy.get('[data-testid^="vendor-"]').should('have.length.at.least', 1)
  })

  it('filters by category', () => {
    cy.get('[data-testid="category-filter"]').select('venues')
    cy.get('[data-testid="apply-filters-button"]').click()

    cy.get('[data-testid="vendor-list"]', { timeout: 10000 }).should('be.visible')
  })

  it('filters by location', () => {
    cy.get('[data-testid="location-input"]').type('New York')
    cy.contains('button', 'Search').click()

    cy.get('[data-testid="vendor-list"]', { timeout: 10000 }).should('be.visible')
  })

  it('filters by price range', () => {
    cy.get('#priceMin').type('1000')
    cy.get('#priceMax').type('5000')
    cy.get('[data-testid="apply-filters-button"]').click()

    cy.get('[data-testid="vendor-list"]', { timeout: 10000 }).should('be.visible')
  })

  it('filters by minimum rating', () => {
    cy.get('[data-testid="rating-filter"]').select('4')
    cy.get('[data-testid="apply-filters-button"]').click()

    cy.get('[data-testid="vendor-list"]', { timeout: 10000 }).should('be.visible')
  })

  it('sorts results by different criteria', () => {
    cy.get('[data-testid="sort-select"]').select('price')
    cy.waitForApi('@searchVendors')

    cy.get('[data-testid="sort-select"]').select('rating')
    cy.waitForApi('@searchVendors')

    cy.get('[data-testid="sort-select"]').select('popularity')
    cy.waitForApi('@searchVendors')
  })

  it('shows no results message when no vendors found', () => {
    cy.get('[data-testid="search-input"]').type('nonexistentvendor12345')
    cy.contains('button', 'Search').click()

    cy.get('[data-testid="no-results"]', { timeout: 10000 }).should('be.visible')
    cy.contains('No vendors found').should('be.visible')
  })

  it('navigates to vendor profile on click', () => {
    cy.get('[data-testid^="vendor-"]').first().click()

    cy.url().should('include', '/vendors/')
    cy.get('[data-testid="vendor-header"]').should('be.visible')
  })

  it('displays vendor cards with correct information', () => {
    cy.get('[data-testid^="vendor-"]').first().within(() => {
      cy.get('h3').should('be.visible') // Business name
      cy.get('[data-testid="rating"]').should('be.visible')
      cy.get('[data-testid="location"]').should('be.visible')
      cy.get('[data-testid="price"]').should('be.visible')
    })
  })

  it('combines multiple filters', () => {
    cy.get('[data-testid="category-filter"]').select('photographers')
    cy.get('[data-testid="location-input"]').type('New York')
    cy.get('#priceMin').type('2000')
    cy.get('#priceMax').type('8000')
    cy.get('[data-testid="rating-filter"]').select('4')
    cy.get('[data-testid="apply-filters-button"]').click()

    cy.get('[data-testid="results-section"]', { timeout: 10000 }).should('be.visible')
  })
})
