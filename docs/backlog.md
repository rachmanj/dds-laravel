# DDS Laravel Development Backlog

## 🎯 **Planned Features**

### **Distribution System Enhancements**

-   [ ] **Distribution Workflow Status Management**

    -   **Priority**: High
    -   **Description**: Enhance distribution workflow with proper status transitions and user notifications
    -   **Components**: Status management, workflow transitions, email notifications, progress tracking
    -   **Estimated Effort**: 2-3 days
    -   **Dependencies**: Distribution model, user notification system, email configuration

-   [ ] **Distribution Bulk Operations**

    -   **Priority**: Medium
    -   **Description**: Add bulk operations for multiple distributions (approve, reject, send, etc.)
    -   **Components**: Bulk selection interface, batch operations, progress indicators
    -   **Estimated Effort**: 1-2 days
    -   **Dependencies**: Distribution list interface, AJAX operations

-   [x] **Processing Analytics Dashboard** ✅ **COMPLETED**

    -   **Priority**: Medium
    -   **Description**: Create analytics dashboard for document processing efficiency tracking across departments
    -   **Components**: ECharts visualization, processing time calculation, department efficiency scoring, filtering
    -   **Actual Effort**: 3 hours
    -   **Implementation**: Completed 2025-10-03 with backend service, frontend dashboard, and sample data

-   [ ] **Distribution Document Preview**

    -   **Priority**: Low
    -   **Description**: Add document preview functionality for invoices and additional documents
    -   **Components**: Document viewer, file handling, preview modal
    -   **Estimated Effort**: 1-2 days
    -   **Dependencies**: Document storage system, file type support

### **Invoice System Enhancements**

-   [ ] **Invoice Field Synchronization Improvements**

    -   **Priority**: Medium
    -   **Description**: Enhance amount field synchronization to prevent manual `formatNumber()` calls
    -   **Components**: Auto-sync on field changes, improved validation, better error handling
    -   **Estimated Effort**: 2-3 hours
    -   **Dependencies**: JavaScript event handling, form validation system

-   [ ] **Invoice Edit Form Validation Enhancement**

    -   **Priority**: Low
    -   **Description**: Add real-time validation feedback and improved error messages
    -   **Components**: Client-side validation, real-time feedback, enhanced error display
    -   **Estimated Effort**: 1-2 days
    -   **Dependencies**: JavaScript validation, form structure

-   [ ] **Invoice Edit History Tracking**

    -   **Priority**: Low
    -   **Description**: Track and display invoice edit history with user and timestamp information
    -   **Components**: Audit trail table, history display, user tracking
    -   **Estimated Effort**: 2-3 days
    -   **Dependencies**: Database schema, user authentication system

### **Database & Query System Enhancements**

-   [ ] **MCP MySQL Configuration Fix**

    -   **Priority**: High
    -   **Description**: Resolve environment variable resolution in MCP configuration for direct database access
    -   **Components**: Fix `.cursor-mcp.json` configuration, test MCP integration, document working setup
    -   **Estimated Effort**: 1-2 hours
    -   **Dependencies**: Environment variable resolution, MCP server configuration

-   [ ] **Database Query Utilities**

    -   **Priority**: Medium
    -   **Description**: Create reusable artisan commands for common database queries
    -   **Components**: Complete `ListUsersByProject` command, create additional query utilities, document patterns
    -   **Estimated Effort**: 1-2 days
    -   **Dependencies**: Laravel artisan commands, database schema understanding

-   [ ] **User-Project Management Interface**

    -   **Priority**: Medium
    -   **Description**: Web interface for managing user-project assignments and queries
    -   **Components**: User management views, project assignment interface, query results display
    -   **Estimated Effort**: 3-4 days
    -   **Dependencies**: Database query utilities, permission system

-   [ ] **Database Query Documentation**

    -   **Priority**: Low
    -   **Description**: Comprehensive documentation of database query patterns and best practices
    -   **Components**: Query reference guide, troubleshooting procedures, performance optimization tips
    -   **Estimated Effort**: 1-2 days
    -   **Dependencies**: Database schema documentation, query utilities

-   [ ] **Database Performance Monitoring**

    -   **Priority**: Low
    -   **Description**: Monitor and optimize database query performance
    -   **Components**: Query logging, performance metrics, optimization recommendations
    -   **Estimated Effort**: 2-3 days
    -   **Dependencies**: Database monitoring tools, performance analysis

