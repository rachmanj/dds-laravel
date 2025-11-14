# Installing SQL Server PHP Extension on Production Server

## Server Information

-   **OS**: Windows Server 2012 R2
-   **Issue**: `sqlsrv` extension is NOT loaded

## Quick Diagnosis

Your production server shows:

```
✗ sqlsrv extension is NOT loaded
```

This means the SQL Server connection cannot work, and the app is likely falling back to OData (which returns fewer records).

**Note for Windows Server 2012 R2**: Some newer driver versions may not be fully compatible. We'll use compatible versions.

## Installation Steps

### Step 1: Check PHP Version on Production

Run on production server:

```powershell
php -v
```

You need to know:

-   PHP version (e.g., 8.2.12)
-   Architecture (x64 or x86)
-   Thread Safety (ZTS or NTS)

### Step 2: Install Microsoft ODBC Driver

**For Windows Server 2012 R2**, you have two options:

#### Option A: ODBC Driver 17 (Recommended for Windows Server 2012 R2)

1. **Download Microsoft ODBC Driver 17 for SQL Server**:

    - Direct link: https://go.microsoft.com/fwlink/?linkid=2230791
    - Or search: "Microsoft ODBC Driver 17 for SQL Server Windows"
    - Choose: **Windows x64** version
    - This version has better compatibility with Windows Server 2012 R2

2. **Run the installer** and follow the wizard

#### Option B: ODBC Driver 18 (If Driver 17 doesn't work)

1. **Download Microsoft ODBC Driver 18 for SQL Server**:

    - https://go.microsoft.com/fwlink/?linkid=2249004
    - Choose: **Windows x64** version
    - Note: May require additional dependencies on older Windows versions

2. **Verify installation**:

    ```powershell
    Get-OdbcDriver | Where-Object {$_.Name -like "*SQL Server*"}
    ```

    You should see either:

    - `ODBC Driver 17 for SQL Server` (recommended)
    - `ODBC Driver 18 for SQL Server`

### Step 3: Download PHP sqlsrv Extension

1. **Go to**: https://github.com/Microsoft/msphpsql/releases
2. **Download the correct version** based on your PHP:
    - For PHP 8.2 ZTS x64: Look for `8.2 ZTS x64` or download `Windows-8.2.zip`
    - For PHP 8.1 ZTS x64: Look for `8.1 ZTS x64` or download `Windows-8.1.zip`
    - Files needed:
        - `php_sqlsrv_82_ts_x64.dll` (or `php_sqlsrv_81_ts_x64.dll` for PHP 8.1)
        - `php_pdo_sqlsrv_82_ts_x64.dll` (or `php_pdo_sqlsrv_81_ts_x64.dll` for PHP 8.1)

**Note**: If you encounter compatibility issues with the latest version, try version 5.11.1 or 5.10.1 which have better support for older Windows Server versions.

### Step 4: Install Extension Files

1. **Find PHP extension directory**:

    ```powershell
    php -i | findstr /i "extension_dir"
    ```

    Usually: `C:\xampp\php\ext` or `C:\php\ext`

2. **Copy DLL files** to the extension directory:

    - Copy `php_sqlsrv_82_ts_x64.dll` → rename to `php_sqlsrv.dll`
    - Copy `php_pdo_sqlsrv_82_ts_x64.dll` → rename to `php_pdo_sqlsrv.dll`

3. **Find php.ini**:

    ```powershell
    php --ini
    ```

4. **Edit php.ini** (as Administrator):

    - Add these lines (near other `extension=` lines):

    ```ini
    extension=php_sqlsrv
    extension=php_pdo_sqlsrv
    ```

5. **Restart web server** (Apache/Nginx/IIS)

### Step 5: Verify Installation

Run on production server:

```powershell
php artisan sap:test-sql-connection --date=2025-11-13
```

You should see:

```
✓ sqlsrv extension is loaded
✓ Connection successful
```

## Common Issues on Windows Server 2012 R2

### Issue 1: "Unable to load dynamic library"

-   **Cause**: Wrong DLL version (ZTS vs NTS, x64 vs x86) or missing Visual C++ Redistributable
-   **Solution**:
    -   Download the correct version matching your PHP
    -   Install Visual C++ Redistributable 2015-2022: https://aka.ms/vs/17/release/vc_redist.x64.exe

### Issue 2: "Driver not found"

-   **Cause**: ODBC Driver not installed or wrong version
-   **Solution**:
    -   Try ODBC Driver 17 first (better compatibility with Windows Server 2012 R2)
    -   If that doesn't work, try ODBC Driver 18

### Issue 3: "Connection timeout"

-   **Cause**: Network/firewall blocking SQL Server port 1433
-   **Solution**: Check firewall rules, verify SQL Server is accessible

### Issue 4: "The specified DSN contains an architecture mismatch"

-   **Cause**: Architecture mismatch between PHP and ODBC Driver
-   **Solution**: Ensure both are x64 (64-bit) or both are x86 (32-bit)

### Issue 5: "SQLSTATE[08001]: SSL Provider error"

-   **Cause**: SSL/TLS configuration issue on older Windows Server
-   **Solution**:
    -   Add `'TrustServerCertificate' => true` to connection options (already in config)
    -   Or update Windows Server with latest security patches

## Quick Test After Installation

```powershell
# Test extension is loaded
php -m | findstr sqlsrv

# Test connection
php artisan sap:test-sql-connection --date=2025-11-13
```

## Expected Result

After installation, you should see:

-   ✓ sqlsrv extension is loaded
-   ✓ Connection successful
-   ✓ Query executed successfully
-   **34 records** (or more) for 2025-11-13

If you still see only 3 records, the issue is likely:

-   Different database/company code
-   Date timezone mismatch
-   Different data in production database
