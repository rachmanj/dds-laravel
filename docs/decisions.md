**Purpose**: Record technical decisions and rationale for future reference
**Last Updated**: 2025-08-08

# Technical Decision Records

## Decision Template

Decision: [Title] - [YYYY-MM-DD]

**Context**: [What situation led to this decision?]

**Options Considered**:

1. **Option A**: [Description]
    - ✅ Pros: [Benefits]
    - ❌ Cons: [Drawbacks]
2. **Option B**: [Description]
    - ✅ Pros: [Benefits]
    - ❌ Cons: [Drawbacks]

**Decision**: [What we chose]

**Rationale**: [Why we chose this option]

**Implementation**: [How this affects the codebase]

**Review Date**: [When to revisit this decision]

---

## Recent Decisions

### Decision: Invoice Attachments Aggregated Index + Detail Management - 2025-08-11

Context: We needed a better UX for invoice attachments. Instead of listing individual attachments globally, the main page should show invoices with attachment summaries, while full attachment management happens on each invoice detail page.

Options Considered:

1. Global list of attachments with invoice reference
    - ✅ Pros: Simple data model
    - ❌ Cons: Poor discoverability by invoice context, more clicks to navigate
2. Aggregated invoice-first view with per-invoice management
    - ✅ Pros: Better mental model, summarizes size/count/last upload per invoice, clearer access control
    - ❌ Cons: Requires additional aggregation endpoints/UI

Decision: Aggregated invoice-first index with attachment summaries, and detailed CRUD on the invoice show page.

Implementation:

-   Added menu item `Invoices → Invoice Attachments` linking to `invoices.attachments.index`
-   Created `resources/views/invoice_attachments/index.blade.php` with DataTables server-side table and summary boxes
-   Implemented `InvoiceAttachmentController@index|data` to return aggregated invoice-level metrics (count, total size, last upload/user)
-   Implemented `Api\InvoiceAttachmentController@getAttachmentStats` for dashboard stats (totals, distribution, recent uploads)
-   Tightened permission checks with `inv-attachment-*` across all controller actions and location-based restrictions
-   Enforced file validation: types [pdf, jpg, jpeg, png, gif, webp], max 5 MB per file
-   Enhanced invoice `show` view with attachments list, upload form, AJAX delete, and edit modal for description

Review Date: 2025-11-11

### Decision: Invoice Number Duplication Prevention System - 2025-08-10

**Context**: The invoice management system needed to prevent duplicate invoice numbers within the same supplier while allowing different suppliers to use the same invoice numbers. This is a common business requirement where suppliers may have their own invoice numbering systems.

**Options Considered**:

1. **Simple unique constraint on invoice_number alone**

    - ❌ Cons: Too restrictive, prevents different suppliers from using same invoice numbers
    - ❌ Cons: Doesn't match business logic requirements

2. **Application-level validation only**

    - ❌ Cons: No database-level protection, potential race conditions
    - ❌ Cons: Data integrity relies solely on application code

3. **Composite unique constraint + custom validation rule + real-time frontend validation**
    - ✅ Pros: Database-level data integrity protection
    - ✅ Pros: Application-level business logic validation
    - ✅ Pros: Real-time user feedback for better UX
    - ✅ Pros: Prevents race conditions and ensures consistency
    - ✅ Pros: Follows Laravel best practices for validation

**Decision**: Implemented comprehensive validation system with three layers:

-   Database: Composite unique constraint on `(supplier_id, invoice_number)`
-   Application: Custom validation rule `UniqueInvoicePerSupplier`
-   Frontend: Real-time AJAX validation with debounced input

**Implementation Details**:

-   Modified existing invoices table migration to add composite constraint
-   Created custom validation rule with proper error handling
-   Added AJAX endpoint for real-time validation
-   Enhanced both create and edit forms with instant feedback
-   Used debouncing (500ms) to optimize API calls

**Outcome**: System now prevents invoice number duplication per supplier while maintaining excellent user experience and data integrity. Different suppliers can use the same invoice numbers as intended by business requirements.

### Decision: Invoice Management System Architecture - 2025-08-10

**Context**: The invoice management system needed comprehensive improvements to user experience, including fixing edit/delete functionality, implementing proper notifications, and standardizing the interface. The system had inconsistent user feedback and some operations weren't working properly.