### **API Enhancement & Integration**

-   [ ] **Webhook Support**

    -   **Priority**: Medium
    -   **Description**: Real-time notifications when invoice status changes
    -   **Components**: Webhook endpoints, event system, retry logic
    -   **Estimated Effort**: 3-4 days

-   [ ] **Bulk Operations API**

    -   **Priority**: Medium
    -   **Description**: Batch invoice updates and status changes via API
    -   **Components**: Bulk endpoints, transaction handling, progress tracking
    -   **Estimated Effort**: 2-3 days

-   [ ] **Advanced Filtering & Search**

    -   **Priority**: Low
    -   **Description**: Complex query builders with multiple criteria and full-text search
    -   **Components**: Query builder, search indexing, filter combinations
    -   **Estimated Effort**: 4-5 days

-   [ ] **Data Export Capabilities**

    -   **Priority**: Medium
    -   **Description**: CSV/Excel export capabilities for large datasets
    -   **Components**: Export controllers, background processing, file generation
    -   **Estimated Effort**: 2-3 days

-   [ ] **Real-time Updates**

    -   **Priority**: Low
    -   **Description**: WebSocket support for live data synchronization
    -   **Components**: WebSocket server, real-time events, client libraries
    -   **Estimated Effort**: 5-6 days

-   [ ] **Enhanced User Accountability**

    -   **Priority**: Medium
    -   **Description**: Track user actions across all invoice operations
    -   **Components**: User activity logs, audit trail, role-based API access
    -   **Estimated Effort**: 3-4 days

-   [ ] **API Documentation Automation**

    -   **Priority**: Low
    -   **Description**: Automatically generate API documentation from code annotations
    -   **Components**: OpenAPI/Swagger generation, code annotation parsing, auto-updating docs
    -   **Estimated Effort**: 4-5 days

-   [ ] **Interactive API Testing Interface**

    -   **Priority**: Low
    -   **Description**: Web-based API testing interface for developers
    -   **Components**: Interactive testing UI, request builder, response viewer, test history
    -   **Estimated Effort**: 3-4 days

-   [ ] **API Versioning Strategy**

    -   **Priority**: Medium
    -   **Description**: Implement proper API versioning for backward compatibility
    -   **Components**: Version routing, deprecation warnings, migration guides
    -   **Estimated Effort**: 2-3 days

### **Supplier Management Enhancements**

-   [ ] **Scheduled Supplier Synchronization**

    -   **Priority**: Medium
    -   **Description**: Automatically sync suppliers from external API on a schedule
    -   **Components**: Laravel scheduler, cron jobs, notification system
    -   **Estimated Effort**: 2-3 days

-   [ ] **Supplier Import History & Rollback**

    -   **Priority**: Low
    -   **Description**: Track import history and allow rollback of specific imports
    -   **Components**: Import history table, rollback functionality, audit trail
    -   **Estimated Effort**: 3-4 days

-   [ ] **Advanced Supplier Mapping Configuration**

    -   **Priority**: Low
    -   **Description**: Allow admins to configure field mappings for different API endpoints
    -   **Components**: Configuration interface, mapping rules, validation
    -   **Estimated Effort**: 2-3 days

### **Import System Enhancements**

-   [ ] **Advanced Excel Import Validation**

    -   **Priority**: Medium
    -   **Description**: Enhanced validation rules and user feedback for Excel imports
    -   **Components**: Custom validation rules, detailed error reporting, preview functionality
    -   **Estimated Effort**: 2-3 days

-   [ ] **Import Template Builder**

    -   **Priority**: Low
    -   **Description**: Allow users to create custom import templates for different document types
    -   **Components**: Template builder interface, field mapping configuration, validation rules
    -   **Estimated Effort**: 4-5 days

-   [ ] **Bulk Import Progress Tracking**

    -   **Priority**: Medium
    -   **Description**: Real-time progress tracking for large import operations
    -   **Components**: Progress bars, status updates, background job processing
    -   **Estimated Effort**: 2-3 days

-   [ ] **Import Data Preview & Validation**

    -   **Priority**: Medium
    -   **Description**: Show preview of import data before processing with validation feedback
    -   **Components**: Preview table, validation highlighting, user confirmation
    -   **Estimated Effort**: 3-4 days

### **File Upload System Enhancements**

