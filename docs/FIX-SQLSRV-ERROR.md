# Fixing "%1 is not a valid Win32 application" Error

## Your Requirements

Based on your PHP configuration:
- **PHP Version**: 8.2.12
- **Thread Safety**: ZTS (Thread Safe) ✅
- **Architecture**: x64 ✅

**You need**: `php_sqlsrv_82_ts_x64.dll` and `php_pdo_sqlsrv_82_ts_x64.dll`

## The Problem

The error "%1 is not a valid Win32 application" means:
- ❌ Wrong architecture (x86 instead of x64)
- ❌ Wrong thread safety (NTS instead of ZTS)
- ❌ Wrong PHP version
- ❌ Corrupted DLL files

## Solution: Download Correct DLLs

### Step 1: Download the Correct Version

1. **Go to Microsoft PHP Drivers Releases**:
   - https://github.com/Microsoft/msphpsql/releases
   - Look for the **latest release** (e.g., v5.11.1 or newer)

2. **Download the Windows ZIP file**:
   - Look for: `Windows-8.2.zip` or similar
   - This contains DLLs for PHP 8.2

3. **Extract the ZIP file**

4. **Find the correct DLLs**:
   - Navigate to: `Windows-8.2\php_sqlsrv_82_ts_x64.dll`
   - Navigate to: `Windows-8.2\php_pdo_sqlsrv_82_ts_x64.dll`
   - **Important**: Must be `_ts_` (Thread Safe) and `_x64` (64-bit)

### Step 2: Replace the DLLs

1. **Delete old DLLs** (if they exist):
   ```powershell
   Remove-Item C:\xampp\php\ext\php_sqlsrv.dll -ErrorAction SilentlyContinue
   Remove-Item C:\xampp\php\ext\php_pdo_sqlsrv.dll -ErrorAction SilentlyContinue
   ```

2. **Copy new DLLs**:
   ```powershell
   # Copy from extracted folder to PHP ext directory
   Copy-Item "path\to\extracted\Windows-8.2\php_sqlsrv_82_ts_x64.dll" "C:\xampp\php\ext\php_sqlsrv.dll"
   Copy-Item "path\to\extracted\Windows-8.2\php_pdo_sqlsrv_82_ts_x64.dll" "C:\xampp\php\ext\php_pdo_sqlsrv.dll"
   ```

   **Or manually**:
   - Copy `php_sqlsrv_82_ts_x64.dll` → `C:\xampp\php\ext\php_sqlsrv.dll`
   - Copy `php_pdo_sqlsrv_82_ts_x64.dll` → `C:\xampp\php\ext\php_pdo_sqlsrv.dll`

### Step 3: Verify php.ini

Make sure `C:\xampp\php\php.ini` has:

```ini
extension=php_sqlsrv
extension=php_pdo_sqlsrv
```

**NOT**:
```ini
extension=php_sqlsrv.dll  ❌ (don't use .dll extension)
extension=php_pdo_sqlsrv.dll  ❌
```

### Step 4: Install Visual C++ Redistributable (if missing)

The DLLs require Visual C++ Redistributable:

1. **Download**: https://aka.ms/vs/17/release/vc_redist.x64.exe
2. **Install** it
3. **Restart** your computer

### Step 5: Verify Installation

```powershell
# Check if extension loads (should NOT show errors)
php -m | findstr /i sqlsrv
```

You should see:
```
pdo_sqlsrv
sqlsrv
```

**No errors!**

## Quick Download Links

### Latest Release (as of 2024):
- **GitHub Releases**: https://github.com/Microsoft/msphpsql/releases
- **Direct Download** (check latest version): 
  - https://github.com/Microsoft/msphpsql/releases/latest
  - Download: `Windows-8.2.zip`

### Visual C++ Redistributable:
- **x64**: https://aka.ms/vs/17/release/vc_redist.x64.exe

## Alternative: Use PECL (if available)

If you have PECL installed:

```powershell
pecl install sqlsrv
pecl install pdo_sqlsrv
```

## Troubleshooting

### Still getting the error?

1. **Check DLL architecture**:
   ```powershell
   # Right-click DLL → Properties → Details
   # Should show: 64-bit
   ```

2. **Check if Visual C++ Redistributable is installed**:
   ```powershell
   Get-ItemProperty "HKLM:\SOFTWARE\Microsoft\VisualStudio\14.0\VC\Runtimes\x64" -ErrorAction SilentlyContinue
   ```

3. **Try downloading from different source**:
   - Sometimes DLLs get corrupted during download
   - Re-download and try again

4. **Check PHP extension directory**:
   ```powershell
   php -i | findstr /i "extension_dir"
   ```
   Make sure DLLs are in the correct directory!

## For Laravel Development Server

Since you're using `php artisan serve`, you only need to:
1. Install the extension correctly (as above)
2. Restart your terminal/command prompt
3. Run `php artisan serve` again

The extension will work for both CLI and web requests.