**Options Considered**:

1. **Keep existing modal-based edit system and fix issues**
    - ✅ Pros: Maintains current UX pattern, no major navigation changes
    - ❌ Cons: Complex modal state management, validation parity issues, maintenance overhead
2. **Implement dedicated edit pages with comprehensive notification system**
    - ✅ Pros: Consistent user experience, better validation handling, easier maintenance
    - ❌ Cons: Requires page navigation, more initial development time

**Decision**: Implement dedicated edit pages with comprehensive toastr notification system and SweetAlert2 confirmations

**Rationale**: Dedicated edit pages provide better user experience consistency and easier maintenance. The comprehensive notification system ensures users always receive proper feedback for their actions. This approach aligns with modern web application patterns and provides a more professional user experience.

**Implementation**:

-   Replaced edit modal with direct navigation to `invoices.edit` route
-   Implemented comprehensive toastr notification system across all invoice operations
-   Added SweetAlert2 confirmations for delete operations with proper AJAX handling
-   Enhanced AJAX form submission with proper validation feedback
-   Standardized notification positioning and styling across all views
-   Added session message handling for page load notifications
-   Implemented proper error handling and loading states for all AJAX operations

**Review Date**: 2025-11-10

### Decision: Invoices Edit Flow and Delete Handling - 2025-08-10

**Context**: The invoices list used a modal for editing and the delete button didn’t work reliably with DataTables-rendered rows. Requirement: editing should navigate to a dedicated edit page; confirmations must use SweetAlert2.

**Options Considered**:

1. Keep modal edit and fix AJAX population/delegation
    - ✅ Pros: Inline UX, no navigation
    - ❌ Cons: Duplicates form logic, more JS complexity, validation/UX parity issues with create/edit pages
2. Link to dedicated `edit` page and simplify actions
    - ✅ Pros: Single source of truth for form/validation, simpler JS, consistent with create page
    - ❌ Cons: Page navigation required

**Decision**: Use dedicated `invoices.edit` page for editing; remove modal; implement delegated delete with SweetAlert2.

**Implementation**:

-   Server-side actions column now renders edit as `<a href="route('invoices.edit', $invoice)">`
-   Removed edit modal markup and related JS from `invoices/index.blade.php`
-   Added delegated click handler on `#invoices-table` for `.delete-invoice` with SweetAlert2 and AJAX `DELETE`
-   Reloads DataTable without resetting pagination on successful delete

**Review Date**: 2025-11-10

### Decision: Excel Import Field Mapping Strategy - 2025-08-08

**Context**: Implementing Excel import functionality for additional_documents table. The original Excel files use legacy field names (ito_no, ito_date, ito_created_date, ito_remarks) that don't match the database field names (document_number, document_date, receive_date, remarks). Need to decide how to handle this field mapping while maintaining data integrity.

**Options Considered**:

1. **Require Excel files to match database field names**: Force users to rename columns in their Excel files
    - ✅ Pros: Direct mapping, no transformation needed
    - ❌ Cons: Breaks existing workflows, requires user training, may cause confusion
2. **Implement field mapping in import class**: Map legacy field names to database fields during import
    - ✅ Pros: Maintains backward compatibility, supports existing Excel files, transparent to users
    - ❌ Cons: Additional complexity in import logic, need to maintain mapping documentation

**Decision**: Implement field mapping in import class with clear documentation

**Rationale**: Field mapping provides better user experience by supporting existing Excel file formats without requiring users to modify their files. This approach maintains backward compatibility while ensuring data integrity. The mapping is clearly documented in the template and import instructions.

**Implementation**:

-   Updated `AdditionalDocumentImport.php` to map ito_no→document_number, ito_date→document_date, ito_created_date→receive_date, ito_remarks→remarks
-   Updated `AdditionalDocumentTemplate.php` to use legacy field names in headers and instructions
-   Added clear documentation about field mapping in template and import form
-   Maintained validation logic to check for required fields using legacy names

**Review Date**: 2025-11-08

### Decision: Excel Import Duplicate Handling Strategy - 2025-08-08