-   [ ] **Progressive File Upload for Large Files**

    -   **Priority**: Medium
    -   **Description**: Implement chunked file uploads for files larger than 50MB
    -   **Components**: Chunked upload controller, progress tracking, file reassembly
    -   **Estimated Effort**: 3-4 days

-   [ ] **File Compression & Optimization**

    -   **Priority**: Low
    -   **Description**: Optional file compression for storage optimization while maintaining quality
    -   **Components**: Compression library integration, user choice options, quality settings
    -   **Estimated Effort**: 2-3 days

-   [ ] **Cloud Storage Integration**

    -   **Priority**: Medium
    -   **Description**: Integrate with cloud storage providers (AWS S3, Google Cloud) for scalable storage
    -   **Components**: Cloud storage drivers, configuration interface, migration tools
    -   **Estimated Effort**: 4-5 days

-   [ ] **File Versioning & History**

    -   **Priority**: Low
    -   **Description**: Track file versions and allow users to view/restore previous versions
    -   **Components**: Version tracking system, file comparison, restore functionality
    -   **Estimated Effort**: 3-4 days

-   [ ] **Advanced File Type Support**

    -   **Priority**: Low
    -   **Description**: Add support for additional file types (CAD files, video files, etc.)
    -   **Components**: MIME type validation, file preview, specialized handling
    -   **Estimated Effort**: 2-3 days

### **Print & Export Enhancements**

-   [ ] **Advanced Transmittal Advice Customization**

    -   **Priority**: Medium
    -   **Description**: Allow users to customize Transmittal Advice templates with company branding
    -   **Components**: Template editor, custom fields, branding options
    -   **Estimated Effort**: 3-4 days

-   [ ] **Batch Printing for Multiple Distributions**

    -   **Priority**: Medium
    -   **Description**: Print multiple Transmittal Advice documents in batch
    -   **Components**: Multi-select interface, batch print controller, PDF generation
    -   **Estimated Effort**: 2-3 days

-   [ ] **Transmittal Advice PDF Export**
    -   **Priority**: Low
    -   **Description**: Export Transmittal Advice as downloadable PDF files
    -   **Components**: PDF generation library, download functionality
    -   **Estimated Effort**: 1-2 days

## ✅ **Recently Completed Features**

### **File Upload Size Enhancement** ✅ **COMPLETED**

**Implementation Date**: 2025-01-27  
**Status**: Fully implemented and operational

**Core Features Delivered**:

-   **Comprehensive Size Enhancement**: All file upload limits increased from 2-10MB to 50MB per file
-   **System-Wide Consistency**: Same 50MB limit across all upload interfaces (invoices, documents, Excel imports)
-   **Backend Validation Updates**: All Laravel validation rules updated to `max:51200` (50MB)
-   **Frontend Synchronization**: JavaScript validation updated to match backend limits across all interfaces
-   **User Interface Updates**: Help text and error messages updated to reflect new 50MB limits
-   **Performance Optimization**: Efficient file handling for larger uploads with Laravel's built-in capabilities

**Controllers Updated**:

-   **InvoiceAttachmentController**: Invoice attachments (5MB → 50MB, 10x increase)
-   **AdditionalDocumentController**: Document attachments (2MB → 50MB, 25x increase) and Excel imports (10MB → 50MB, 5x increase)
-   **InvoiceController**: Bulk invoice Excel imports (10MB → 50MB, 5x increase)

**Frontend Templates Updated**:

-   **invoices/show.blade.php**: Invoice attachment upload interface with 50MB help text and validation
-   **invoices/attachments/index.blade.php**: Modal upload validation updated to 50MB
-   **additional_documents/import.blade.php**: Excel import validation updated to 50MB

**Business Impact**:

-   **User Productivity**: Reduced need to split or compress large business documents
-   **Process Efficiency**: Streamlined document upload workflows without size constraints
-   **User Satisfaction**: Better support for real-world business document sizes
-   **System Adoption**: Improved user experience leads to increased system usage
-   **Data Integrity**: Complete documents uploaded without compression or splitting

**Technical Achievements**:

-   **Validation Consistency**: All validation rules updated simultaneously across the system
-   **Frontend-Backend Sync**: Complete synchronization of client and server validation
-   **Performance Optimization**: Efficient file handling for larger uploads
-   **User Experience**: Clear communication of new limits and consistent behavior

---

