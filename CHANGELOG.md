# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.4] - 2025-11-24

### Fixed
- **CRITICAL: Email Profile Switching Not Working**: Fixed critical bug where sender credentials were not being passed properly to email closure, causing all emails to be sent from the default email address regardless of selected profile
- **Closure Scope Issue**: Resolved PHP closure scope issue where `$this->senderCredentials` was not accessible inside the `Mail::send()` closure
- **Method Parameter Passing**: Fixed sender credentials not being passed to `sendToRecipient()` method

### Technical Improvements
- Modified `sendToRecipient()` method to accept sender credentials as parameter
- Updated `Mail::send()` closure to properly receive sender credentials via `use` statement
- Added comprehensive logging to track sender credentials throughout the email sending process
- Enhanced error tracking for debugging email sending issues

## [1.2.3] - 2025-11-24

### Fixed
- **Email Profile Switching Issue**: Fixed critical bug where switching between sender profiles (e.g., "DEV EMAIL" to "Allen") would only change the display name while the actual sender email remained from the previous profile
- **SMTP Configuration Caching**: Resolved configuration caching issues that prevented proper switching between different SMTP profiles
- **Sender Credentials Handling**: Improved sender credentials preparation to ensure both name and email are properly passed for all sender types (config-based and database)

### Enhanced
- **Logging**: Added comprehensive logging throughout the email sending process for better debugging
- **SMTP Configuration Management**: Enhanced configuration handling with proper cache clearing between sender switches
- **Error Handling**: Improved error handling and validation for sender credentials

### Technical Improvements
- Enhanced `selectSender()` method with better logging and user feedback
- Fixed sender credentials preparation for both config-based and database senders
- Improved SMTP configuration reset mechanism in background jobs
- Added detailed from address handling with proper fallbacks

### Files Modified
- `src/Livewire/MassMailer.php` - Enhanced sender selection and credential preparation
- `src/Jobs/SendMassMailJob.php` - Improved SMTP configuration and email sending
- `composer.json` - Version updated to 1.2.3

### Files Modified in v1.2.4
- `src/Jobs/SendMassMailJob.php` - Fixed closure scope issue and sender credentials passing
- `composer.json` - Version updated to 1.2.4
- `CHANGELOG.md` - Added v1.2.4 release notes

## [1.2.2] - Previous Release
- Previous stable release with mass mailer functionality
