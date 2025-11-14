# Installing SQL Server PHP Extension

## Windows Server 2012 R2, 64-bit, PHP 8.2

## Production Server Console Commands

### Step 1: Check Current Status

```powershell
# Check PHP version (should show 8.2.x)
php -v

# Check if sqlsrv is already installed
php -m | findstr sqlsrv

# Find PHP extension directory
php -i | findstr /i "extension_dir"

# Find php.ini location
php --ini
```

**Note the output** - you'll need:

-   Extension directory path (e.g., `C:\xampp\php\ext`)
-   php.ini path (e.g., `C:\xampp\php\php.ini`)

### Step 2: Install Microsoft ODBC Driver 17

**For Windows Server 2012 R2, use ODBC Driver 17** (better compatibility than Driver 18):

```powershell
# Check if ODBC Driver is already installed
Get-OdbcDriver | Where-Object {$_.Name -like "*SQL Server*"}
```

**If not installed:**

1. **Download ODBC Driver 17 for SQL Server (x64)**:

    - Direct link: https://go.microsoft.com/fwlink/?linkid=2230791
    - Or search: "Microsoft ODBC Driver 17 for SQL Server Windows x64"
    - Download the `.msi` file

2. **Install the driver**:

    - Run the downloaded `.msi` file
    - Follow installation wizard
    - Restart if prompted

3. **Verify installation**:
    ```powershell
    Get-OdbcDriver | Where-Object {$_.Name -like "*SQL Server*"}
    ```
    Should show: `ODBC Driver 17 for SQL Server`

### Step 3: Install Visual C++ Redistributable (Required)

**Download and install**:

-   Direct link: https://aka.ms/vs/17/release/vc_redist.x64.exe
-   Or search: "Visual C++ Redistributable 2015-2022 x64"
-   Run the installer
-   Restart if prompted

### Step 4: Download PHP sqlsrv Extension for PHP 8.2

**IMPORTANT**: Your PHP is **NTS (Non-Thread Safe)**, not ZTS!

1. **Go to**: https://github.com/Microsoft/msphpsql/releases

2. **Download**: `Windows-8.2.zip` (for PHP 8.2)

    - Look for the latest release
    - Download the ZIP file

3. **Extract the ZIP file** to a temporary folder

4. **Find these files** in the extracted folder (look for **NTS**, not TS):

    - `php_sqlsrv_82_nts_x64.dll` ← **NTS version** (not ts_x64)
    - `php_pdo_sqlsrv_82_nts_x64.dll` ← **NTS version** (not ts_x64)

    **Note**: The folder may contain both TS and NTS versions. Make sure you use the **NTS** files!

**If you encounter compatibility issues**, try an older stable version:

-   Version 5.11.1: https://github.com/Microsoft/msphpsql/releases/tag/v5.11.1
-   Download: `Windows-8.2.zip` from that release

### Step 5: Copy Extension Files to PHP Extension Directory

**Your PHP extension directory**: Based on your output, it's `ext` (relative) or `C:\tools\php82\ext`

```powershell
# Get your PHP extension directory (from Step 1)
# Your php.ini is at: C:\tools\php82\php.ini
# So extension directory is likely: C:\tools\php82\ext

# Copy DLL files (adjust paths based on your setup)
# Replace C:\path\to\extracted with your actual extracted folder path
copy "C:\path\to\extracted\php_sqlsrv_82_nts_x64.dll" "C:\tools\php82\ext\php_sqlsrv.dll"
copy "C:\path\to\extracted\php_pdo_sqlsrv_82_nts_x64.dll" "C:\tools\php82\ext\php_pdo_sqlsrv.dll"
```

**Or manually:**

1. Navigate to extracted folder
2. Copy `php_sqlsrv_82_nts_x64.dll` ← **NTS version**
3. Rename to `php_sqlsrv.dll`
4. Place in PHP `ext` directory: `C:\tools\php82\ext`
5. Copy `php_pdo_sqlsrv_82_nts_x64.dll` ← **NTS version**
6. Rename to `php_pdo_sqlsrv.dll`
7. Place in PHP `ext` directory: `C:\tools\php82\ext`

### Step 6: Edit php.ini

```powershell
# Open php.ini in notepad (Run as Administrator)
# Your php.ini is at: C:\tools\php82\php.ini
notepad "C:\tools\php82\php.ini"
```

**In php.ini:**

1. Find the section with other `extension=` lines (usually near the top)
2. Add these two lines:
    ```ini
    extension=php_sqlsrv
    extension=php_pdo_sqlsrv
    ```
3. Save and close

### Step 7: Restart Web Server

```powershell
# For XAMPP: Use XAMPP Control Panel to stop and start Apache

# For IIS:
iisreset

# For Windows Service:
net stop w3svc
net start w3svc
```

