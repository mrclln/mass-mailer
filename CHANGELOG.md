# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2025-11-25

### New Release - Automatic File Upload from CSV

This release introduces automatic file upload functionality to solve cross-platform compatibility issues between Windows development and Linux production environments.

### Added
- **Automatic File Upload from CSV**: New feature that automatically uploads files referenced in CSV when they're not found on the server
- **Cross-Platform Compatibility**: Solves file path issues between Windows (`C:\path\file.pdf`) and Linux (`/path/file.pdf`) environments
- **Network Drive Support**: Handles files accessible via network drives and shared folders
- **Temporary File Storage**: Uploaded files stored temporarily in `storage/app/temp_attachments/` with automatic cleanup
- **Smart Upload Detection**: Only attempts upload for files that don't exist on server but are accessible
- **Configuration Control**: Enable/disable auto-upload via `MASS_MAILER_AUTO_UPLOAD_FROM_CSV` environment variable
- **Configurable Cleanup**: Customizable cleanup timeout via `MASS_MAILER_TEMP_CLEANUP_HOURS` (default: 1 hour)
- **Enhanced Logging**: Comprehensive tracking of upload attempts, successes, and failures
- **Test Script**: Added `test-auto-file-upload.php` for testing and validation
- **Documentation**: Complete `AUTO_UPLOAD_FROM_CSV.md` guide with usage examples and troubleshooting

### Enhanced
- **CSV Processing**: Enhanced `processAttachmentPaths()` method with automatic upload fallback
- **File Handling**: Improved file existence checks with upload attempt for missing files
- **Job Cleanup**: Enhanced `SendMassMailJob` cleanup process to handle uploaded temporary files
- **Error Handling**: Better error handling and logging for file upload scenarios
- **Backward Compatibility**: All existing functionality preserved, new feature is opt-in

### Technical Features
- **Upload Method**: New `tryUploadAttachmentFile()` method handles file reading and server storage
- **Temporary Storage**: Unique filename generation with `uniqid()` for collision prevention
- **Memory Management**: Efficient file reading and storage without memory leaks
- **Permission Handling**: Proper error handling for file access and permission issues
- **Cleanup Automation**: Automatic deletion of temporary files after configurable timeout
- **File Type Support**: Works with all supported attachment types (PDF, DOC, images, etc.)

### Configuration Options
```env
MASS_MAILER_AUTO_UPLOAD_FROM_CSV=true
MASS_MAILER_TEMP_CLEANUP_HOURS=1
MASS_MAILER_MAX_ATTACHMENT_SIZE=1024
```

### Files Added
- `AUTO_UPLOAD_FROM_CSV.md` - Comprehensive documentation
- `test-auto-file-upload.php` - Test script for functionality validation

### Files Modified
- `src/Livewire/MassMailer.php` - Enhanced CSV processing and new upload method
- `src/Jobs/SendMassMailJob.php` - Updated cleanup for uploaded files
- `src/Config/mass-mailer.php` - Added auto-upload configuration options
- `composer.json` - Version updated to 2.1.0 and enhanced description
- `CHANGELOG.md` - Added v2.1.0 release notes

### Benefits
- **Cross-Platform**: Works seamlessly between Windows development and Linux production
- **Network Friendly**: Supports files on network drives and shared folders
- **Zero Configuration**: Works out-of-the-box with sensible defaults
- **Graceful Degradation**: Falls back to original behavior if files can't be uploaded
- **Automatic Cleanup**: Prevents server file accumulation with configurable timeout

## [2.0.0] - 2025-11-25

### Major Release - Complete Mass Mailer Package

This major release consolidates all previous updates and introduces significant new features for comprehensive email campaign management.

### Added
- **MassMailerLogs Component**: New Livewire component for comprehensive log management
- **Advanced Filtering**: Search by recipient, subject, error messages, status, and date ranges
- **Dual Framework Support**: Complete views for both Tailwind CSS and Bootstrap
- **Export Functionality**: CSV and JSON export with filtered data support
- **Log Details Modal**: Comprehensive view of individual email logs
- **Retry Mechanism**: Retry failed emails directly from the interface
- **Dashboard Statistics**: Real-time metrics and success rate tracking
- **Data Management**: Clear old logs with confirmation dialogs
- **Responsive Design**: Mobile-first design for all screen sizes
- **Pagination**: Efficient browsing of large log datasets
- **MassMailerUserTrait**: New comprehensive trait for User model integration
- **User Analytics**: 30+ methods for email analytics and reporting
- **Easy Integration**: Simple trait usage for immediate functionality
- **Advanced Reporting**: Performance trends, failure analysis, recipient tracking
- **Email Validation for Sender Profiles**: Implemented SMTP credential validation before saving new sender profiles
- **Test Email Functionality**: Added automatic test email sending to validate SMTP credentials before profile creation
- **Comprehensive Email Logging**: Implemented full email logging system with user tracking
- **User Activity Tracking**: All email campaigns now logged with user association for analytics
- **Email Statistics**: Added user-level email statistics including success rates and failure tracking
- **MassMailerLog Model**: New Eloquent model for comprehensive email log management

