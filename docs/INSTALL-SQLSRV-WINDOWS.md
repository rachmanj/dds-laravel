# Installing SQL Server PHP Extension on Windows

## Your PHP Configuration

Based on your system:
- **PHP Version**: 8.2.12
- **Architecture**: x64 (64-bit)
- **Thread Safety**: Enabled (ZTS)
- **Compiler**: Visual C++ 2019

## Step 1: Install Microsoft ODBC Driver for SQL Server

The `sqlsrv` extension requires the Microsoft ODBC Driver for SQL Server.

### Option A: Download and Install (Recommended)

1. **Download Microsoft ODBC Driver 18 for SQL Server**:
   - Go to: https://learn.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server
   - Download: **ODBC Driver 18 for SQL Server** (Windows x64)
   - Or direct link: https://go.microsoft.com/fwlink/?linkid=2249004

2. **Install the driver**:
   - Run the downloaded `.msi` file
   - Follow the installation wizard
   - Restart your computer if prompted

### Option B: Using Chocolatey (if you have it)

```powershell
choco install msodbcsql18
```

### Verify ODBC Driver Installation

```powershell
# Check installed ODBC drivers
Get-OdbcDriver | Where-Object {$_.Name -like "*SQL Server*"}
```

You should see something like:
```
Name                                    Platform
----                                    --------
ODBC Driver 18 for SQL Server          {x64}
```

## Step 2: Install PHP sqlsrv Extension

### Download the Extension

1. **Go to Microsoft's PHP Drivers for SQL Server**:
   - https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server
   - Or direct: https://github.com/Microsoft/msphpsql/releases

2. **Download the correct version**:
   - For PHP 8.2 ZTS x64, download: **8.2 ZTS x64** version
   - Look for: `php_sqlsrv_82_ts_x64.dll` and `php_pdo_sqlsrv_82_ts_x64.dll`
   - The file name format: `php_sqlsrv_[version]_[ts/nts]_[x64/x86].dll`

### Install the Extension

1. **Find your PHP extension directory**:
   ```powershell
   php -i | findstr /i "extension_dir"
   ```
   Usually: `C:\php\ext` or `C:\xampp\php\ext` or similar

2. **Copy the DLL files**:
   - Copy `php_sqlsrv_82_ts_x64.dll` to your PHP `ext` directory
   - Copy `php_pdo_sqlsrv_82_ts_x64.dll` to your PHP `ext` directory
   - Rename them to:
     - `php_sqlsrv.dll`
     - `php_pdo_sqlsrv.dll`

3. **Find your php.ini file**:
   ```powershell
   php --ini
   ```

4. **Edit php.ini**:
   - Open `php.ini` in a text editor (as Administrator)
   - Add these lines (usually near other `extension=` lines):
   ```ini
   extension=php_sqlsrv
   extension=php_pdo_sqlsrv
   ```

5. **Restart your web server** (if using Apache/Nginx):
   ```powershell
   # For XAMPP
   # Stop and start Apache from XAMPP Control Panel
   
   # For Laravel development server, just restart it
   php artisan serve
   ```

## Step 3: Verify Installation

### Check if Extension is Loaded

```powershell
php -m | findstr /i sqlsrv
```

You should see:
```
pdo_sqlsrv
sqlsrv
```

### Test Connection

```powershell
php artisan tinker
```

Then in tinker:
```php
try {
    DB::connection('sap_sql')->select('SELECT TOP 1 * FROM OWTR');
    echo "Connection successful!";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Troubleshooting

### Error: "Unable to load dynamic library 'php_sqlsrv'"

**Solution**: 
- Make sure you downloaded the correct version (ZTS for Thread Safe, NTS for Non-Thread Safe)
- Check that the DLL files are in the correct `ext` directory
- Verify the file names match what's in `php.ini`

### Error: "The specified DSN contains an architecture mismatch"

**Solution**:
- Make sure both PHP and ODBC Driver are the same architecture (both x64 or both x86)
- You're using x64, so make sure ODBC Driver is also x64

### Error: "SQLSTATE[08001]: [Microsoft][ODBC Driver 18 for SQL Server]SSL Provider: No credentials are available in the security package"

**Solution**:
- Add `'TrustServerCertificate' => true` to connection options (already done in config)
- Or enable encryption in connection string

### Error: "SQLSTATE[HY000] [2002] No connection could be made"

**Solution**:
- Check that SQL Server is running
- Verify host, port, and database name in `.env`
- Check firewall settings
- Test connection with SQL Server Management Studio first

## Quick Installation Script (PowerShell)

Save this as `install-sqlsrv.ps1` and run as Administrator:

```powershell
# Check PHP version
$phpVersion = php -r "echo PHP_VERSION;"
Write-Host "PHP Version: $phpVersion"

# Check if extension is already installed
$extInstalled = php -m | Select-String -Pattern "sqlsrv"
if ($extInstalled) {
    Write-Host "sqlsrv extension is already installed!" -ForegroundColor Green
    exit
}

# Get PHP extension directory
$extDir = php -i | Select-String -Pattern "extension_dir" | Select-Object -First 1
$extDir = $extDir.ToString().Split("=>")[1].Trim()
Write-Host "Extension directory: $extDir"

# Download URLs (update these with latest versions)
$sqlsrvUrl = "https://github.com/Microsoft/msphpsql/releases/download/v5.11.1/Windows-8.2.zip"
$tempFile = "$env:TEMP\sqlsrv.zip"

Write-Host "Downloading sqlsrv extension..."
Invoke-WebRequest -Uri $sqlsrvUrl -OutFile $tempFile

Write-Host "Extracting..."
Expand-Archive -Path $tempFile -DestinationPath "$env:TEMP\sqlsrv" -Force

# Copy DLL files (adjust path based on extracted structure)
$dllPath = "$env:TEMP\sqlsrv\Windows-8.2\php_sqlsrv_82_ts_x64.dll"
if (Test-Path $dllPath) {
    Copy-Item $dllPath "$extDir\php_sqlsrv.dll" -Force
    Write-Host "Copied php_sqlsrv.dll" -ForegroundColor Green
}

$pdoDllPath = "$env:TEMP\sqlsrv\Windows-8.2\php_pdo_sqlsrv_82_ts_x64.dll"
if (Test-Path $pdoDllPath) {
    Copy-Item $pdoDllPath "$extDir\php_pdo_sqlsrv.dll" -Force
    Write-Host "Copied php_pdo_sqlsrv.dll" -ForegroundColor Green
}

# Find php.ini
$phpIni = php --ini | Select-String -Pattern "Loaded Configuration File" | ForEach-Object { $_.ToString().Split(":")[1].Trim() }
Write-Host "php.ini location: $phpIni"

# Add extensions to php.ini
$iniContent = Get-Content $phpIni
if ($iniContent -notmatch "extension=php_sqlsrv") {
    Add-Content $phpIni "`nextension=php_sqlsrv"
    Add-Content $phpIni "extension=php_pdo_sqlsrv"
    Write-Host "Added extensions to php.ini" -ForegroundColor Green
}

Write-Host "`nInstallation complete! Please restart your web server." -ForegroundColor Green
```

## Alternative: Using PECL (if available)

```powershell
pecl install sqlsrv
pecl install pdo_sqlsrv
```

## References

- Microsoft PHP Drivers: https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server
- ODBC Driver: https://learn.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server
- GitHub Releases: https://github.com/Microsoft/msphpsql/releases

