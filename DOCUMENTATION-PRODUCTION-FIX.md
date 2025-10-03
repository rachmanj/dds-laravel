# Production Server Fix for Processing Analytics

## Issue

Production server shows "Not Found" error for `/processing-analytics` route, but works with `/dds/processing-analytics`.

## Root Cause

The Laravel application is deployed in a subdirectory `/dds/` on the production server instead of the document root.

## Solutions

### Option 1: Apache Configuration (Recommended)

Update the Apache virtual host configuration to point directly to the Laravel public folder:

```apache
# For document root deployment
<VirtualHost *:80>
    ServerName 192.168.32.30
    DocumentRoot /path/to/dds-laravel/public

    <Directory "/path/to/dds-laravel/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# OR for subdirectory deployment with proper alias
<VirtualHost *:80>
    ServerName 192.168.32.30
    DocumentRoot /var/www/html

    Alias /dds /path/to/dds-laravel/public
    <Directory "/path/to/dds-laravel/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Option 2: Environment Configuration

If subdirectory deployment is required, update `.env` file:

```env
APP_URL=http://192.168.32.30/dds
ASSET_URL=/dds
```

### Option 3: Update Routes (Last Resort)

If configuration changes are not possible, we can update the routes to match the subdirectory structure:

```php
// In routes/web.php
Route::prefix('dds')->middleware(['auth', 'active.user'])->group(function () {
    Route::get('/processing-analytics', function () {
        return view('processing-analytics.index');
    })->name('processing-analytics.index');
});
```

## Current Status

✅ Routes are correctly configured in Laravel
✅ Frontend integration works properly
⚠️ Production server needs Apache configuration update

## Recommendation

Update Apache virtual host configuration (Option 1) for proper deployment.
