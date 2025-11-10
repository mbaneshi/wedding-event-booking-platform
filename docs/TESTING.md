# Testing Documentation

## Overview

This document outlines the testing strategy and procedures for the Wedding Booking Platform.

## Test Coverage Goals

- **Minimum Coverage**: 80% across all codebases
- **Frontend**: Jest + React Testing Library
- **Backend**: PHPUnit
- **E2E**: Cypress

## Frontend Testing

### Setup

```bash
cd frontend
npm install
```

### Running Tests

```bash
# Run all tests
npm test

# Run tests with coverage
npm run test:coverage

# Run tests in watch mode
npm run test:watch
```

### Test Structure

```
frontend/__tests__/
├── components/           # Component unit tests
│   ├── VendorCard.test.tsx
│   ├── SearchFilters.test.tsx
│   └── BookingForm.test.tsx
├── pages/               # Page integration tests
│   ├── search.test.tsx
│   └── vendor-profile.test.tsx
└── integration/         # Full flow tests
    └── booking-flow.test.tsx
```

### Writing Component Tests

```typescript
import { render, screen, fireEvent } from '@testing-library/react'
import '@testing-library/jest-dom'
import YourComponent from '@/components/YourComponent'

describe('YourComponent', () => {
  it('should render correctly', () => {
    render(<YourComponent />)
    expect(screen.getByText('Expected Text')).toBeInTheDocument()
  })

  it('should handle user interactions', () => {
    render(<YourComponent />)
    const button = screen.getByRole('button')
    fireEvent.click(button)
    expect(screen.getByText('Result')).toBeInTheDocument()
  })
})
```

## Backend Testing

### Setup

```bash
cd backend
composer install
php artisan test --env=testing
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/VendorControllerTest.php

# Run specific test method
php artisan test --filter test_can_create_vendor
```

### Test Structure

```
backend/tests/
├── Unit/
│   └── Services/
│       ├── SearchServiceTest.php
│       ├── BookingServiceTest.php
│       ├── PaymentServiceTest.php
│       └── NotificationServiceTest.php
└── Feature/
    ├── VendorControllerTest.php
    ├── BookingControllerTest.php
    ├── PaymentControllerTest.php
    └── ReviewControllerTest.php
```

### Writing Unit Tests

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\YourService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class YourServiceTest extends TestCase
{
    use RefreshDatabase;

    private YourService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new YourService();
    }

    public function test_method_works_correctly()
    {
        $result = $this->service->method();
        $this->assertEquals('expected', $result);
    }
}
```

### Writing Feature Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class YourControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_returns_success()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/your-endpoint', ['data' => 'value']);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }
}
```

## E2E Testing with Cypress

### Setup

```bash
cd e2e
npm install cypress
```

### Running E2E Tests

```bash
# Interactive mode
npm run cypress

# Headless mode
npm run cypress:headless

# Specific test
npx cypress run --spec "cypress/integration/booking-flow.spec.js"
```

### Test Structure

```
e2e/cypress/
├── integration/
│   ├── booking-flow.spec.js
│   ├── vendor-registration.spec.js
│   └── search.spec.js
├── fixtures/            # Test data
├── support/
│   ├── commands.js      # Custom commands
│   └── e2e.js          # Global config
└── cypress.config.js
```

### Writing E2E Tests

```javascript
describe('Feature Flow', () => {
  beforeEach(() => {
    cy.visit('/')
  })

  it('completes the flow successfully', () => {
    cy.get('[data-testid="input"]').type('test')
    cy.get('[data-testid="submit"]').click()
    cy.contains('Success').should('be.visible')
  })
})
```

## Test Data Management

### Factories (Backend)

```php
// database/factories/VendorFactory.php
public function definition()
{
    return [
        'business_name' => $this->faker->company(),
        'email' => $this->faker->unique()->safeEmail(),
        'status' => 'approved',
        // ...
    ];
}
```

### Mock Data (Frontend)

```typescript
// __tests__/mocks/vendors.ts
export const mockVendor = {
  id: '123',
  businessName: 'Test Vendor',
  // ...
}
```

## Continuous Integration

Tests run automatically on:
- Pull request creation
- Push to main branch
- Scheduled nightly runs

### GitHub Actions Workflow

```yaml
name: Tests
on: [push, pull_request]
jobs:
  frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - run: cd frontend && npm install && npm test
  backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - run: cd backend && composer install && php artisan test
```

## Coverage Reports

### Frontend Coverage

```bash
npm run test:coverage
# View: frontend/coverage/lcov-report/index.html
```

### Backend Coverage

```bash
php artisan test --coverage --min=80
# View: backend/coverage/index.html
```

## Best Practices

1. **Write tests first** (TDD approach when possible)
2. **Test behavior, not implementation**
3. **Keep tests independent** (no shared state)
4. **Use descriptive test names**
5. **Mock external services** (APIs, databases when needed)
6. **Test edge cases and error conditions**
7. **Maintain test data fixtures**
8. **Run tests before committing**

## Troubleshooting

### Common Issues

**Frontend tests fail with module not found**
```bash
npm install
# Check jest.config.js moduleNameMapper
```

**Backend tests fail with database errors**
```bash
php artisan migrate:fresh --env=testing
```

**Cypress tests timeout**
```bash
# Increase timeout in cypress.config.js
defaultCommandTimeout: 10000
```

## Resources

- [Jest Documentation](https://jestjs.io/)
- [React Testing Library](https://testing-library.com/react)
- [PHPUnit Documentation](https://phpunit.de/)
- [Cypress Documentation](https://www.cypress.io/)
