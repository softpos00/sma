# Smart Masjid Admin (Bootstrap + PHP)

A lightweight starter implementation of the Smart Masjid system with:

- **Bootstrap admin panel** with jQuery AJAX helpers
- **REST-style PHP API** for mobile + admin usage
- **JWT bearer authentication** with refresh tokens and OTP login for users
- **MySQL schema** aligned to the provided specification
- **OWASP-aware defaults** (prepared statements, no plaintext secrets, HTTPS-first messaging)

Use this as a base to extend into production (harden secrets, add rate limiting, wire up SMS gateway, etc.).

## Project layout

```
public/
  api/index.php        # Single entry point for REST endpoints
  admin/index.php      # Bootstrap admin UI with AJAX calls
  css/admin.css        # Small UI tweaks
src/
  ApiController.php    # Mosque/prayer CRUD endpoints
  AuthService.php      # OTP + admin auth + JWT handling
  Database.php         # PDO helper
  JwtService.php       # HS256 encode/decode
  Response.php         # JSON helper
database/schema.sql    # MySQL DDL + SUPER admin seed
```

## Setup (development)

1. Install PHP 8.1+ and MySQL 8 locally.
2. Import the schema:

   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. Configure environment (override defaults in `src/config.php`):

   ```bash
   export DB_HOST=localhost
   export DB_NAME=abbkfute_sma
   export DB_USER=abbkfute_sma
   export DB_PASSWORD=Rcis123$..
   export JWT_SECRET=please-change-me
   ```

4. Serve the project (Apache + `public/` as document root, or PHP built-in server with the provided router):

   ```bash
   php -S localhost:8000 -t public public/router.php
   ```

5. Use the default SUPER admin for the UI login (change in production):

   - Phone: `+8801000000000`
   - Password: `Arif00&` (bcrypt hash seeded in SQL)

## API surface (aligned to spec)

- `POST /api/auth/request-otp` – generate OTP (dev echo)
- `POST /api/auth/verify-otp` – verify and issue JWT + refresh
- `POST /api/auth/admin/login` – admin password login
- `POST /api/auth/refresh` – rotate access token using refresh token
- `POST /api/auth/logout` – revoke refresh token
- `GET /api/mosques` – list active mosques
- `GET /api/mosques/{id}` – mosque details
- `GET /api/mosques/{id}/today` – today’s times with default fallback
- `GET /api/mosques/{id}/next-prayer` – next upcoming prayer
- `POST /api/admin/prayer-times/update` – upsert a day’s jama‘ah times (ADMIN/SUPER)
- `POST /api/admin/jummah` – update Jumu’ah (ADMIN/SUPER)

All protected routes require the `Authorization: Bearer <access_token>` header.

## Admin UI walkthrough

- Login with the SUPER admin to receive an access token saved in-memory.
- Manage 365-day prayer times, Jumu’ah, and quickly fetch “today” values.
- OTP tester allows requesting an OTP for a phone (echoed only for development).
- Mosque directory table demonstrates authenticated GET calls.

## Security & quality notes (enhancements beyond the spec)

- Added DB indexes for refresh tokens and locations to improve audit lookups.
- JWT secret configurable via environment; access tokens remain stateless.
- All SQL interactions use prepared statements to block SQL injection.
- Recommended production additions:
  - Rate limiting and IP throttling on OTP endpoints
  - HTTPS-only deployment and HSTS headers at the web server
  - Move credentials and secrets into `.env` outside of the repo
  - Connect real SMS gateway for OTP delivery
  - Add audit logging for admin actions

## Development checklist

- Review `database/schema.sql` for schema and seed user.
- Adjust `public/admin/index.php` if your API base path differs from `/api`.
- Extend `ApiController` with additional validation and pagination as needed.

## Known limitations

- The demo uses a minimal router in `public/api/index.php`; consider a micro-framework for production.
- No CSRF is required for bearer APIs, but ensure CORS rules are aligned with mobile apps.
- Silent/DND automation is mobile-side (app responsibility) and not implemented server-side here.