**Context**: Need to decide how to handle duplicate document numbers during Excel import. Users may accidentally import the same data multiple times or have overlapping data in their Excel files.

**Options Considered**:

1. **User-configurable duplicate handling**: Provide options for skip, update, or allow duplicates
    - ✅ Pros: Flexible, gives users control over behavior
    - ❌ Cons: Complex UI, potential for user confusion, more error-prone
2. **Always skip duplicates**: Automatically skip any records with duplicate document numbers
    - ✅ Pros: Simple, predictable behavior, prevents data corruption
    - ❌ Cons: Less flexible, may skip valid data if user doesn't understand behavior

**Decision**: Always skip duplicates with clear user communication

**Rationale**: Simplified duplicate handling reduces user confusion and prevents accidental data corruption. The behavior is predictable and safe. Clear communication about this behavior helps users understand what to expect.

**Implementation**:

-   Removed user-facing duplicate handling controls from import form
-   Updated import logic to always check for duplicates and skip them
-   Added clear explanation in import form about automatic duplicate skipping
-   Enhanced import summary to show skipped count for transparency
-   Updated template instructions to mention duplicate handling behavior

**Review Date**: 2025-11-08

### Decision: Excel Import Location Override Strategy - 2025-08-08

**Context**: Need to decide how to handle the cur_loc field during Excel import. Users may have different location codes in their Excel files, but the system needs to ensure all imported records have a consistent location.

**Options Considered**:

1. **Use Excel file values**: Import cur_loc values as they appear in the Excel file
    - ✅ Pros: Preserves user data, flexible
    - ❌ Cons: May create inconsistent data, potential for errors
2. **Always override with specific value**: Set cur_loc to "000HLOG" for all imported records
    - ✅ Pros: Consistent data, prevents errors, matches business requirements
    - ❌ Cons: Ignores user-provided location data

**Decision**: Always override cur_loc with "000HLOG" for all imported records

**Rationale**: Business requirements specify that all imported additional documents should be assigned to "000HLOG" location. This ensures data consistency and prevents location-related errors. The override is clearly documented in the template and import instructions.

**Implementation**:

-   Updated `AdditionalDocumentImport.php` to always set cur_loc to "000HLOG"
-   Added clear documentation in template about automatic location assignment
-   Updated import instructions to explain location override behavior
-   Maintained transparency about this behavior in import summary

**Review Date**: 2025-11-08

### Decision: Route File Organization Strategy - 2025-08-08

**Context**: The monolithic `web.php` file was becoming difficult to maintain as the application grew. Need to organize routes into logical, feature-specific files for better maintainability and team collaboration.

**Options Considered**:

1. **Keep monolithic web.php**: Single file with all routes
    - ✅ Pros: Simple structure, all routes in one place
    - ❌ Cons: Difficult to maintain, hard to find specific routes, poor team collaboration
2. **Feature-based route files**: Split by functionality (admin, additional-docs, etc.)
    - ✅ Pros: Better organization, easier maintenance, improved team collaboration, scalable
    - ❌ Cons: Slightly more complex structure, need to manage includes

**Decision**: Feature-based route files with `require` statements in main web.php

**Rationale**: Feature-based organization provides better maintainability and scalability. It makes it easier for developers to locate and modify specific functionality. The slight complexity increase is outweighed by the significant benefits in maintainability and team collaboration.

**Implementation**:

-   Created `routes/admin.php` for all admin management routes
-   Created `routes/additional-docs.php` for additional documents routes
-   Updated `routes/web.php` to include these files with `require` statements
-   Maintained all existing functionality and route names

**Review Date**: 2025-11-08

### Decision: Additional Documents Controller Location - 2025-08-08

**Context**: Additional Documents functionality is accessible to all authenticated users, not just admins. Need to decide whether to keep the controller in Admin namespace or move it to main Controllers directory.

**Options Considered**:

1. **Keep in Admin namespace**: `app/Http/Controllers/Admin/AdditionalDocumentController.php`
    - ✅ Pros: Consistent with other admin controllers
    - ❌ Cons: Misleading since it's not admin-only, violates separation of concerns
2. **Move to main Controllers directory**: `app/Http/Controllers/AdditionalDocumentController.php`
    - ✅ Pros: Reflects actual access level, better separation of concerns, clearer organization
    - ❌ Cons: Breaks consistency with other CRUD controllers

