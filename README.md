# DDS Laravel - Document & Invoice Management System

A comprehensive document management and invoice processing platform built with Laravel 10, featuring role-based access control, file management, and integrated notification systems.

## 🚀 Features

### ✅ **Completed Features**

#### **Core System**

-   **Authentication & Authorization**: Complete user management with RBAC using Spatie Laravel Permission
-   **Admin Management**: Full CRUD operations for system administration
-   **Master Data Management**: Projects, departments, suppliers, invoice types
-   **Document Management**: Core CRUD with file uploads and distribution
-   **Invoice Management**: Complete CRUD with advanced features and comprehensive notification system

#### **User Experience**

-   **Modern UI**: AdminLTE 3 with responsive design and Bootstrap 4
-   **Navbar menu search**: Permission-aware destination search from the top bar (keyboard shortcut on desktop); see [architecture.md](docs/architecture.md) (section *Navbar Menu Search*).
-   **DataTables Integration**: Server-side processing with advanced filtering and search
-   **Notification System**: Comprehensive toastr integration with SweetAlert2 confirmations
-   **AJAX Operations**: Smooth form submissions without page reloads with proper error handling
-   **File Management**: Secure file uploads with validation and storage
-   **Edit Operations**: Dedicated edit pages for consistent user experience
-   **Delete Operations**: SweetAlert2 confirmations with proper AJAX handling and user feedback

#### **Technical Implementation**

-   **Laravel 10**: Latest framework with modern PHP features
-   **Database Design**: Optimized MySQL schema with proper relationships
-   **Security**: CSRF protection, input validation, XSS prevention
-   **Performance**: Efficient queries, pagination, and caching strategies
-   **Code Quality**: Clean architecture with proper separation of concerns

### 🔄 **In Progress**

-   Advanced search and filtering capabilities
-   Enhanced document workflow management
-   Performance optimization and caching implementation

### 📋 **Planned Features**

-   Real-time notifications with WebSocket integration
-   Advanced reporting and analytics dashboard
-   Mobile Progressive Web App (PWA)
-   API development for third-party integrations
-   Two-factor authentication and enhanced security

## 🛠 Technology Stack

### **Backend**

-   **Framework**: Laravel 10.x
-   **PHP**: 8.1+
-   **Database**: MySQL 8.0+
-   **Authentication**: Laravel Sanctum + Spatie Laravel Permission

### **Frontend**

-   **CSS Framework**: AdminLTE 3 with Bootstrap 4
-   **JavaScript**: jQuery, DataTables, SweetAlert2, Toastr
-   **Date Handling**: Moment.js with DateRangePicker
-   **Form Controls**: Bootstrap Switch, Select2

### **Development Tools**

-   **Package Manager**: Composer
-   **Build Tool**: Laravel Mix
-   **Version Control**: Git
-   **Development**: Docker support

## 📁 Project Structure

```
dds-laravel/
├── app/
│   ├── Http/Controllers/     # Application controllers
│   ├── Models/              # Eloquent models
│   └── Providers/           # Service providers
├── resources/
│   ├── views/               # Blade templates
│   │   ├── admin/          # Admin management views
│   │   ├── invoices/       # Invoice management views
│   │   ├── additional_documents/ # Document management views
│   │   └── layouts/        # Layout templates
│   └── assets/             # Frontend assets
├── routes/                  # Application routes
├── database/                # Migrations and seeders
└── docs/                    # Project documentation
```

## 🚀 Quick Start

### **Prerequisites**

-   PHP 8.1 or higher
-   Composer
-   MySQL 8.0 or higher
-   Node.js and NPM (for asset compilation)

### **Installation**

1. **Clone the repository**

    ```bash
    git clone <repository-url>
    cd dds-laravel
    ```

2. **Install PHP dependencies**

    ```bash
    composer install
    ```

3. **Install Node.js dependencies**

    ```bash
    npm install
    ```

4. **Environment setup**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

5. **Database configuration**

    ```bash
    # Update .env with your database credentials
    php artisan migrate
    php artisan db:seed
    ```

6. **Asset compilation**

    ```bash
    npm run dev
    ```

7. **Start the development server**
    ```bash
    php artisan serve
    ```

## 🔐 Default Users

After running the seeders, you'll have these default accounts:

-   **Superadmin**: `superadmin@example.com` / `password`
-   **Admin**: `admin@example.com` / `password`
-   **User**: `user@example.com` / `password`

## 📚 Documentation

-   **[Architecture](docs/architecture.md)**: System architecture and technical details
-   **[API Reference](docs/api.md)**: API endpoints and usage
-   **[Development Guide](docs/development.md)**: Development setup and guidelines
-   **[Deployment](docs/deployment.md)**: Production deployment instructions
-   **[Backlog](docs/backlog.md)**: Feature roadmap and development priorities

## 🔧 Configuration

### **Key Configuration Files**

-   `.env`: Environment variables and database configuration
-   `config/auth.php`: Authentication configuration
-   `config/permission.php`: Spatie Permission configuration
-   `config/filesystems.php`: File storage configuration

### **Environment Variables**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dds_backend
DB_USERNAME=your_username
DB_PASSWORD=your_password

APP_NAME="DDS Laravel"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

## 🧪 Testing

```bash
# Run PHPUnit tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run tests with coverage
php artisan test --coverage
```

## 📦 Deployment

### **Production Requirements**

-   PHP 8.1+ with required extensions
-   MySQL 8.0+ or compatible database
-   Web server (Nginx/Apache)
-   SSL certificate for HTTPS
-   Proper file permissions

### **Deployment Steps**

1. Set production environment variables
2. Run database migrations
3. Compile and optimize assets
4. Configure web server
5. Set up SSL certificates
6. Configure backup strategies

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is proprietary software. All rights reserved.

## 🆘 Support

For support and questions:

-   Create an issue in the repository
-   Contact the development team
-   Check the documentation in the `docs/` folder

## 🗺 Roadmap

### **Q4 2025**

-   Advanced search and filtering
-   Performance optimization
-   Enhanced security features

### **Q1 2026**

-   Real-time notifications
-   Mobile PWA
-   API development

### **Q2 2026**

-   Advanced analytics
-   Machine learning integration
-   Multi-language support

---

**Last Updated**: August 10, 2025  
**Version**: 1.0.0  
**Status**: Production Ready ✅