### Enhanced
- **User Experience**: Intuitive interface with professional styling
- **Performance**: Optimized queries with proper indexing
- **Accessibility**: ARIA labels and keyboard navigation support
- **Interactivity**: Real-time filtering and search functionality
- **Security**: User data isolation and authorization checks
- **User Relationships**: Direct relationship methods for logs and senders
- **Time-based Analytics**: Daily, weekly, monthly, and yearly reporting
- **Database Schema**: Added user_id field to mass_mailer_logs table with proper foreign key relationships
- **Job Tracking**: Enhanced SendMassMailJob to log all email attempts with detailed status tracking
- **Error Logging**: Improved error tracking with database persistence for failed email analysis
- **User Analytics**: Added methods for calculating success rates and generating user statistics
- **Audit Trail**: Complete audit trail of all email activities with timestamps and user attribution
- **Error Handling**: Improved error messages for invalid SMTP credentials with clear user feedback
- **System Reliability**: Prevents storing invalid SMTP configurations that would cause email sending failures

### Fixed
- **CRITICAL: Email Profile Switching Not Working**: Fixed critical bug where sender credentials were not being passed properly to email closure, causing all emails to be sent from the default email address regardless of selected profile
- **Closure Scope Issue**: Resolved PHP closure scope issue where `$this->senderCredentials` was not accessible inside the `Mail::send()` closure
- **Method Parameter Passing**: Fixed sender credentials not being passed to `sendToRecipient()` method
- **Email Profile Switching Issue**: Fixed critical bug where switching between sender profiles (e.g., "DEV EMAIL" to "Allen") would only change the display name while the actual sender email remained from the previous profile
- **SMTP Configuration Caching**: Resolved configuration caching issues that prevented proper switching between different SMTP profiles
- **Sender Credentials Handling**: Improved sender credentials preparation to ensure both name and email are properly passed for all sender types (config-based and database)
- **Livewire Component Registration**: Fixed missing MassMailerLogs component registration in service provider causing ComponentNotFoundException

### Technical Features
- **Livewire Integration**: Reactive updates without page refreshes
- **Query Optimization**: Efficient database queries with indexes
- **State Management**: URL query string persistence for filters
- **Memory Management**: Efficient handling of large datasets
- **Error Handling**: Comprehensive error messages and logging
- **Relationship Methods**: massMailerLogs, successfulMassMailerLogs, failedMassMailerLogs
- **Statistics Methods**: getMassMailerStats, getMassMailerSuccessRate, activity summaries
- **Time Analytics**: getMassMailerEmailsSentToday/Week/Month, performance trends
- **Analysis Tools**: Failure analysis, recipient tracking, subject performance
- **Data Management**: Export capabilities, log cleanup, bulk operations
- **Advanced Queries**: Custom scopes, date ranges, status filtering
- **Modified `saveNewSender()` method**: Added credential validation check before database save
- **New `testSenderCredentials()` method**: Implements SMTP testing with temporary configuration
- **Dynamic SMTP Testing**: System temporarily configures SMTP settings, tests, then cleans up
- **Exception Handling**: Comprehensive catch blocks for SMTP transport and general exceptions
- **Configuration Management**: Proper cache clearing and temporary configuration handling

### User Interface
- **Error Alert**: "Invalid Credentials!" alert when SMTP test fails
- **Success Feedback**: "New sender added successfully!" when validation passes
- **Test Email**: Users receive confirmation email proving SMTP configuration works

### Files Modified
- `src/Providers/MassMailerServiceProvider.php` - Fixed Livewire component registration (v2.0.0)
- `src/Livewire/MassMailer.php` - Enhanced sender selection and credential preparation
- `src/Jobs/SendMassMailJob.php` - Improved SMTP configuration and email sending
- `src/Livewire/MassMailerLogs.php` - New comprehensive log management component
- `src/Traits/MassMailerUserTrait.php` - New user analytics trait
- `src/Models/MassMailerLog.php` - New email logging model
- `composer.json` - Version updated to 2.0.0
- `CHANGELOG.md` - Updated with v2.0.0 release notes

## [1.2.2] - Previous Release
- Previous stable release with mass mailer functionality