**Decision**: Move AdditionalDocumentController to main Controllers directory

**Rationale**: Additional Documents is accessible to all authenticated users, not just admins. Keeping it in the Admin namespace is misleading and violates separation of concerns. The main Controllers directory better reflects its actual access level and purpose.

**Implementation**:

-   Moved `AdditionalDocumentController.php` from `app/Http/Controllers/Admin/` to `app/Http/Controllers/`
-   Updated namespace from `App\Http\Controllers\Admin` to `App\Http\Controllers`
-   Updated route references in `routes/additional-docs.php`
-   Cleared route cache to ensure changes take effect

**Review Date**: 2025-11-08

### Decision: Select2 Integration for Document Type Selection - 2025-08-08

**Context**: Need to enhance the document type selection interface in Additional Documents forms. Standard HTML select elements lack search functionality and modern UX patterns.

**Options Considered**:

1. **Standard HTML select**: Use basic select element
    - ✅ Pros: Simple, no additional dependencies
    - ❌ Cons: Poor UX for large lists, no search functionality
2. **Select2 with Bootstrap 4 theme**: Enhanced select with search and modern styling
    - ✅ Pros: Better UX, search functionality, consistent with Bootstrap theme, already available in AdminLTE
    - ❌ Cons: Additional JavaScript dependency

**Decision**: Select2 with Bootstrap 4 theme

**Rationale**: Select2 provides significantly better user experience with search functionality and modern styling. It's already included in AdminLTE plugins, so no additional dependencies are needed. The Bootstrap 4 theme ensures consistency with the existing UI.

**Implementation**:

-   Added Select2 CSS and Bootstrap 4 theme to create/edit forms
-   Added `select2bs4` class to document type select elements
-   Initialized Select2 with Bootstrap 4 theme, placeholder, and clear functionality
-   Applied to both create and edit forms for consistency

**Review Date**: 2025-11-08

### Decision: Laravel 11+ Architecture Configuration - 2025-01-15

**Context**: Laravel 11 introduced new application structure with `bootstrap/providers.php` and `bootstrap/app.php` instead of traditional `config/app.php` for service providers and middleware registration.

**Options Considered**:

1. **Traditional Laravel 10 approach**: Use `config/app.php` for service providers
    - ✅ Pros: Familiar pattern, well-documented
    - ❌ Cons: Not compatible with Laravel 11, would cause errors
2. **Laravel 11+ approach**: Use `bootstrap/providers.php` and `bootstrap/app.php`
    - ✅ Pros: Future-proof, follows Laravel 11 conventions, cleaner structure
    - ❌ Cons: New pattern, less documentation available

**Decision**: Laravel 11+ approach with `bootstrap/providers.php` and `bootstrap/app.php`

**Rationale**: Laravel 11+ is the current version and provides better performance and cleaner architecture. Following the new conventions ensures compatibility and future maintainability.

**Implementation**:

-   Registered DataTables service provider in `bootstrap/providers.php`
-   Added Spatie Permission middleware aliases in `bootstrap/app.php`
-   Updated base Controller to extend proper BaseController with traits

**Review Date**: 2025-07-15

---

### Decision: Local AdminLTE Plugins vs CDN - 2025-01-15

**Context**: Need to implement SweetAlert2 and Toastr for confirmations and notifications. Had to choose between CDN resources and local AdminLTE plugins.

**Options Considered**:

1. **CDN Resources**: Use external CDN links for SweetAlert2 and Toastr
    - ✅ Pros: Easy to implement, always latest versions
    - ❌ Cons: External dependency, potential loading issues, no offline capability
2. **Local AdminLTE Plugins**: Use existing AdminLTE plugin files
    - ✅ Pros: No external dependencies, faster loading, offline capability, consistent with existing AdminLTE theme
    - ❌ Cons: Requires AdminLTE to have the plugins, version dependency

**Decision**: Local AdminLTE plugins for all UI components

**Rationale**: AdminLTE already includes SweetAlert2 and Toastr plugins. Using local resources provides better performance, reliability, and consistency with the existing AdminLTE theme. Eliminates external dependencies and potential loading issues.

