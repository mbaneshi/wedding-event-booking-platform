// Custom commands for Cypress tests

// Login command
Cypress.Commands.add('login', (email = 'test@example.com', password = 'password123') => {
  cy.request({
    method: 'POST',
    url: `${Cypress.env('apiUrl')}/auth/login`,
    body: {
      email,
      password,
    },
  }).then((response) => {
    window.localStorage.setItem('token', response.body.token)
    window.localStorage.setItem('user', JSON.stringify(response.body.user))
  })
})

// Logout command
Cypress.Commands.add('logout', () => {
  window.localStorage.removeItem('token')
  window.localStorage.removeItem('user')
})

// Create vendor command
Cypress.Commands.add('createVendor', (vendorData) => {
  const token = window.localStorage.getItem('token')
  return cy.request({
    method: 'POST',
    url: `${Cypress.env('apiUrl')}/vendors`,
    headers: {
      Authorization: `Bearer ${token}`,
    },
    body: vendorData,
  })
})

// Search vendors command
Cypress.Commands.add('searchVendors', (searchParams) => {
  return cy.request({
    method: 'GET',
    url: `${Cypress.env('apiUrl')}/vendors/search`,
    qs: searchParams,
  })
})

// Create booking command
Cypress.Commands.add('createBooking', (bookingData) => {
  const token = window.localStorage.getItem('token')
  return cy.request({
    method: 'POST',
    url: `${Cypress.env('apiUrl')}/bookings`,
    headers: {
      Authorization: `Bearer ${token}`,
    },
    body: bookingData,
  })
})

// Wait for API call
Cypress.Commands.add('waitForApi', (alias) => {
  cy.wait(alias).its('response.statusCode').should('eq', 200)
})