### **Document Status Management System** ✅ **COMPLETED**

**Implementation Date**: 2025-01-27  
**Status**: Fully implemented and operational

**Core Features Delivered**:

-   **Permission-Based Access Control**: `reset-document-status` permission with admin/superadmin role assignment
-   **Comprehensive Status Management**: Individual and bulk status reset operations with full audit logging
-   **Advanced Filtering & Search**: Status-based filtering, document type filtering, and search functionality
-   **Professional UI/UX**: AdminLTE integration with responsive tables, modals, and status overview cards
-   **Audit Trail Integration**: Complete status change tracking via DistributionHistory model
-   **Department-Based Access**: Non-admin users see only their department documents
-   **Safety Restrictions**: Bulk operations limited to safe status transitions (`unaccounted_for` → `available`)

**Technical Achievements**:

-   **Controller Architecture**: `DocumentStatusController` with comprehensive status management
-   **Route Integration**: Three API endpoints with proper permission protection
-   **Data Relationships**: Correct eager loading and field references for all document types
-   **JavaScript Integration**: AJAX operations with real-time feedback and DataTables
-   **Layout Architecture**: Proper Blade template structure with `layouts.main` extension
-   **Database Architecture**: Flexible constraint management with nullable foreign keys
-   **Audit System**: Complete status change tracking with all required fields
-   **System Recovery**: Comprehensive issue resolution from complete failure to full operation

**Business Impact**:

-   **Workflow Continuity**: Missing/damaged documents can be found and redistributed
-   **Compliance**: Complete audit trails for regulatory requirements
-   **Administrative Efficiency**: Bulk operations for handling multiple found documents
-   **Data Integrity**: Proper status management prevents workflow corruption

---

## 🚀 **Future Enhancements & Features**

### **Distribution System Enhancements**

#### **Advanced Document Status Management**

-   **Real-time Status Updates**: WebSocket integration for live status changes across all users
-   **Status Change Notifications**: Email/SMS alerts when document status changes
-   **Advanced Bulk Operations**: Enhanced bulk status management with custom status transition rules
-   **Status History Timeline**: Visual timeline of all status changes with user attribution
-   **Status Validation Rules**: Business rule engine for complex workflow validation
-   **Status Change Workflows**: Approval workflows for sensitive status changes
-   **Status Analytics Dashboard**: Advanced reporting on status change patterns and trends

#### **Distribution Workflow Optimization**

-   **Automated Routing**: AI-powered document routing suggestions based on content analysis
-   **Workflow Templates**: Pre-configured distribution workflows for common scenarios
-   **Parallel Processing**: Support for multiple departments processing same distribution
-   **Escalation Rules**: Automatic escalation for overdue or stuck distributions
-   **Workflow Analytics**: Advanced metrics for workflow optimization and bottleneck identification

#### **Performance & Scalability**

-   **Redis Caching**: Cache frequently accessed distribution data for improved performance
-   **Database Sharding**: Horizontal scaling for high-volume distribution systems
-   **Queue Processing**: Background job processing for distribution operations
-   **CDN Integration**: Content delivery network for document attachments
-   **Load Balancing**: Multiple server support for high-availability deployments

### **Dashboard & Analytics Enhancements**

#### **Advanced Business Intelligence**

-   **Predictive Analytics**: Machine learning models for distribution timing predictions
-   **Trend Analysis**: Historical data analysis for seasonal patterns and optimization
-   **Custom Report Builder**: Drag-and-drop report creation for business users
-   **Data Export**: Multiple format support (PDF, Excel, CSV) for reporting
-   **Real-time Dashboards**: Live updates with WebSocket integration

#### **Mobile Experience**

-   **Native Mobile App**: React Native or Flutter app for mobile distribution management
-   **Push Notifications**: Real-time alerts for distribution status changes
-   **Offline Capability**: Basic functionality without internet connection
-   **Mobile-Optimized Views**: Touch-friendly interfaces for mobile devices
-   **QR Code Integration**: Quick document scanning and identification

### **API & Integration Enhancements**

#### **Advanced API Features**

-   **GraphQL API**: Flexible data querying for complex integration needs
-   **Webhook System**: Real-time notifications for external systems
-   **API Versioning**: Comprehensive version management for API evolution
-   **Rate Limiting Dashboard**: Visual monitoring of API usage patterns
-   **API Analytics**: Detailed usage analytics and performance metrics