**Implementation**:

-   Used `adminlte/plugins/sweetalert2/sweetalert2.min.js` for confirmations
-   Used `adminlte/plugins/toastr/toastr.min.js` and `toastr.min.css` for notifications
-   Applied same pattern to DataTables with local AdminLTE resources

**Review Date**: 2025-07-15

---

### Decision: DataTables Server-Side Processing Pattern - 2025-01-15

**Context**: Implementing DataTables for admin CRUD pages. Need to choose between client-side and server-side processing, and determine the best controller pattern.

**Options Considered**:

1. **Client-side processing**: Load all data and process in browser
    - ✅ Pros: Simple implementation, fast for small datasets
    - ❌ Cons: Poor performance with large datasets, security concerns
2. **Server-side processing with inline AJAX**: Handle AJAX in index method
    - ✅ Pros: Single method, less code
    - ❌ Cons: Mixed concerns, harder to maintain
3. **Server-side processing with separate data method**: Dedicated data endpoint
    - ✅ Pros: Clean separation of concerns, better maintainability, reusable pattern
    - ❌ Cons: Additional route and method

**Decision**: Server-side processing with separate `data()` method pattern

**Rationale**: Provides clean separation between view rendering and data serving. Better performance for large datasets, more maintainable code structure, and consistent pattern across all controllers.

**Implementation**:

-   Added `data()` method to all admin controllers
-   Created separate routes for data endpoints (`/admin/users/data`, etc.)
-   Used consistent DataTables configuration across all views

**Review Date**: 2025-07-15

---

### Decision: SweetAlert2 + Toastr for UI Feedback - 2025-01-15

**Context**: Need to replace default browser confirmations and Bootstrap alerts with modern, user-friendly alternatives.

**Options Considered**:

1. **Bootstrap alerts only**: Keep existing Bootstrap alert system
    - ✅ Pros: Already implemented, consistent with AdminLTE
    - ❌ Cons: Not user-friendly, requires page reload, limited customization
2. **SweetAlert2 only**: Use SweetAlert2 for both confirmations and notifications
    - ✅ Pros: Beautiful UI, highly customizable
    - ❌ Cons: Overkill for simple notifications, more complex setup
3. **SweetAlert2 + Toastr combination**: SweetAlert2 for confirmations, Toastr for notifications
    - ✅ Pros: Best of both worlds, appropriate tool for each use case, excellent UX
    - ❌ Cons: Two libraries to manage

**Decision**: SweetAlert2 for confirmations + Toastr for notifications

**Rationale**: SweetAlert2 excels at confirmations with beautiful dialogs, while Toastr is perfect for non-intrusive notifications. This combination provides the best user experience with appropriate tools for each use case.

**Implementation**:

-   SweetAlert2 for delete confirmations with custom styling
-   Toastr for success/error notifications and flash messages
-   AJAX integration for smooth user experience without page reloads

**Review Date**: 2025-07-15

---

### Decision: Projects CRUD Modal Interface - 2025-08-07

**Context**: Implementing CRUD operations for projects table. Need to choose between traditional page-based forms and modal-based interface for create/edit operations.

**Options Considered**:

1. **Traditional page-based forms**: Separate create and edit pages
    - ✅ Pros: Familiar pattern, full page space for forms, simple implementation
    - ❌ Cons: Page navigation required, slower user experience, more files to maintain
2. **Modal-based interface**: Bootstrap modals for create/edit operations
    - ✅ Pros: No page navigation, faster UX, inline editing, fewer files
    - ❌ Cons: Limited space for complex forms, more complex JavaScript

**Decision**: Modal-based interface with AJAX form submission

**Rationale**: Projects have simple forms (code, owner, location, status) that work well in modals. Modal interface provides better user experience with no page navigation and faster operations. AJAX submission eliminates page reloads.

**Implementation**:

-   Created Bootstrap modal with dynamic form for create/edit operations
-   Implemented AJAX form submission with proper error handling
-   Updated ProjectController to handle AJAX requests with JSON responses
-   Removed create/edit routes and blade files
-   Enhanced DataTables actions with modal data attributes

**Review Date**: 2026-02-07

---

### Decision: Checkbox Validation Pattern - 2025-08-07

