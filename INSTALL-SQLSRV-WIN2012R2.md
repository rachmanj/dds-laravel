# Installing SQL Server PHP Extension on Windows Server 2012 R2

## Production Server Console Commands

Run these commands directly on your Windows Server 2012 R2 production server:

### Step 1: Check Current Status

```powershell
# Check PHP version and configuration
php -v

# Check if sqlsrv extension is installed
php -m | findstr sqlsrv

# Find PHP extension directory
php -i | findstr /i "extension_dir"

# Find php.ini location
php --ini
```

**Note the output** - you'll need:
- PHP version (e.g., 8.2.12)
- Thread Safety (ZTS or NTS)
- Extension directory path (e.g., `C:\xampp\php\ext`)
- php.ini path (e.g., `C:\xampp\php\php.ini`)

### Step 2: Install Microsoft ODBC Driver

**For Windows Server 2012 R2, use ODBC Driver 17** (better compatibility):

```powershell
# Check if ODBC Driver is already installed
Get-OdbcDriver | Where-Object {$_.Name -like "*SQL Server*"}
```

**If not installed:**

1. Download ODBC Driver 17 for SQL Server:
   - Direct link: https://go.microsoft.com/fwlink/?linkid=2230791
   - Or search: "Microsoft ODBC Driver 17 for SQL Server Windows x64"
   - Download the `.msi` file

2. Install the driver:
   - Run the downloaded `.msi` file
   - Follow installation wizard
   - Restart if prompted

3. Verify installation:
   ```powershell
   Get-OdbcDriver | Where-Object {$_.Name -like "*SQL Server*"}
   ```
   Should show: `ODBC Driver 17 for SQL Server`

### Step 3: Install Visual C++ Redistributable (Required)

Windows Server 2012 R2 may need Visual C++ Redistributable:

```powershell
# Download and install Visual C++ Redistributable 2015-2022
# Direct link: https://aka.ms/vs/17/release/vc_redist.x64.exe
# Or search: "Visual C++ Redistributable 2015-2022 x64"
```

### Step 4: Download PHP sqlsrv Extension

1. Go to: https://github.com/Microsoft/msphpsql/releases
2. Download the ZIP file matching your PHP version:
   - For PHP 8.2 ZTS x64: `Windows-8.2.zip`
   - For PHP 8.1 ZTS x64: `Windows-8.1.zip`
   - Extract the ZIP file

3. **If you encounter compatibility issues**, try an older stable version:
   - Version 5.11.1: https://github.com/Microsoft/msphpsql/releases/tag/v5.11.1
   - Version 5.10.1: https://github.com/Microsoft/msphpsql/releases/tag/v5.10.1

### Step 5: Copy Extension Files

```powershell
# Navigate to extracted folder (adjust path)
cd C:\path\to\extracted\folder

# Find your PHP extension directory (from Step 1)
# Example: C:\xampp\php\ext

# Copy DLL files (adjust paths based on your setup)
copy "php_sqlsrv_82_ts_x64.dll" "C:\xampp\php\ext\php_sqlsrv.dll"
copy "php_pdo_sqlsrv_82_ts_x64.dll" "C:\xampp\php\ext\php_pdo_sqlsrv.dll"
```

**Or manually:**
- Copy `php_sqlsrv_82_ts_x64.dll` from extracted folder
- Rename to `php_sqlsrv.dll`
- Place in PHP `ext` directory (from Step 1)
- Copy `php_pdo_sqlsrv_82_ts_x64.dll` from extracted folder
- Rename to `php_pdo_sqlsrv.dll`
- Place in PHP `ext` directory

### Step 6: Edit php.ini

```powershell
# Open php.ini in notepad (as Administrator)
# Use the path from Step 1
notepad "C:\xampp\php\php.ini"
```

**In php.ini:**
1. Find the section with other `extension=` lines
2. Add these two lines:
   ```ini
   extension=php_sqlsrv
   extension=php_pdo_sqlsrv
   ```
3. Save and close

### Step 7: Restart Web Server

```powershell
# For XAMPP: Use XAMPP Control Panel to restart Apache

# For IIS:
iisreset

# For Windows Service:
net stop w3svc
net start w3svc
```

### Step 8: Verify Installation

```powershell
# Check if extension is loaded
php -m | findstr sqlsrv
```

**Expected output:**
```
pdo_sqlsrv
sqlsrv
```

### Step 9: Test Connection

```powershell
# Navigate to your Laravel project directory
cd C:\xampp\htdocs\dds

# Run the test command
php artisan sap:test-sql-connection --date=2025-11-13
```

**Expected output:**
```
✓ sqlsrv extension is loaded
✓ Connection successful
✓ Query executed successfully
Found 34 records (or more)
```

## Troubleshooting for Windows Server 2012 R2

### Error: "Unable to load dynamic library 'php_sqlsrv'"

**Solutions:**
1. Install Visual C++ Redistributable 2015-2022 (Step 3)
2. Verify DLL version matches PHP (ZTS vs NTS, x64 vs x86)
3. Check DLL files are in correct `ext` directory
4. Try older sqlsrv version (5.11.1 or 5.10.1)

### Error: "The specified DSN contains an architecture mismatch"

**Solution:**
- Ensure PHP and ODBC Driver are both x64 (64-bit)
- Check: `php -i | findstr "Architecture"`

### Error: "SQLSTATE[08001]: SSL Provider error"

**Solution:**
- Connection config already has `TrustServerCertificate => true`
- If still fails, update Windows Server with latest security patches

### Error: "Driver not found"

**Solution:**
- Install ODBC Driver 17 (better for Windows Server 2012 R2)
- Verify: `Get-OdbcDriver | Where-Object {$_.Name -like "*SQL Server*"}`

## Quick Reference Commands

```powershell
# 1. Check status
php -v
php -m | findstr sqlsrv
php --ini

# 2. Check ODBC Driver
Get-OdbcDriver | Where-Object {$_.Name -like "*SQL Server*"}

# 3. After installation, verify
php -m | findstr sqlsrv

# 4. Test connection
cd C:\xampp\htdocs\dds
php artisan sap:test-sql-connection --date=2025-11-13
```

## Download Links Summary

- **ODBC Driver 17**: https://go.microsoft.com/fwlink/?linkid=2230791
- **Visual C++ Redistributable**: https://aka.ms/vs/17/release/vc_redist.x64.exe
- **PHP sqlsrv Extension**: https://github.com/Microsoft/msphpsql/releases
- **Older sqlsrv (if needed)**: https://github.com/Microsoft/msphpsql/releases/tag/v5.11.1

## Notes for Windows Server 2012 R2

1. **Use ODBC Driver 17** instead of 18 for better compatibility
2. **Install Visual C++ Redistributable** - required for sqlsrv extension
3. **If latest version fails**, try sqlsrv version 5.11.1 or 5.10.1
4. **Restart web server** after installation
5. **Run as Administrator** when editing php.ini

