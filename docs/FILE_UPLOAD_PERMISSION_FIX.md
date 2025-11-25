# File Upload Permission Fix - Mass Mailer

## Issue Description

The error `Symfony\Component\HttpFoundation\File\Exception\FileException: Could not move the file` occurs when Laravel's file upload system fails to move files from temporary storage to the final destination. This is typically caused by:

1. **Insufficient directory permissions** - The web server cannot write to the storage directories
2. **Missing directory structure** - Required directories don't exist or weren't created properly
3. **File path conflicts** - Filenames contain special characters or are too long
4. **Storage disk misconfiguration** - The Laravel storage disk configuration is incorrect

## Root Cause Analysis

The specific error you're seeing:
```
Could not move the file "C:\laragon\www\test-mailer\storage\app/private\livewire-tmp/zalUkLm7qksLTN0fn4ivWAiFuSz8jd-metaQm9vazEueGxzeA==-.xlsx"
to "C:\laragon\www\test-mailer\storage\app/public/mass_mail/guest/folder_2025-11-25_05-43-07_692541eb3991e\Book1.xlsx"
```

This indicates that:
1. Livewire successfully uploaded the file to its temporary directory
2. Laravel's file handling tried to move it to the final storage location
3. The move operation failed, likely due to permission issues

## Applied Fixes

### 1. Enhanced File Storage Method

**Before (Problematic Code):**
```php
$file->move($folderPath, $fileName);
```

**After (Fixed Code):**
```php
$relativePath = 'mass_mail/' . $folderPath . '/' . $sanitizedFileName;
$storedPath = $file->storeAs('mass_mail/' . $folderPath, $sanitizedFileName, $disk);
```

**Benefits:**
- Uses Laravel's built-in storage system instead of raw PHP `move()`
- Handles file conflicts automatically
- Respects disk configuration settings
- Better error handling and logging

### 2. Robust Directory Creation

**Before:**
```php
if (!is_dir($folderPath)) {
  mkdir($folderPath, 0755, true);
}
```

**After:**
```php
foreach ($directories as $dir) {
  if (!is_dir($dir['path'])) {
    $created = mkdir($dir['path'], 0755, true);
    if ($created) {
      if (function_exists('chmod')) {
        chmod($dir['path'], 0755);
      }
    }
  }
}
```

**Benefits:**
- Explicit permission setting with `chmod()`
- Better error detection and logging
- Ownership setting for Unix systems
- Verifies directories are writable after creation

### 3. File Name Sanitization

Added a new method to sanitize filenames:
```php
protected function sanitizeFileName(string $fileName): string
{
  // Remove any path components
  $fileName = basename($fileName);

  // Replace any non-alphanumeric characters with underscore
  $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

  // Ensure we don't have multiple consecutive underscores
  $fileName = preg_replace('/_+/', '_', $fileName);

  // Remove leading/trailing underscores
  $fileName = trim($fileName, '_');

  return $fileName;
}
```

**Benefits:**
- Prevents filename conflicts
- Removes potentially dangerous characters
- Ensures cross-platform compatibility

### 4. Comprehensive Error Handling

Added extensive logging and error handling:
- Detailed error messages with context
- File verification after storage
- Stack traces for debugging
- Success/failure status tracking

## Installation Instructions

### 1. Update Your MassMailer Component

The fixes are already applied to `src/Livewire/MassMailer.php`. If you have an older version, replace it with the updated file.

### 2. Fix Storage Permissions

Run the diagnostic script to fix permissions:

```bash
# From your Laravel project root
php storage-permission-fix.php
```

This script will:
- Check all storage directories
- Create missing directories
- Fix permissions (0755)
- Set proper ownership on Unix systems
- Test file creation capabilities

### 3. Manual Permission Fix (if needed)

If the script doesn't resolve all issues, manually set permissions:

**Linux/Unix:**
```bash
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/
sudo chmod -R 775 storage/app/
sudo chmod -R 775 storage/app/public/
```

**Windows:**
- Right-click storage folder → Properties → Security
- Ensure IIS_IUSRS or your web server user has Full Control
- Or run your web server as Administrator

### 4. Verify Laravel Storage Configuration

Ensure your `config/filesystems.php` has the public disk properly configured:

```php
'disks' => [
  'local' => [
    'driver' => 'local',
    'root' => storage_path('app'),
  ],

  'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
  ],
],
```

### 5. Create Symbolic Link (if needed)

```bash
php artisan storage:link
```

## Testing the Fix

1. **Clear any cached configurations:**
   ```bash
   php artisan config:clear
   php artisan view:clear
   php artisan cache:clear
   ```

2. **Test file uploads:**
   - Go to your mass-mailer interface
   - Enable "Use Attachment Paths" mode
   - Upload a test file
   - Check the storage logs for success messages

3. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

   Look for entries like:
   ```
   [2025-11-25 05:45:00] INFO: Successfully saved attachment file to folder...
   ```

## Troubleshooting

### If uploads still fail:

1. **Check PHP settings:**
   ```php
   php -i | grep -E "(upload_max_filesize|post_max_size|max_execution_time)"
   ```

2. **Verify web server user:**
   ```bash
   ps aux | grep -E "(apache|nginx|httpd|php-fpm)"
   ```

3. **Test manual file creation:**
   ```bash
   touch storage/app/public/mass_mail/test.txt
   echo "test" > storage/app/public/mass_mail/test.txt
   ls -la storage/app/public/mass_mail/
   ```

4. **Check SELinux (Linux):**
   ```bash
   getenforce  # Should be "Permissive" or "Disabled"
   ```

### Common Error Solutions

**"Permission denied" errors:**
- Ensure web server user owns the storage directory
- Set permissions to 755 or 775
- Check SELinux/AppArmor policies

**"File not found" after upload:**
- Clear Laravel caches
- Check storage disk configuration
- Verify symbolic links are correct

**"Disk not found" errors:**
- Run `php artisan config:clear`
- Check `config/filesystems.php`
- Ensure 'public' disk is defined

## Prevention

To prevent future issues:

1. **Regular permission audits** - Run the diagnostic script monthly
2. **Server setup scripts** - Include permission setting in deployment
3. **Monitoring** - Set up alerts for file upload failures
4. **Documentation** - Keep this guide accessible to your team

## Support

If you continue experiencing issues:

1. Check the Laravel logs in `storage/logs/laravel.log`
2. Review the diagnostic script output
3. Ensure all PHP and web server requirements are met
4. Test with a minimal file upload scenario

The enhanced error logging will help identify the exact point of failure if issues persist.
