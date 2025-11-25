# Automatic File Upload from CSV Feature

## Overview

The mass mailer now includes an **automatic file upload feature** that solves the cross-platform compatibility issue when uploading CSV files with attachment references between Windows development environments and Linux production servers.

## The Problem This Solves

Previously, when you uploaded a CSV file containing Windows file paths like `C:\Users\Dept. of Agriculture\Downloads\Book1.xlsx`, the mass mailer would:

- ✅ **Work locally on Windows**: File paths existed, attachments were included
- ❌ **Fail on Linux servers**: File paths didn't exist, attachments were skipped

## How It Works

### 1. CSV Processing
When processing a CSV with attachment paths:

```csv
email,first_name,attachments
user1@example.com,John,C:\Users\Dept. of Agriculture\Documents\report.pdf
user2@example.com,Jane,documents/image.jpg
```

### 2. Automatic Upload Process
1. **File Path Check**: System checks if the file exists on the server
2. **Upload Attempt**: If file doesn't exist locally but is accessible (e.g., network drive), attempts to upload
3. **Temporary Storage**: Successfully uploaded files are stored in `storage/app/temp_attachments/`
4. **Path Replacement**: Original path is replaced with server-side temporary path
5. **Email Attachment**: Temporary file is used for email attachment

### 3. Cleanup Process
- **After Email Sending**: Temporary files are automatically cleaned up
- **Configurable Timeout**: Default 1 hour before cleanup (configurable)
- **Smart Cleanup**: Only deletes uploaded files, preserves original auto-detected files

## Configuration

Add these settings to your `.env` file:

```env
# Enable/disable automatic file upload from CSV
MASS_MAILER_AUTO_UPLOAD_FROM_CSV=true

# Hours before temporary files are cleaned up
MASS_MAILER_TEMP_CLEANUP_HOURS=1

# Maximum attachment size (KB)
MASS_MAILER_MAX_ATTACHMENT_SIZE=1024
```

Or modify `config/mass-mailer.php`:

```php
'attachments' => [
    'max_size' => 1024,
    'auto_upload_from_csv' => true,
    'temp_cleanup_hours' => 1,
    // ... other settings
],
```

## Usage Examples

### Example 1: Network Drive Access
If your Windows development machine has access to network drives:

```csv
email,attachments
user@example.com,\\network-drive\shared\documents\report.pdf
```

The system will:
1. Detect the file is not on the server
2. Attempt to read from the network path
3. Upload to server if accessible
4. Use uploaded file for email attachment

### Example 2: Cross-Platform Workflow
1. **Local Development (Windows)**:
   - CSV contains: `C:\Users\Dept. of Agriculture\Downloads\file.pdf`
   - Works because file exists locally

2. **Production Server (Linux)**:
   - Same CSV uploaded
   - System tries to upload file if accessible
   - Falls back gracefully if not accessible

## Security Considerations

### File Access Control
- Files must be readable by the web server process
- Only accessible network drives or shared folders can be uploaded
- Temporary files are stored outside the web root

### Cleanup Safety
- Automatic cleanup prevents file accumulation
- Only uploaded files are deleted, not original files
- Configurable cleanup timeout

## Logging and Monitoring

The system provides detailed logging:

```php
// Successful upload
Log::info('Successfully uploaded attachment file', [
    'recipient_index' => 0,
    'original_path' => 'C:\path\to\file.pdf',
    'uploaded_path' => '/storage/app/temp_attachments/file_1234567890abcdef.pdf',
    'file_size' => 1024000
]);

// File not accessible
Log::warning('File not accessible for upload', [
    'recipient_index' => 0,
    'file_path' => 'C:\path\to\nonexistent.pdf',
    'accessible' => false
]);
```

## Testing

Run the test script to verify functionality:

```bash
php test-auto-file-upload.php
```

This will:
- Check configuration settings
- Test file upload scenarios
- Verify cleanup process
- Display diagnostic information

## Benefits

1. **Cross-Platform Compatibility**: Works seamlessly between Windows dev and Linux servers
2. **Network Drive Support**: Handles files on network drives and shared folders
3. **Automatic Fallback**: Gracefully handles files that can't be accessed
4. **Temporary Storage**: No permanent storage changes required
5. **Configurable**: Easy to enable/disable and customize behavior
6. **Automatic Cleanup**: Prevents file accumulation on server

## Troubleshooting

### Files Not Being Uploaded
1. Check `MASS_MAILER_AUTO_UPLOAD_FROM_CSV=true` in `.env`
2. Verify file permissions and accessibility
3. Check Laravel logs for detailed error messages
4. Ensure temp directory is writable: `storage/app/temp_attachments/`

### Cleanup Not Working
1. Verify cleanup hours setting: `MASS_MAILER_TEMP_CLEANUP_HOURS`
2. Check if temp directory exists and is writable
3. Review job logs for cleanup activity

### Performance Considerations
- File uploads happen during CSV processing
- Large files may slow down the initial processing
- Consider adjusting batch sizes for large recipient lists
- Monitor disk space in temp directory

## Migration from Previous Version

This feature is **backward compatible**:
- Existing CSV files without attachments work unchanged
- Only affects CSV files with attachment paths
- Can be disabled via configuration if needed
- Original auto-detected file behavior preserved

## Future Enhancements

Potential improvements:
- Support for cloud storage URLs (S3, Google Drive, etc.)
- Compression of uploaded files
- Virus scanning of uploaded files
- Upload progress indicators
- Retry mechanisms for failed uploads