#### **Third-Party Integrations**

-   **ERP System Integration**: SAP, Oracle, or other ERP system connectors
-   **Document Management Systems**: SharePoint, Google Drive, or Dropbox integration
-   **Accounting Software**: QuickBooks, Xero, or other accounting system integration
-   **Shipping & Logistics**: FedEx, UPS, or DHL integration for physical document tracking
-   **Communication Platforms**: Slack, Microsoft Teams, or email integration

### **Security & Compliance Enhancements**

#### **Advanced Security Features**

-   **Multi-Factor Authentication**: SMS, email, or authenticator app support
-   **IP Whitelisting**: Geographic and network-based access restrictions
-   **Audit Trail Enhancement**: Comprehensive logging of all system activities
-   **Data Encryption**: End-to-end encryption for sensitive document data
-   **Compliance Reporting**: Automated compliance reports for regulatory requirements

#### **Data Protection & Privacy**

-   **GDPR Compliance**: European data protection regulation compliance
-   **Data Retention Policies**: Automated data archiving and deletion
-   **Privacy Controls**: User consent management and data access controls
-   **Backup & Recovery**: Automated backup systems with disaster recovery
-   **Data Masking**: Sensitive data protection in logs and exports

### **User Experience Improvements**

#### **Interface Enhancements**

-   **Dark Mode**: User preference for dark/light theme selection
-   **Customizable Dashboard**: User-configurable dashboard layouts
-   **Keyboard Shortcuts**: Power user shortcuts for common operations
-   **Accessibility Improvements**: WCAG compliance and screen reader support
-   **Multi-language Support**: Internationalization for global deployments

#### **Workflow Simplification**

-   **Smart Forms**: Auto-completion and validation for common data entry
-   **Bulk Operations**: Mass actions for multiple documents or distributions
-   **Drag & Drop**: Visual document management with drag and drop
-   **Search & Filter**: Advanced search with saved filters and favorites
-   **User Onboarding**: Interactive tutorials and guided tours for new users

### **Business Process Automation**

#### **Workflow Automation**

-   **Approval Workflows**: Configurable approval chains for distributions
-   **Conditional Logic**: Business rule engine for complex workflow decisions
-   **SLA Monitoring**: Service level agreement tracking and alerts
-   **Escalation Management**: Automatic escalation for overdue items
-   **Workflow Templates**: Pre-built workflows for common business processes

#### **Integration Automation**

-   **Data Synchronization**: Automated data sync with external systems
-   **Document Processing**: OCR and automated document classification
-   **Email Integration**: Automated email processing and response
-   **Calendar Integration**: Outlook, Google Calendar integration for scheduling
-   **Task Management**: Integration with project management tools

### **Monitoring & Maintenance**

#### **System Monitoring**

-   **Performance Monitoring**: Real-time system performance metrics
-   **Error Tracking**: Comprehensive error logging and alerting
-   **User Activity Monitoring**: User behavior analytics and insights
-   **System Health Dashboard**: Overall system status and health indicators
-   **Capacity Planning**: Resource usage forecasting and planning

#### **Maintenance & Support**

-   **Automated Testing**: Comprehensive test suite for regression prevention
-   **Deployment Automation**: CI/CD pipeline for automated deployments
-   **Backup Automation**: Automated backup verification and testing
-   **Documentation Generation**: Auto-generated API and system documentation
-   **Support Ticket Integration**: Integration with help desk systems

## 📅 **Implementation Timeline**

### **Phase 1: Q1 2026 (High Priority)**

-   Real-time status updates with WebSocket integration
-   Advanced document status management features
-   Performance optimization and caching implementation
-   Enhanced security features and compliance reporting

### **Phase 2: Q2 2026 (Medium Priority)**

-   Mobile application development
-   Advanced analytics and business intelligence
-   Third-party system integrations
-   Workflow automation and business rule engine

### **Phase 3: Q3 2026 (Low Priority)**

-   AI-powered features and machine learning
-   Advanced reporting and custom dashboards
-   Multi-language and internationalization support
-   Comprehensive API ecosystem

## 🎯 **Success Criteria**

### **Technical Metrics**

-   **Performance**: Sub-second response times for all operations
-   **Scalability**: Support for 10,000+ concurrent users
-   **Reliability**: 99.9% uptime with automated failover
-   **Security**: Zero security vulnerabilities in production