### Step 8: Verify Extension is Loaded

```powershell
# Check if extension is loaded
php -m | findstr sqlsrv
```

**Expected output:**

```
pdo_sqlsrv
sqlsrv
```

If you see this, the extension is installed correctly!

### Step 9: Test SQL Server Connection

```powershell
# Navigate to your Laravel project directory
cd C:\xampp\htdocs\dds

# Run the test command
php artisan sap:test-sql-connection --date=2025-11-13
```

**Expected output:**

```
=== SQL Server Connection Test ===

1. PHP Extension Check:
   ✓ sqlsrv extension is loaded (version: X.X.X)
   ✓ pdo_sqlsrv extension is loaded (version: X.X.X)

2. Database Configuration:
   Host: arkasrv2
   Database: SBO_AAP_NEW
   ...

3. Connection Test:
   ✓ Connection successful
   Server version: XX.XX.XXXX

4. Simple Query Test:
   ✓ Query executed successfully

5. ITO Query Test (Date: 2025-11-13):
   Count query result: 34 records
   Full query result: 34 records
   ✓ Counts match
   ✓ Matches expected count (34 records for 2025-11-13)
```

## Troubleshooting

### Error: "Unable to load dynamic library 'php_sqlsrv'"

**Solutions:**

1. **Install Visual C++ Redistributable** (Step 3) - This is often the cause
2. **Verify DLL version matches PHP**:
    - PHP 8.2.0 **NTS** x64 → Use `php_sqlsrv_82_nts_x64.dll` ← **NTS, not TS!**
    - Your PHP shows: `(NTS Visual C++ 2019 x64)` - so you need **NTS** version
    - Check: `php -v` shows "NTS" for Non-Thread Safe
3. **Check DLL files are in correct directory**:
    ```powershell
    dir "C:\xampp\php\ext\php_sqlsrv.dll"
    dir "C:\xampp\php\ext\php_pdo_sqlsrv.dll"
    ```
4. **Try older sqlsrv version** (5.11.1) if latest doesn't work

### Error: "Driver not found"

**Solution:**

-   Install ODBC Driver 17 (Step 2)
-   Verify: `Get-OdbcDriver | Where-Object {$_.Name -like "*SQL Server*"}`

### Error: "The specified DSN contains an architecture mismatch"

**Solution:**

-   Ensure both PHP and ODBC Driver are x64 (64-bit)
-   Check: `php -i | findstr "Architecture"` should show "x64"

### Still shows only 3 records after installation?

**Possible causes:**

1. Different database/company code in production
2. Date timezone mismatch
3. Different data in production database

**Check:**

```powershell
php artisan sap:test-sql-connection --date=2025-11-13
```

This will show detailed diagnostics.

## Quick Reference - All Commands

```powershell
# 1. Check status
php -v
php -m | findstr sqlsrv
php --ini
php -i | findstr /i "extension_dir"

# 2. Check ODBC Driver
Get-OdbcDriver | Where-Object {$_.Name -like "*SQL Server*"}

# 3. After installation, verify
php -m | findstr sqlsrv

# 4. Test connection
cd C:\xampp\htdocs\dds
php artisan sap:test-sql-connection --date=2025-11-13
```

## Download Links Summary

-   **ODBC Driver 17 (x64)**: https://go.microsoft.com/fwlink/?linkid=2230791
-   **Visual C++ Redistributable (x64)**: https://aka.ms/vs/17/release/vc_redist.x64.exe
-   **PHP sqlsrv Extension (PHP 8.2)**: https://github.com/Microsoft/msphpsql/releases
-   **Older sqlsrv (if needed)**: https://github.com/Microsoft/msphpsql/releases/tag/v5.11.1

## Installation Checklist

-   [ ] Checked PHP version (8.2.x)
-   [ ] Installed ODBC Driver 17 for SQL Server
-   [ ] Installed Visual C++ Redistributable 2015-2022
-   [ ] Downloaded Windows-8.2.zip from msphpsql releases
-   [ ] Copied php_sqlsrv.dll to PHP ext directory
-   [ ] Copied php_pdo_sqlsrv.dll to PHP ext directory
-   [ ] Added extension lines to php.ini
-   [ ] Restarted web server
-   [ ] Verified extension loaded: `php -m | findstr sqlsrv`
-   [ ] Tested connection: `php artisan sap:test-sql-connection`

## Expected Final Result

After successful installation:

-   ✓ sqlsrv extension is loaded
-   ✓ Connection successful
-   ✓ Query executed successfully
-   **34 records** (or more) for 2025-11-13 instead of just 3

This means the app is now using direct SQL Server queries instead of falling back to OData!
