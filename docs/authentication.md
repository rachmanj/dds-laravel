# Authentication System Documentation

## Overview

This document describes the authentication system implemented in the DDS Laravel project. The system uses Laravel's built-in authentication features with AdminLTE 3.2 for the UI components.

## Features

-   User login with email or username and password
-   User registration
-   Remember me functionality
-   Form validation
-   Error handling
-   Protected routes
-   Username uniqueness validation with NULL value support

## Files Structure

### Controllers

-   `app/Http/Controllers/Auth/LoginController.php` - Handles login form display and authentication attempts
-   `app/Http/Controllers/Auth/RegisterController.php` - Handles user registration
-   `app/Http/Controllers/Auth/LogoutController.php` - Handles logout

### Routes

Authentication routes are defined in `routes/web.php`:

```php
// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

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

Users can log in at `/login` with their email or username and password. The form includes:

-   Email or Username field (`login`)
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

## Username Uniqueness Validation

### Implementation (2025-10-01)

The system enforces username uniqueness to prevent user conflicts and security issues while maintaining flexibility for email-only login.

#### Database Constraint

```sql
-- Migration: 2025_10_01_060319_add_unique_constraint_to_username_in_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->string('username')->nullable()->unique()->change();
});
```

**Key Features**:

-   **Unique Constraint**: Prevents duplicate usernames at database level
-   **Nullable Support**: Multiple users can have NULL username (for email-only login)
-   **Data Integrity**: MySQL unique constraint allows multiple NULL values while enforcing uniqueness on non-NULL values

#### Application Validation

**User Creation** (`UserController::store()`):

```php
$request->validate([
    'username' => ['nullable', 'string', 'max:255', 'unique:users'],
    // ... other fields
]);
```

**User Update** (`UserController::update()`):

```php
$request->validate([
    'username' => ['nullable', 'string', 'max:255', 'unique:users,username,' . $user->id],
    // ... other fields
]);
```

**Update Logic**: The `unique:users,username,{user_id}` rule allows users to keep their existing username during updates while preventing them from changing to another user's username.

#### Security Benefits

1. **Prevents Username Conflicts**: No two users can have the same username
2. **Login Clarity**: Eliminates ambiguity in username-based authentication
3. **Impersonation Prevention**: Users cannot create accounts with existing usernames
4. **Database Integrity**: Constraint enforced at multiple levels (database + application)

#### User Experience

-   **Clear Error Messages**: "The username has already been taken." shown for duplicate attempts
-   **Flexible Login**: Users can choose to use email-only (NULL username) or username+email
-   **Update Freedom**: Users can update their profile information without username conflicts
-   **NULL Handling**: Multiple users can have empty usernames without validation errors

#### Testing Scenarios

| Scenario                                 | Expected Result                | Status  |
| ---------------------------------------- | ------------------------------ | ------- |
| Create user with duplicate username      | Validation error displayed     | ✅ PASS |
| Create user with unique username         | User created successfully      | ✅ PASS |
| Update user to duplicate username        | Validation error displayed     | ✅ PASS |
| Update user keeping same username        | Update successful              | ✅ PASS |
| Create user with NULL username           | User created successfully      | ✅ PASS |
| Create multiple users with NULL username | All users created successfully | ✅ PASS |

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
-   [Laravel Validation Documentation](https://laravel.com/docs/11.x/validation#rule-unique)
