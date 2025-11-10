describe('Vendor Registration E2E', () => {
  beforeEach(() => {
    cy.visit('/register/vendor')
  })

  it('completes vendor registration successfully', () => {
    // Step 1: User registration
    cy.get('#name').type('John Doe')
    cy.get('#email').type(`vendor-${Date.now()}@example.com`)
    cy.get('#password').type('SecurePassword123!')
    cy.get('#passwordConfirm').type('SecurePassword123!')
    cy.contains('button', 'Next').click()

    // Step 2: Business information
    cy.get('#businessName').type('Elite Events & Co')
    cy.get('#category').select('Wedding Planners')
    cy.get('#description').type('Premium wedding planning services with 10+ years experience')
    cy.get('#city').type('New York')
    cy.get('#country').select('United States')
    cy.get('#phone').type('+1234567890')
    cy.contains('button', 'Next').click()

    // Step 3: Services
    cy.contains('button', 'Add Service').click()
    cy.get('[data-testid="service-0-name"]').type('Full Wedding Planning')
    cy.get('[data-testid="service-0-description"]').type('Complete planning from start to finish')
    cy.get('[data-testid="service-0-priceFrom"]').type('5000')
    cy.get('[data-testid="service-0-priceTo"]').type('15000')
    cy.contains('button', 'Next').click()

    // Step 4: Verification
    cy.get('#businessLicense').attachFile('business-license.pdf')
    cy.get('#insurance').attachFile('insurance.pdf')
    cy.get('[data-testid="terms-checkbox"]').check()
    cy.contains('button', 'Submit Registration').click()

    // Verify success
    cy.contains('Registration Submitted', { timeout: 10000 }).should('be.visible')
    cy.contains('Your vendor profile is pending approval').should('be.visible')
  })

  it('validates required fields', () => {
    cy.contains('button', 'Next').click()

    cy.contains('Name is required').should('be.visible')
    cy.contains('Email is required').should('be.visible')
    cy.contains('Password is required').should('be.visible')
  })

  it('prevents duplicate email registration', () => {
    cy.get('#name').type('John Doe')
    cy.get('#email').type('existing@example.com')
    cy.get('#password').type('Password123!')
    cy.get('#passwordConfirm').type('Password123!')
    cy.contains('button', 'Next').click()

    cy.contains('Email already exists').should('be.visible')
  })
})
