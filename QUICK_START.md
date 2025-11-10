# Quick Start Guide

Get the Wedding & Event Booking Platform running in 5 minutes!

## Prerequisites

- Docker Desktop installed
- 10GB free disk space
- Internet connection

## 1. Start with Docker (Easiest)

```bash
# Navigate to project
cd /Users/nerd/freelancer/11-wedding-event-booking-platform

# Start all services
docker-compose up -d

# Wait 30 seconds for services to initialize
```

## 2. Initialize Database

```bash
# Run migrations
docker-compose exec backend php artisan migrate

# Seed sample data
docker-compose exec backend php artisan db:seed
```

## 3. Access the Application

- **Frontend:** http://localhost:3000
- **Backend API:** http://localhost:8000/api

## 4. Test Login

### Create Admin User
```bash
docker-compose exec backend php artisan tinker

# In tinker console, paste:
User::create(['email' => 'admin@test.com', 'password' => Hash::make('password'), 'first_name' => 'Admin', 'last_name' => 'User', 'role' => 'admin', 'email_verified_at' => now()]);
exit
```

### Login Credentials
- **Email:** admin@test.com
- **Password:** password

## 5. Test Stripe Payments

Use test card: **4242 4242 4242 4242**
- Expiry: Any future date
- CVC: Any 3 digits
- ZIP: Any 5 digits

## Common Commands

```bash
# View logs
docker-compose logs -f

# Stop services
docker-compose down

# Restart services
docker-compose restart

# Access backend shell
docker-compose exec backend bash

# Access frontend shell
docker-compose exec frontend sh
```

## Troubleshooting

### Services won't start
```bash
docker-compose down -v
docker-compose up -d --build
```

### Database connection failed
```bash
# Check PostgreSQL is running
docker-compose ps postgres

# Restart database
docker-compose restart postgres
```

### Port already in use
Edit `docker-compose.yml` and change ports:
```yaml
ports:
  - "3001:3000"  # Frontend
  - "8001:8000"  # Backend
```

## Next Steps

1. Read [SETUP_GUIDE.md](SETUP_GUIDE.md) for detailed setup
2. Configure Stripe keys in `.env` files
3. Add Google Maps API key
4. Customize branding and colors
5. Follow [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for production

## Need Help?

- See [PROJECT_README.md](PROJECT_README.md) for full documentation
- Check [REQUIREMENTS.md](REQUIREMENTS.md) for feature specifications
- Review [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md) for implementation details

## Quick Architecture Overview

```
┌─────────────┐         ┌─────────────┐         ┌──────────────┐
│   Next.js   │────────▶│   Laravel   │────────▶│  PostgreSQL  │
│  Frontend   │  HTTP   │   Backend   │   SQL   │   Database   │
│  Port 3000  │         │  Port 8000  │         │  Port 5432   │
└─────────────┘         └─────────────┘         └──────────────┘
                               │
                               │
                               ▼
                        ┌─────────────┐
                        │    Redis    │
                        │ Cache/Queue │
                        │  Port 6379  │
                        └─────────────┘
```

Happy Coding! 🚀
