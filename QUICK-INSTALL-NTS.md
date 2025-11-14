# Quick Install Guide - PHP 8.2.0 NTS x64
## Windows Server 2012 R2 Production Server

## Your Server Info
- **PHP**: 8.2.0 **NTS** (Non-Thread Safe) x64
- **php.ini**: `C:\tools\php82\php.ini`
- **Extension dir**: `C:\tools\php82\ext`
- **ODBC Drivers**: ✅ Already installed (32-bit and 64-bit)

## Critical: Use NTS Version!

Your PHP is **NTS (Non-Thread Safe)**, so you need:
- `php_sqlsrv_82_nts_x64.dll` ← **NTS** (not ts_x64)
- `php_pdo_sqlsrv_82_nts_x64.dll` ← **NTS** (not ts_x64)

## Installation Steps

### Step 1: Install Visual C++ Redistributable
- Download: https://aka.ms/vs/17/release/vc_redist.x64.exe
- Install and restart if needed

### Step 2: Download PHP sqlsrv Extension
- Go to: https://github.com/Microsoft/msphpsql/releases
- Download: `Windows-8.2.zip`
- Extract the ZIP file

### Step 3: Copy NTS DLL Files

```powershell
# Copy NTS version (not TS version!)
copy "C:\path\to\extracted\php_sqlsrv_82_nts_x64.dll" "C:\tools\php82\ext\php_sqlsrv.dll"
copy "C:\path\to\extracted\php_pdo_sqlsrv_82_nts_x64.dll" "C:\tools\php82\ext\php_pdo_sqlsrv.dll"
```

**Important**: Make sure you use files with `_nts_x64` in the name, NOT `_ts_x64`!

### Step 4: Edit php.ini

```powershell
notepad "C:\tools\php82\php.ini"
```

Add these lines (near other `extension=` lines):
```ini
extension=php_sqlsrv
extension=php_pdo_sqlsrv
```

Save and close.

### Step 5: Restart Web Server

```powershell
# For IIS:
iisreset

# Or restart your web server service
```

### Step 6: Verify

```powershell
php -m | findstr sqlsrv
```

Should show:
```
pdo_sqlsrv
sqlsrv
```

### Step 7: Test Connection

```powershell
cd C:\xampp\htdocs\dds
php artisan sap:test-sql-connection --date=2025-11-13
```

## Common Mistake

❌ **Wrong**: Using `php_sqlsrv_82_ts_x64.dll` (TS = Thread Safe)
✅ **Correct**: Using `php_sqlsrv_82_nts_x64.dll` (NTS = Non-Thread Safe)

Your PHP shows `(NTS Visual C++ 2019 x64)`, so you MUST use NTS version!

## Quick Commands

```powershell
# 1. Check PHP version (should show NTS)
php -v

# 2. Check extension directory
php -i | findstr /i "extension_dir"

# 3. After copying files, verify they exist
dir "C:\tools\php82\ext\php_sqlsrv.dll"
dir "C:\tools\php82\ext\php_pdo_sqlsrv.dll"

# 4. Check if loaded
php -m | findstr sqlsrv

# 5. Test connection
cd C:\xampp\htdocs\dds
php artisan sap:test-sql-connection --date=2025-11-13
```