**Context**: Handling checkbox fields in forms, specifically the `is_active` field in projects. Need to choose proper validation approach for checkbox fields that may not be present in request.

**Options Considered**:

1. **Boolean validation**: Use `['boolean']` validation rule
    - ✅ Pros: Strict type checking, clear validation intent
    - ❌ Cons: Fails when checkbox is unchecked (field not present in request)
2. **Nullable boolean validation**: Use `['nullable', 'boolean']` validation rule
    - ✅ Pros: Allows field to be absent, still validates when present
    - ❌ Cons: Still may cause issues with Laravel's boolean validation
3. **No validation + manual handling**: Remove validation, use `$request->has()`
    - ✅ Pros: Simple and reliable, handles checkbox behavior correctly
    - ❌ Cons: No server-side validation for the field

**Decision**: No validation for checkbox fields, manual handling with `$request->has()`

**Rationale**: Checkboxes have unique behavior - when unchecked, they don't send any value in the request. Using `$request->has('is_active')` correctly handles this by returning `true` when checked and `false` when unchecked, which is the expected behavior.

**Implementation**:

-   Removed `is_active` validation from store and update methods
-   Used `$request->has('is_active')` to determine boolean value
-   Applied same pattern to other checkbox fields in the system

**Review Date**: 2026-02-07

---

### Decision: User Interface Enhancement Strategy - 2025-08-07

**Context**: Need to improve user experience by enhancing navigation, displaying user context information, and providing self-service password management capabilities.

**Options Considered**:

1. **Traditional navbar**: Keep navbar as-is with basic functionality
    - ✅ Pros: Simple, no layout changes required
    - ❌ Cons: Poor user experience, no context information, limited functionality
2. **Enhanced navbar with fixed positioning**: Implement fixed navbar with user location display
    - ✅ Pros: Better UX, always accessible navigation, user context information
    - ❌ Cons: Requires CSS adjustments, potential layout issues
3. **Separate profile management**: Create dedicated profile section with password management
    - ✅ Pros: Organized functionality, proper validation, security best practices
    - ❌ Cons: Additional complexity, more routes and views

**Decision**: Enhanced navbar with fixed positioning + separate profile management

**Rationale**: Fixed navbar provides better user experience by keeping navigation always accessible. User location display provides important context. Separate profile management ensures proper security and validation for password changes.

**Implementation**:

-   Added `fixed-top` class to navbar with CSS adjustments for proper layout
-   Enhanced navbar to display user's department location code using `department_location_code` accessor
-   Created ProfileController with change password functionality and current password verification
-   Added profile routes and views with proper validation and user feedback

**Review Date**: 2026-02-07

### Invoice Edit Interface: Page vs. Modal - 2025-08-10

**Context**: The invoices list page had an edit modal that was not functioning properly and created a poor user experience.

**Decision**: Replace the edit modal with direct navigation to a dedicated edit page.

**Rationale**:

-   **User Experience**: Dedicated edit pages provide more space for complex forms and better mobile experience
-   **Maintainability**: Simpler code structure without modal state management
-   **Consistency**: Aligns with Laravel's standard resource controller pattern
-   **Performance**: No need to load form data via AJAX for modal display

**Implementation**: Updated InvoiceController actions column to render direct links instead of modal triggers, removed modal markup and JavaScript.

**Status**: ✅ Implemented

### Toastr Integration Strategy for Invoices Management - 2025-08-10

**Context**: The invoices management feature had inconsistent notification systems, mixing SweetAlert2 and toastr, leading to poor user experience.

**Decision**: Standardize on toastr for all user feedback and notifications across invoice operations.

**Rationale**:

-   **Consistency**: Single notification system provides better user experience
-   **Toastr Advantages**: Non-blocking, auto-dismissing, better for form feedback
-   **SweetAlert2 Use Case**: Reserved for critical confirmations (delete operations)
-   **AJAX Integration**: Toastr works better with AJAX form submissions

**Implementation**:

-   Added comprehensive toastr initialization and configuration
-   Implemented AJAX form submission with toastr feedback
-   Enhanced session message handling for redirect-based operations
-   Standardized error handling and validation feedback

**Status**: ✅ Implemented
