Purpose: Technical reference for understanding system design and development patterns
Last Updated: 2025-08-09

## Architecture Documentation Guidelines

### Document Purpose

This document describes the CURRENT WORKING STATE of the application architecture. It serves as:

-   Technical reference for understanding how the system currently works
-   Onboarding guide for new developers
-   Design pattern documentation for consistent development
-   Schema and data flow documentation reflecting actual implementation

### What TO Include

-   **Current Technology Stack**: Technologies actually in use
-   **Working Components**: Components that are implemented and functional
-   **Actual Database Schema**: Tables, fields, and relationships as they exist
-   **Implemented Data Flows**: How data actually moves through the system
-   **Working API Endpoints**: Routes that are active and functional
-   **Deployment Patterns**: How the system is actually deployed
-   **Security Measures**: Security implementations that are active

### What NOT to Include

-   **Issues or Bugs**: These belong in `MEMORY.md` with technical debt entries
-   **Limitations or Problems**: Document what IS working, not what isn't
-   **Future Plans**: Enhancement ideas belong in `backlog.md`
-   **Deprecated Features**: Remove outdated information rather than marking as deprecated
-   **Wishlist Items**: Planned features that aren't implemented yet

### Update Guidelines

-   **Reflect Reality**: Always document the actual current state, not intended state
-   **Schema Notes**: When database schema has unused fields, note them factually
-   **Cross-Reference**: Link to other docs when appropriate, but don't duplicate content

### For AI Coding Agents

-   **Investigate Before Updating**: Use codebase search to verify current implementation
-   **Move Issues to Memory**: If you discover problems, document them in `MEMORY.md`
-   **Factual Documentation**: Describe what exists, not what should exist

---

# System Architecture

## Overview

The DDS Laravel system is a comprehensive document management and invoice processing platform built with Laravel 10, featuring role-based access control, file management, and integrated notification systems.

## Core Components

### 1. Authentication & Authorization

-   **Laravel Sanctum**: API authentication
-   **Spatie Laravel Permission**: Role-based access control (RBAC)
-   **User Roles**: superadmin, admin, user with department-based restrictions

### 2. Database Architecture

-   **Primary Database**: MySQL (`dds_backend`)
-   **Key Tables**: users, departments, invoices, suppliers, invoice_types, projects, additional_documents, distributions
-   **Relationships**: Complex many-to-many relationships with pivot tables for document distribution

### 3. File Management System

-   **Storage**: Laravel Storage with local filesystem
-   **File Types**: PDFs, images, documents
-   **Attachments**: Polymorphic relationships for multiple document types

## Invoices Management System

### Current Implementation Status

-   **✅ Complete**: Core CRUD operations with proper validation
-   **✅ Complete**: Role-based access control and location restrictions
-   **✅ Complete**: Comprehensive toastr notification system
-   **✅ Complete**: AJAX form submission for create/edit operations
-   **✅ Complete**: SweetAlert2 integration for delete confirmations
-   **✅ Complete**: DataTables integration with server-side processing
-   **✅ Complete**: Edit page navigation (dedicated edit pages instead of modals)
-   **✅ Complete**: Delete functionality with proper AJAX handling and user feedback

### Technical Architecture

```
Invoices Management Flow:
1. User Access Control → Department Location Validation
2. Form Submission → AJAX Processing → Database Validation
3. Real-time Validation → Composite Unique Constraint (supplier_id + invoice_number)
4. User Feedback → Toastr Notifications + SweetAlert2 Confirmations
5. Data Persistence → Optimized Database Schema with Proper Indexes
```

### Validation System Architecture

#### **Invoice Number Duplication Prevention**

-   **Database Level**: Composite unique constraint on `(supplier_id, invoice_number)`
-   **Application Level**: Custom validation rule `UniqueInvoicePerSupplier`
-   **Frontend Level**: Real-time AJAX validation with debounced input
-   **API Endpoint**: `/invoices/validate-invoice-number` for instant feedback

#### **Validation Flow**

```
User Input → Frontend Validation → AJAX Request → Backend Validation → Database Check → Response → UI Update
```

#### **Key Benefits**

-   **Business Logic**: Different suppliers can use same invoice numbers
-   **Data Integrity**: Prevents duplicate invoices per supplier
-   **User Experience**: Instant feedback without form submission
-   **Performance**: Debounced validation (500ms delay) + database indexes

### Notification System Architecture

