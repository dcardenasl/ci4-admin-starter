# Quick Start Guide

Get up and running with **CI4 Admin Starter** in 5 minutes.

## Prerequisites

Before starting, ensure you have:

- **PHP 8.1+** installed and accessible via `php` command
- **Composer 2.x** installed
- **Node.js 16+** and npm installed
- **Git** (to clone the repository)
- A running instance of **[ci4-api-starter](https://github.com/dcardenasl/ci4-api-starter)** on `http://localhost:8180` (or update the URL in `.env`)

Check your versions:
```bash
php --version
composer --version
node --version
npm --version
```

## Step 1: Clone the Repository

```bash
git clone https://github.com/dcardenasl/ci4-admin-starter.git
cd ci4-admin-starter
```

## Step 2: Run the Install Script (Recommended)

```bash
bash install.sh
```

The script will:
1. Create `.env` from the template
2. Prompt you for configuration (API URL, environment, etc.)
3. Optionally install Composer dependencies
4. Optionally install npm dependencies
5. Run database seeds/migrations (if applicable)

**Answer the prompts as follows:**

| Prompt | Example |
|--------|---------|
| Environment (dev/prod) | `development` |
| Application Base URL | `http://localhost:8182/` |
| Backend API Base URL | `http://localhost:8180/` |
| API Prefix | `/api/v1` |
| Install Composer deps? | `y` |
| Install npm deps? | `y` |

## Step 3: Manual Installation (Alternative to Script)

If you prefer manual setup:

```bash
# Install PHP dependencies
composer install

# Install npm dependencies
npm install

# Copy environment template
cp env .env
```

Then edit `.env`:

```dotenv
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8182/'
apiClient.baseUrl = 'http://localhost:8180'
apiClient.apiPrefix = '/api/v1'
```

## Step 4: Start Development Servers

Open **two terminal windows** in your project directory.

**Terminal 1 — PHP Development Server:**
```bash
php spark serve --port 8182
```

You should see:
```
CodeIgniter v4.x.x Command Line Tool - Server Edition
...
Server running on http://localhost:8182
```

**Terminal 2 — Tailwind CSS Watcher:**
```bash
npm run dev:css
```

You should see:
```
> npm run dev:css
Rebuilding CSS...
```

Both must be running during development.

## Step 5: Open in Browser

Navigate to: **`http://localhost:8182`**

You should see the login page. If the backend API is running on `http://localhost:8180`, you can:

1. **Create a new account** (if registration is enabled)
2. **Login** with test credentials from the backend

### Verify the Setup

- [ ] Page loads without errors
- [ ] Styles are properly applied (colors, layout)
- [ ] Can navigate to login/register page
- [ ] Backend API is accessible (check browser console for errors)

## Configuration Checklist

Before using the application, verify:

```
✅ .env exists and is configured correctly
✅ apiClient.baseUrl points to your backend API
✅ Both PHP and npm dev servers are running
✅ No errors in browser console (F12)
✅ Backend API is running and accessible
✅ Database migrations on backend are complete (if using API seeding)
```

## Common Setup Issues

### "Connection refused" Error

**Issue:** `Connection to http://localhost:8180 refused`

**Solution:**
1. Ensure backend API is running: `php spark serve --port 8180` in the backend project
2. Check `apiClient.baseUrl` in `.env` matches the backend port
3. Restart the PHP development server

### Styles Not Applied (Page Looks Broken)

**Issue:** No colors, broken layout

**Solution:**
1. Stop the npm dev server (Ctrl+C)
2. Run `npm run build:css` to rebuild CSS
3. Restart with `npm run dev:css`
4. Hard refresh browser (Cmd+Shift+R or Ctrl+Shift+R)

### Port Already in Use

**Issue:** `Address already in use` when starting `php spark serve`

**Solution:**
```bash
# Use a different port
php spark serve --port 8083

# Or kill the process using port 8182
lsof -i :8182  # Find process ID
kill -9 <PID>  # Kill it
```

### 401 Unauthorized Errors

**Issue:** All API requests return 401

**Solution:**
1. Check backend API is running and healthy
2. If using `apiClient.appKey`, verify it's correct (wrong key causes all requests to return 401)
3. Try removing `apiClient.appKey` temporarily to isolate the issue
4. Check backend logs for validation errors

## Running Tests

```bash
# Run all tests
composer test

# Run unit tests only
composer test:unit

# Run feature tests only
composer test:feature

# Run with coverage report
composer test:coverage
```

All tests should pass before proceeding to development.

## Next Steps

1. **Read the [Documentation Hub](./INDEX.md)** for detailed guides
2. **Explore the [Architecture](./ARCHITECTURE.md)** to understand the request flow
3. **Check [Frontend Guide](./FRONTEND.md)** for UI component patterns
4. **Review [Services & Validation](./SERVICES.md)** to learn how to add new features
5. **See [How-To Guides](./HOW-TO.md)** for step-by-step feature development

## Development Commands

```bash
# Tests
composer test              # Run all tests
composer test:unit         # Unit tests only
composer test:feature       # Feature tests only
composer test:coverage      # With coverage report

# Code Quality
composer analyse           # PHPStan static analysis
composer format            # Auto-fix code style
composer format:check      # Check style (dry-run)
composer quality          # Full quality check

# JavaScript
npm run lint:js           # Lint JavaScript files
npm run lint:all          # Lint all JS
npm run dev:css           # Watch Tailwind CSS
npm run build:css         # Production CSS build
```

## Getting Help

- **Issues with setup?** Check [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)
- **General questions?** See [FAQ.md](./FAQ.md)
- **Need architecture overview?** Read [ARCHITECTURE.md](./ARCHITECTURE.md)
- **Want to add a feature?** Follow [HOW-TO.md](./HOW-TO.md)

## Environment Variables Reference

| Variable | Purpose | Required | Default |
|----------|---------|----------|---------|
| `CI_ENVIRONMENT` | App environment | Yes | `development` |
| `app.baseURL` | Frontend public URL | Yes | `http://localhost:8182/` |
| `apiClient.baseUrl` | Backend API URL | Yes | `http://localhost:8180` |
| `apiClient.apiPrefix` | API prefix | No | `/api/v1` |
| `GOOGLE_CLIENT_ID` | Google OAuth client ID | No | — |
| `FILE_MAX_SIZE` | Max upload size (bytes) | No | `10485760` (10 MB) |
| `apiClient.appKey` | API app key for rate limiting | No | — |

See `.env.example` or the [DEPLOYMENT.md](./DEPLOYMENT.md) guide for complete reference.

---

**✅ Ready to develop?** You're all set! Start building features and refer to the documentation hub as needed.

**Questions?** Check [FAQ.md](./FAQ.md) or [TROUBLESHOOTING.md](./TROUBLESHOOTING.md).