### **Business Metrics**

-   **User Adoption**: 90% of target users actively using the system
-   **Process Efficiency**: 50% reduction in distribution processing time
-   **Error Reduction**: 95% reduction in distribution errors
-   **Compliance**: 100% audit trail accuracy and completeness

### **User Experience Metrics**

-   **User Satisfaction**: 4.5+ rating on user experience surveys
-   **Training Time**: 50% reduction in new user training time
-   **Support Requests**: 70% reduction in support ticket volume
-   **Feature Usage**: 80% of advanced features actively used

---

**Last Updated**: 2025-01-27  
**Next Review**: 2025-02-27  
**Status**: 📋 **Planning Phase** - Comprehensive roadmap for future development

## 🚀 **Future Enhancements & Ideas**

### **Distribution Create Page Improvements**

-   [ ] **Implement Dynamic Document Selection based on Document Type**

    -   **Priority**: High
    -   **Description**: Dynamically update the document selection area based on the chosen "Document Type" (Invoice or Additional Document). Display searchable dropdown/list of available documents accordingly.
    -   **Estimated Effort**: 2-3 days
    -   **Dependencies**: Frontend JavaScript, Backend API for document filtering.

-   [ ] **Implement Real-time Search and Filtering for Documents**

    -   **Priority**: Medium
    -   **Description**: Add a search bar within the "Document Selection" area to quickly find documents by invoice number, document number, or other relevant identifiers. Add filters for date range, status, or document type.
    -   **Estimated Effort**: 1-2 days
    -   **Dependencies**: Frontend JavaScript, potentially new API endpoints for filtered document lists.

-   [ ] **Provide Clearer Indication of "Documents currently in your department location"**

    -   **Priority**: Medium
    -   **Description**: Visually grey out or disable documents in the selection list that are not in the user's department, or only display documents available for distribution.
    -   **Estimated Effort**: 0.5-1 day
    -   **Dependencies**: Frontend JavaScript, accurate `cur_loc` data for documents.

-   [ ] **Provide Visual Feedback on Selected Documents**

    -   **Priority**: High
    -   **Description**: As documents are selected, display them in a "Selected Documents" section with an option to remove them, providing a clear overview before distribution creation.
    -   **Estimated Effort**: 1-2 days
    -   **Dependencies**: Frontend JavaScript for managing selected documents.

-   [ ] **Enhance Input Validation and Error Handling**

    -   **Priority**: High
    -   **Description**: Implement clear client-side and server-side validation with real-time feedback for missing required fields or invalid selections.
    -   **Estimated Effort**: 1 day
    -   **Dependencies**: Frontend JavaScript validation, Laravel validation rules.

-   [ ] **Implement User Confirmation Before Distribution Creation**

    -   **Priority**: Medium
    -   **Description**: After the user clicks "Create Distribution", display a confirmation dialog summarizing distribution details and asking for final confirmation.
    -   **Estimated Effort**: 0.5 day
    -   **Dependencies**: Frontend JavaScript for modal/dialog.

-   [ ] **Pre-populate Current User's Department as Origin Department**

    -   **Priority**: Low
    -   **Description**: Automatically pre-populate the "Origin Department" field (or implicitly use the current user's department) and disable it.
    -   **Estimated Effort**: 0.5 day
    -   **Dependencies**: Frontend JavaScript, User department data.

-   [ ] **Allow Multi-document Selection**

    -   **Priority**: Medium
    -   **Description**: Enable multi-selection of documents through checkboxes in a list or a "select all" option for filtered results.
    -   **Estimated Effort**: 1-2 days
    -   **Dependencies**: Frontend JavaScript for handling multiple selections.

-   [ ] **Implement Toastr Notifications for Success/Error Messages**

    -   **Priority**: High
    -   **Description**: Use Toastr notifications to display a success message after a distribution is created and error messages if the creation fails.
    -   **Estimated Effort**: 0.5 day
    -   **Dependencies**: Frontend JavaScript (Toastr library).

-   [ ] **Ability to Unlink/Manage Automatically Included Additional Documents**
    -   **Priority**: Medium
    -   **Description**: Provide an interface on the "Create Distribution" or "Distribution Details" page to view and optionally remove automatically included additional documents.
    -   **Estimated Effort**: 1-2 days
    -   **Dependencies**: Frontend JavaScript, Backend logic for managing linked documents.