-   **Toastr**: Primary notification system for all user feedback
    -   Success messages, error handling, validation feedback
    -   Non-blocking, auto-dismissing notifications
    -   Consistent positioning and styling across all views
    -   Session message integration for page loads
    -   AJAX response feedback for real-time operations
-   **SweetAlert2**: Critical confirmations only (delete operations)
    -   Modal-style confirmations for destructive actions
    -   Enhanced user experience for important decisions
    -   Consistent styling with AdminLTE theme

### AJAX Integration Patterns

-   **Form Submission**: AJAX with FormData for file uploads
-   **Response Handling**: JSON responses for AJAX, redirects for regular requests
-   **Error Handling**: Comprehensive validation error display
-   **Loading States**: User feedback during operations
-   **Delete Operations**: SweetAlert2 confirmation followed by AJAX DELETE request
-   **DataTable Integration**: Proper reload after operations without pagination reset

### User Interface Architecture

-   **Edit Operations**: Direct navigation to dedicated edit pages
    -   Consistent form experience with create pages
    -   Proper validation and error handling
    -   File upload support for attachments
-   **Delete Operations**: SweetAlert2 confirmation with AJAX handling
    -   User-friendly confirmation dialogs
    -   Proper error handling and user feedback
    -   DataTable state preservation after operations

## Document Management System

### Current Status

-   **✅ Complete**: Core document CRUD operations
-   **✅ Complete**: File upload and storage
-   **✅ Complete**: Distribution management
-   **🔄 In Progress**: Advanced search and filtering

### Architecture Components

-   **Document Model**: Central entity for all document types
-   **Attachment System**: Polymorphic file attachments
-   **Distribution Engine**: Role-based document sharing
-   **Search Indexing**: Full-text search capabilities

## Frontend Architecture

### Technology Stack

-   **CSS Framework**: AdminLTE 3 with Bootstrap 4
-   **JavaScript Libraries**: jQuery, DataTables, SweetAlert2, Toastr
-   **Date Handling**: Moment.js with DateRangePicker
-   **Form Controls**: Bootstrap Switch, Select2

### Responsive Design

-   **Mobile-First**: Bootstrap responsive grid system
-   **Touch-Friendly**: Optimized for mobile devices
-   **Accessibility**: ARIA labels and semantic HTML

## API Architecture

### RESTful Endpoints

-   **Resource Controllers**: Standard Laravel resource routing
-   **AJAX Support**: JSON responses for dynamic requests
-   **Validation**: Laravel Form Request validation
-   **Error Handling**: Consistent error response format

### Data Flow

```
Client Request → Route → Middleware → Controller → Model → Database
                ↓
Response ← JSON/Redirect ← Validation ← Business Logic ← Query
```

## Security Architecture

### Access Control

-   **Route Protection**: Middleware-based authentication
-   **Data Isolation**: Department-based data segregation
-   **Permission Checks**: Role-based feature access
-   **CSRF Protection**: Laravel built-in CSRF tokens

### Data Validation

-   **Input Sanitization**: Laravel validation rules
-   **SQL Injection Prevention**: Eloquent ORM with parameter binding
-   **File Upload Security**: MIME type and size validation
-   **XSS Protection**: Blade template escaping

## Performance Considerations

### Database Optimization

-   **Indexing**: Strategic database indexes on frequently queried fields
-   **Eager Loading**: N+1 query prevention with relationship loading
-   **Pagination**: Server-side pagination for large datasets
-   **Caching**: Redis integration for frequently accessed data

### Frontend Performance

-   **Lazy Loading**: DataTables server-side processing
-   **Asset Optimization**: Minified CSS/JS with proper caching
-   **Image Optimization**: Responsive images and lazy loading
-   **CDN Integration**: External asset delivery optimization

## Deployment Architecture

### Environment Configuration

-   **Development**: Local development with Docker
-   **Staging**: Pre-production testing environment
-   **Production**: Live system with load balancing

### Infrastructure

-   **Web Server**: Nginx with PHP-FPM
-   **Database**: MySQL with read replicas
-   **File Storage**: Local filesystem with backup strategy
-   **Monitoring**: Application performance monitoring

## Future Enhancements

### Planned Features

-   **Real-time Notifications**: WebSocket integration for live updates
-   **Advanced Search**: Elasticsearch integration for full-text search
-   **API Versioning**: RESTful API with version control
-   **Microservices**: Service-oriented architecture for scalability

### Technical Debt

-   **Code Refactoring**: Controller method optimization
-   **Test Coverage**: Unit and integration test implementation
-   **Documentation**: API documentation with OpenAPI/Swagger
-   **Performance Monitoring**: Application performance metrics
