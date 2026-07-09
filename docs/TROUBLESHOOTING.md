# Troubleshooting Guide

Common issues and solutions for **CI4 Admin Starter**.

## Setup & Installation

### "Command not found: composer" or "Command not found: php"

**Problem:** Your system can't find PHP or Composer in the PATH.

**Solution:**
1. Verify installation:
   ```bash
   which php          # Should return path to PHP
   which composer     # Should return path to Composer
   ```
2. If not found, install or add to PATH:
   - **macOS:** Use Homebrew: `brew install php composer`
   - **Linux:** Use your package manager: `apt-get install php composer`
   - **Windows:** Download from [php.net](https://www.php.net/downloads) and [getcomposer.org](https://getcomposer.org/download/)
3. Restart your terminal and try again

### "Install script failed with error code X"

**Problem:** The `bash install.sh` script encountered an error.

**Solution:**
1. Run the manual setup instead:
   ```bash
   composer install
   npm install
   cp env .env
   ```
2. Edit `.env` manually with your configuration
3. Check the script output for specific errors
4. If still failing, check file permissions: `chmod +x install.sh`

### ".env file not found or not writable"

**Problem:** Can't create or edit `.env` file.

**Solution:**
```bash
# Copy the template manually
cp env .env

# If permission error, check folder permissions
ls -la .env

# Fix if needed
chmod 644 .env

# Verify you can edit it
nano .env  # Edit as needed
```

---

## Development Server Issues

### "Port 8182 already in use" or "Address already in use"

**Problem:** Another process is using the port.

**Solution:**

**macOS/Linux:**
```bash
# Find what's using the port
lsof -i :8182

# Kill the process (replace 12345 with PID from above)
kill -9 12345

# Or use a different port
php spark serve --port 8083
```

**Windows (PowerShell):**
```powershell
# Find process using port
Get-Process -Id (Get-NetTCPConnection -LocalPort 8182).OwningProcess

# Kill it by PID
Stop-Process -Id 12345 -Force

# Or use different port
php spark serve --port 8083
```

### "Connection refused" when accessing http://localhost:8182

**Problem:** Server is not responding.

**Solution:**
1. Check the PHP server is still running:
   ```bash
   # In the terminal, you should see:
   # Server running on http://localhost:8182
   ```
2. If not running, start it: `php spark serve --port 8182`
3. Check if it's a firewall issue:
   - Try `curl http://localhost:8182` from another terminal
   - If curl works but browser doesn't, check browser cache
4. Hard refresh browser: `Cmd+Shift+R` (Mac) or `Ctrl+Shift+R` (Windows/Linux)

### "PHP Fatal error: Class 'Phar' not found"

**Problem:** PHP is missing the Phar extension.

**Solution:**
```bash
# Check if Phar is enabled
php -m | grep -i phar

# If not listed, install it
# macOS with Homebrew
brew install php

# Linux (example for Ubuntu)
sudo apt-get install php-phar

# Windows: Enable in php.ini
# Uncomment: extension=phar.so (Unix) or extension=php_phar.dll (Windows)
```

---

## CSS & Frontend Issues

### "Styles not applied" or "Page looks broken"

**Problem:** No colors, missing layout, broken design.

**Causes & Solutions:**

1. **CSS not built:**
   ```bash
   # Stop the CSS watcher
   # (Ctrl+C in the npm terminal)
   
   # Rebuild CSS
   npm run build:css
   
   # Restart watcher
   npm run dev:css
   ```

2. **CSS file not being served:**
   - Check browser DevTools (F12) → Network tab
   - Look for `public/assets/css/app.css`
   - If 404 error, file doesn't exist: rebuild with `npm run build:css`

3. **Browser cache:**
   ```bash
   # Hard refresh
   Mac: Cmd + Shift + R
   Windows/Linux: Ctrl + Shift + R
   
   # Or clear browser cache entirely
   # Open DevTools → Settings → Clear cache on page reload
   ```

4. **Tailwind configuration issue:**
   - Check `app/Views/layouts/partials/head.php` exists
   - Verify CSS custom properties are defined
   - Check `src/css/app.css` `@theme` block and `@source` directives (Tailwind v4 — no more `tailwind.config.js`)

### "Alpine.js not working" or "No interactivity"

**Problem:** Dropdowns, modals, or interactive elements don't work.

**Solution:**
1. Verify Alpine.js is loaded:
   ```bash
   # In browser DevTools console, run:
   console.log(Alpine)  # Should print Alpine object
   ```
2. Check CDN is accessible:
   - Go to `app/Views/layouts/partials/head.php`
   - Verify Alpine CDN URL is not blocked
   - Check DevTools → Network tab for Alpine.js load status
3. Hard refresh browser: `Cmd+Shift+R` or `Ctrl+Shift+R`
4. Check browser console for errors (F12)

### "Icons not displaying" (Lucide Icons)

**Problem:** Icons show as empty boxes or squares.

**Solution:**
1. Check Lucide CDN in `app/Views/layouts/partials/head.php`
2. Verify CDN is accessible (DevTools → Network)
3. Check that icon names match Lucide's naming:
   - Use lowercase with hyphens: `chevron-down`, `menu-circle`, etc.
   - Reference: https://lucide.dev/icons/
4. Verify HTML is using correct icon syntax:
   ```html
   <svg class="lucide-icon" data-icon="chevron-down"></svg>
   ```

---

## Backend API Issues

### "All API requests return 401 Unauthorized"

**Problem:** Cannot authenticate or every request fails with 401.

**Causes & Solutions:**

1. **Backend not running:**
   ```bash
   # Start backend API (in separate project)
   cd ../ci4-api-starter
   php spark serve --port 8180
   ```

2. **Wrong API URL in `.env`:**
   ```bash
   # Check in .env
   apiClient.baseUrl = 'http://localhost:8180'  # Should match backend port
   ```
   - Restart frontend server after changing: `php spark serve --port 8182`

3. **Invalid API key:**
   ```bash
   # Check in .env
   # If apiClient.appKey is set and WRONG, all requests return 401
   # Solution: Either fix the key or remove it entirely
   
   # Remove the line (or comment it out):
   # apiClient.appKey = apk_xxxx
   ```

4. **CORS issues:**
   - Check backend allows requests from frontend URL
   - Frontend URL: `http://localhost:8182`
   - Backend should have CORS headers configured

5. **Session/Token expired:**
   - Try logging in again
   - Check session configuration in `app/Config/Session.php`

### "Connection timeout" or "Backend slow"

**Problem:** API requests take too long or timeout.

**Solution:**
1. Check backend is running and healthy:
   ```bash
   curl http://localhost:8180/api/v1/health
   # Should return JSON response
   ```

2. Check network connectivity:
   ```bash
   ping localhost  # Windows/Linux
   ping 127.0.0.1  # macOS
   ```

3. Increase timeout in `app/Config/ApiClient.php`:
   ```php
   public float $timeout = 10.0;  // Increase from default
   ```

4. Check backend logs for errors
5. Monitor CPU/memory on backend server

### "CORS errors" or "Request blocked"

**Problem:** Browser blocks API requests with CORS error.

**Solution:**
1. Check browser console for exact CORS error message
2. Verify backend has CORS headers for your frontend domain
3. Check `app/Config/ApiClient.php` timeout settings
4. Ensure `apiClient.baseUrl` doesn't have trailing slash:
   ```
   ❌ Wrong:  apiClient.baseUrl = 'http://localhost:8180/'
   ✅ Correct: apiClient.baseUrl = 'http://localhost:8180'
   ```

---

## Authentication & Session Issues

### "Session cookies not being set"

**Problem:** Login doesn't persist; you're logged out after refresh.

**Solution:**
1. Check session configuration in `app/Config/Session.php`:
   ```php
   public string $driver = 'database';  // Or 'files', depending on your setup
   ```

2. For `database` driver, ensure `sessions` table exists:
   ```bash
   php spark migrate
   ```

3. Check cookie settings in `.env`:
   ```dotenv
   # For development:
   # cookie.secure = false
   # For production HTTPS:
   # cookie.secure = true
   ```

4. Verify session driver is writable:
   - If using `files`: check `writable/session/` permissions
   - If using `database`: check database connection in `app/Config/Database.php`

### "Keep getting redirected to login"

**Problem:** Even after login, redirected back to login page.

**Solution:**
1. Check `AuthFilter` in `app/Filters/AuthFilter.php`:
   - Ensures `access_token` exists in session
   - If token missing, redirects to login

2. Verify backend login endpoint works:
   ```bash
   curl -X POST http://localhost:8180/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"test@example.com","password":"password"}'
   ```

3. Check session storage:
   ```php
   // In a controller or route
   dd(session()->all());  // Dumps all session data
   ```

4. Check token expiration:
   ```php
   dd(session('token_expires_at'));  // Should be a future timestamp
   ```

---

## Testing Issues

### "Tests fail with 'ApiClient not found'"

**Problem:** PHPUnit tests can't find ApiClient or its mock.

**Solution:**
1. Ensure `app/Config/Services.php` has the mock:
   ```php
   public static function apiClient(...) {
       // Service factory
   }
   ```

2. Check test namespace matches:
   ```php
   namespace Tests\Unit;  // Must match test file location
   ```

3. Run Composer autoload update:
   ```bash
   composer dumpautoload
   ```

4. Check test file extends correct base class:
   ```php
   use Tests\Support\TestCase;
   class MyTest extends TestCase { ... }
   ```

### "Database tests fail" or "Table not found"

**Problem:** Feature tests can't find database tables.

**Solution:**
1. Check test database is configured in `phpunit.xml`:
   ```xml
   <php>
       <env name="database.tests.DBDriver" value="SQLite"/>
       <env name="database.tests.DBDatabase" value=":memory:"/>
   </php>
   ```

2. Run migrations before tests:
   ```bash
   php spark migrate --database tests
   ```

3. Check `DatabaseTestCase` runs migrations:
   ```php
   class MyFeatureTest extends FeatureTestCase {
       public function setUp(): void {
           parent::setUp();
           // Migrations should run here
       }
   }
   ```

### "Test coverage report not generated"

**Problem:** `tests/coverage/` directory doesn't exist after running tests.

**Solution:**
1. Check Xdebug is installed:
   ```bash
   php -m | grep Xdebug
   ```
   If not installed:
   ```bash
   # macOS with Homebrew
   brew install php-xdebug
   
   # Linux
   sudo apt-get install php-xdebug
   ```

2. Run tests with coverage:
   ```bash
   composer test:coverage
   # or
   vendor/bin/phpunit --coverage-html=tests/coverage/
   ```

3. Check coverage directory permissions:
   ```bash
   chmod -R 755 tests/coverage/
   ```

---

## Code Quality Issues

### "PHPStan errors" or "Code style issues"

**Problem:** `composer analyse` or `composer format:check` fails.

**Solution:**
1. Run analysis with verbose output:
   ```bash
   vendor/bin/phpstan analyse --debug
   ```

2. Auto-fix style issues:
   ```bash
   vendor/bin/php-cs-fixer fix
   ```

3. View what would be fixed without applying:
   ```bash
   vendor/bin/php-cs-fixer fix --dry-run --diff
   ```

4. Update PHPStan baseline if new issues are expected:
   ```bash
   vendor/bin/phpstan analyse --generate-baseline
   ```

### "Lint errors in JavaScript"

**Problem:** `npm run lint:js` reports errors.

**Solution:**
1. Check which files have errors:
   ```bash
   npm run lint:js --verbose
   ```

2. Auto-fix JavaScript:
   ```bash
   npx eslint --fix public/assets/js/
   ```

3. Check ESLint config in project root (`.eslintrc.json`)

---

## File Upload Issues

### "File upload fails" or "File too large"

**Problem:** Uploads don't work or get rejected.

**Solution:**
1. Check max file size setting:
   ```bash
   # In .env
   FILE_MAX_SIZE = 10485760  # 10 MB
   ```

2. Verify PHP upload limits in `php.ini`:
   ```
   upload_max_filesize = 20M
   post_max_size = 20M
   ```
   Restart PHP server after changing.

3. Check `writable/uploads/` directory:
   ```bash
   # Ensure it exists and is writable
   mkdir -p writable/uploads
   chmod 755 writable/uploads
   ```

4. Test upload endpoint directly:
   ```bash
   curl -F "file=@/path/to/test.pdf" http://localhost:8182/files/upload
   ```

---

## Performance Issues

### "Page loads slowly" or "High memory usage"

**Problem:** Application is sluggish or uses too much memory.

**Solution:**
1. Check database queries (if using database session storage):
   - Enable query logging in `app/Config/Database.php`
   - Check logs for slow queries

2. Enable caching in production:
   - `app/Config/Cache.php` should use Redis or Memcached
   - Not file-based in production

3. Profile with Xdebug:
   - Enable profiling in `php.ini`
   - Analyze with KCachegrind or similar

4. Check API response time:
   ```bash
   curl -w "\nTotal time: %{time_total}s\n" http://localhost:8180/api/v1/users
   ```

5. Monitor resources:
   ```bash
   # macOS
   top
   
   # Linux
   htop
   ```

---

## Permission & File Issues

### "Permission denied" errors

**Problem:** Can't write files or access directories.

**Solution:**
```bash
# Fix directory permissions (usually needed for writable/)
chmod -R 775 writable/
chmod -R 775 public/assets/

# If still having issues, check owner
ls -la writable/ | head

# Change owner if needed (use your web server user)
chown -R www-data:www-data writable/  # Linux
chown -R _www:_www writable/          # macOS
```

### ".env file not created or permissions issue"

**Problem:** Can't read or write to `.env`.

**Solution:**
```bash
# Verify .env exists
ls -la .env

# Check permissions
chmod 644 .env

# If missing, copy from template
cp env .env

# Verify you can read it
cat .env | head
```

---

## General Debugging Tips

### Enable verbose logging

Edit `app/Config/Logger.php` to log more details:
```php
public array $handlers = [
    'CodeIgniter\Logs\Handlers\FileHandler' => [
        'level' => CRITICAL,  // Change to DEBUG for verbose
    ],
];
```

Then check logs:
```bash
tail -f writable/logs/log-*.log
```

### Use browser developer tools

- **Network tab:** Check API requests and responses
- **Console tab:** Look for JavaScript errors
- **Application tab:** Check cookies and session storage
- **Storage tab:** Verify no sensitive data in localStorage

### Debug with dd() function

CodeIgniter's `dd()` function dumps and dies:
```php
// In any controller or route
dd($variable);  // Dumps $variable and stops execution
dd(session()->all());  // Dumps all session data
```

### Check configuration values

```php
// In routes or controller
echo config('App')->appName;
echo config('ApiClient')->baseUrl;
```

---

## Still Having Issues?

1. Check the [FAQ.md](./FAQ.md) for other common questions
2. Review [QUICK-START.md](./QUICK-START.md) to verify your setup
3. Check project logs: `tail -f writable/logs/log-*.log`
4. Review browser console (F12) for JavaScript errors
5. Check backend logs if API issues
6. Open an issue: https://github.com/dcardenasl/ci4-admin-starter/issues

---

**Remember:** Most issues stem from either:
1. Backend API not running or misconfigured
2. CSS/JavaScript not built (run `npm run build:css`)
3. Session/permission issues (check `writable/` directory)
4. Port conflicts (another service using 8182 or 8180)
