# Spatie Laravel Permission Setup Guide

This document explains how to use the Spatie Laravel Permission system that has been set up in your Laravel application.

## What's Been Set Up

1. **Package Installation**: Spatie Laravel Permission package installed
2. **Database Tables**: Permission tables created via migration
3. **User Model**: Updated with `HasRoles` trait
4. **Roles & Permissions**: Pre-configured with basic roles and permissions
5. **Seeders**: Database seeded with sample data
6. **Example Routes**: Demo routes showing permission usage
7. **Example Views**: Dashboard showing permission-based UI

## Default Roles and Permissions

### Roles Created:

-   **super-admin**: Full access to everything
-   **admin**: Extensive permissions (no role management)
-   **manager**: Moderate permissions (content management)
-   **user**: Basic permissions (view content only)

### Permissions Created:

-   **User Management**: `view-users`, `create-users`, `edit-users`, `delete-users`
-   **Role Management**: `view-roles`, `create-roles`, `edit-roles`, `delete-roles`
-   **Permission Management**: `view-permissions`, `assign-permissions`
-   **Content Management**: `view-content`, `create-content`, `edit-content`, `delete-content`
-   **Settings**: `view-settings`, `edit-settings`

## Default Users

-   **Super Admin**: `admin@example.com` (password: `password`)
-   **Test User**: `test@example.com` (password: `password`)

## How to Use

### 1. In Controllers

```php
// Check if user has permission
if (auth()->user()->can('edit-users')) {
    // User can edit users
}

// Check if user has role
if (auth()->user()->hasRole('admin')) {
    // User is admin
}

// Check multiple permissions
if (auth()->user()->hasAnyPermission(['create-users', 'edit-users'])) {
    // User has at least one of these permissions
}

// Check all permissions
if (auth()->user()->hasAllPermissions(['create-users', 'edit-users'])) {
    // User has all these permissions
}
```

### 2. In Blade Templates

```blade
{{-- Check permission --}}
@can('edit-users')
    <button>Edit User</button>
@endcan

{{-- Check role --}}
@role('admin')
    <div>Admin content</div>
@endrole

{{-- Check if user doesn't have permission --}}
@cannot('delete-users')
    <p>You cannot delete users</p>
@endcannot

{{-- Check multiple roles --}}
@hasanyrole('admin|manager')
    <div>Admin or Manager content</div>
@endhasanyrole
```

### 3. In Routes (Middleware)

```php
// Require specific permission
Route::get('/users', [UserController::class, 'index'])
    ->middleware('permission:view-users');

// Require specific role
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('role:admin');

// Require multiple permissions
Route::get('/users/create', [UserController::class, 'create'])
    ->middleware('permission:create-users|edit-users');

// Require multiple roles
Route::get('/management', [ManagementController::class, 'index'])
    ->middleware('role:admin|manager');
```

### 4. Managing Roles and Permissions

```php
// Create a new role
$role = Role::create(['name' => 'editor']);

// Create a new permission
$permission = Permission::create(['name' => 'publish-articles']);

// Assign permission to role
$role->givePermissionTo('publish-articles');

// Assign role to user
$user->assignRole('editor');

// Remove role from user
$user->removeRole('editor');

// Sync roles (removes all existing and assigns new ones)
$user->syncRoles(['admin', 'editor']);

// Check if user has permission
$user->hasPermissionTo('publish-articles');

// Get all user permissions
$user->getAllPermissions();

// Get all user roles
$user->getRoleNames();
```

## Testing the System

1. **Start the server**: `php artisan serve`
2. **Visit**: `http://localhost:8000/dashboard`
3. **Login with**: `admin@example.com` / `password`
4. **Explore**: Different sections based on permissions

## Available Routes for Testing

-   `/dashboard` - Main dashboard (requires auth)
-   `/users` - Users page (requires `view-users` permission)
-   `/roles` - Roles page (requires `view-roles` permission)
-   `/content` - Content page (requires `view-content` permission)
-   `/settings` - Settings page (requires `view-settings` permission)
-   `/admin-panel` - Admin panel (requires `admin` role)
-   `/super-admin` - Super admin panel (requires `super-admin` role)

## Adding New Permissions

1. **Create migration** (if needed):

```bash
php artisan make:migration add_new_permissions
```

2. **Add to seeder**:

```php
// In RolePermissionSeeder.php
$permissions = [
    // ... existing permissions
    'new-permission',
    'another-permission',
];
```

3. **Run seeder**:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

## Best Practices

1. **Use descriptive permission names**: `create-users` instead of `create`
2. **Group related permissions**: `users.create`, `users.edit`, `users.delete`
3. **Cache permissions** in production for better performance
4. **Use roles for broad access control** and permissions for specific actions
5. **Always check permissions** before performing actions, not just in UI

## Cache Management

```bash
# Clear permission cache
php artisan permission:cache-reset

# Cache permissions (for production)
php artisan permission:cache
```

## Troubleshooting

-   **Permissions not working?** Clear cache: `php artisan permission:cache-reset`
-   **Roles not showing?** Make sure User model has `HasRoles` trait
-   **Middleware not working?** Check if middleware is registered in `bootstrap/app.php`

## Next Steps

1. Customize permissions for your specific application needs
2. Add more roles as required
3. Implement permission checks in your controllers
4. Add permission-based UI elements to your views
5. Set up API authentication if needed
