# Authentication System Documentation

## Overview

This document describes the authentication system implemented in the DDS Laravel project. The system uses Laravel's built-in authentication features with AdminLTE 3.2 for the UI components.

## Features

-   User login with email and password
-   User registration
-   Remember me functionality
-   Form validation
-   Error handling
-   Protected routes

## Files Structure

### Controllers

-   `app/Http/Controllers/Auth/AuthController.php` - Handles authentication logic including login, registration, and logout.

### Routes

Authentication routes are defined in `routes/web.php`:

```php
// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Protected routes will go here
});
```

### Views

-   `resources/views/layouts/auth.blade.php` - Base layout for authentication pages with AdminLTE theme
-   `resources/views/auth/login.blade.php` - Login form
-   `resources/views/auth/register.blade.php` - Registration form

## AdminLTE Integration

The authentication system uses AdminLTE 3.2 for the UI components. The following AdminLTE components are used:

-   Login page template
-   Registration page template
-   Form components
-   Card components
-   Icons from Font Awesome

## Usage

### Login

Users can log in at `/login` with their email and password. The form includes:

-   Email field
-   Password field
-   Remember me checkbox
-   Sign in button
-   Link to registration page

### Registration

New users can register at `/register`. The registration form includes:

-   Name field
-   Email field
-   Password field
-   Password confirmation field
-   Terms agreement checkbox
-   Register button
-   Link to login page

### Protected Routes

Routes that require authentication should be placed within the `auth` middleware group in `routes/web.php`.

## Future Enhancements

Potential enhancements for the authentication system:

1. Password reset functionality
2. Email verification
3. Two-factor authentication
4. Social login integration
5. User profile management

## References

-   [Laravel Authentication Documentation](https://laravel.com/docs/11.x/authentication)
-   [AdminLTE Documentation](https://adminlte.io/docs/3.2/)
