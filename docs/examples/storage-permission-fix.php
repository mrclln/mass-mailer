<?php

/**
 * Storage Permission Fix Script
 *
 * This script diagnoses and fixes common storage permission issues
 * that cause file upload failures in Laravel applications.
 *
 * Run this from your Laravel project root:
 * php storage-permission-fix.php
 */

echo "🔍 Storage Permission Diagnostic Tool\n";
echo "=====================================\n\n";

// Check if we're in a Laravel project
if (!file_exists('artisan') && !file_exists('composer.json')) {
    echo "❌ Error: This script must be run from your Laravel project root directory.\n";
    exit(1);
}

echo "✅ Laravel project detected\n\n";

// Define storage paths
$paths = [
    'storage/app' => storage_path('app'),
    'storage/app/public' => storage_path('app/public'),
    'storage/app/public/mass_mail' => storage_path('app/public/mass_mail'),
    'storage/framework' => storage_path('framework'),
    'storage/framework/cache' => storage_path('framework/cache'),
    'storage/framework/sessions' => storage_path('framework/sessions'),
    'storage/framework/views' => storage_path('framework/views'),
    'storage/logs' => storage_path('logs'),
];

echo "📁 Checking Storage Paths:\n";
echo "--------------------------\n";

$issuesFound = false;

foreach ($paths as $name => $path) {
    echo "Checking: $name\n";

    if (!is_dir($path)) {
        echo "  ❌ Directory does not exist: $path\n";

        // Try to create it
        $created = mkdir($path, 0755, true);
        if ($created) {
            echo "  ✅ Created directory successfully\n";

            // Set ownership and permissions for Unix systems
            if (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin') {
                $wwwDataUser = posix_getpwnam('www-data');
                if ($wwwDataUser) {
                    chown($path, $wwwDataUser['name']);
                    echo "  🔧 Set ownership to www-data\n";
                }

                $currentUser = posix_getpwuid(posix_geteuid());
                if ($currentUser) {
                    chown($path, $currentUser['name']);
                    echo "  🔧 Set ownership to current user: {$currentUser['name']}\n";
                }
            }

            chmod($path, 0755);
            echo "  🔧 Set permissions to 0755\n";
        } else {
            echo "  ❌ Failed to create directory\n";
            $issuesFound = true;
        }
    } else {
        echo "  ✅ Directory exists: $path\n";

        // Check if writable
        if (!is_writable($path)) {
            echo "  ⚠️  Directory is not writable\n";

            // Try to fix permissions
            if (chmod($path, 0755)) {
                echo "  ✅ Fixed permissions to 0755\n";
            } else {
                echo "  ❌ Failed to fix permissions\n";
                $issuesFound = true;
            }

            // Try to set ownership on Unix systems
            if (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin') {
                $wwwDataUser = posix_getpwnam('www-data');
                if ($wwwDataUser && chown($path, $wwwDataUser['name'])) {
                    echo "  ✅ Set ownership to www-data\n";
                }

                $currentUser = posix_getpwuid(posix_geteuid());
                if ($currentUser && chown($path, $currentUser['name'])) {
                    echo "  ✅ Set ownership to current user: {$currentUser['name']}\n";
                }
            }
        } else {
            echo "  ✅ Directory is writable\n";
        }

        // Show current permissions
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo "  📄 Current permissions: $perms\n";
    }

    echo "\n";
}

// Test file creation
echo "🧪 Testing File Creation:\n";
echo "-------------------------\n";

$testFile = storage_path('app/public/mass_mail/test_write_' . uniqid() . '.tmp');
$testContent = 'Test content: ' . date('Y-m-d H:i:s');

if (file_put_contents($testFile, $testContent) !== false) {
    echo "✅ File creation test successful\n";
    echo "📄 Created: " . basename($testFile) . "\n";

    // Clean up test file
    unlink($testFile);
    echo "🗑️  Cleaned up test file\n";
} else {
    echo "❌ File creation test failed\n";
    echo "This indicates a permissions issue with the storage directory\n";
    $issuesFound = true;
}

echo "\n";

// Windows-specific fixes
if (PHP_OS_FAMILY === 'Windows') {
    echo "🪟 Windows System Detected\n";
    echo "--------------------------\n";
    echo "For Windows, ensure that:\n";
    echo "1. Your web server (Apache/Nginx) has write access to storage directories\n";
    echo "2. Run your command prompt as Administrator to create directories if needed\n";
    echo "3. Check that your antivirus isn't blocking file access\n";
    echo "\n";
}

// Final recommendations
echo "💡 Final Recommendations:\n";
echo "-------------------------\n";

if (!$issuesFound) {
    echo "✅ All storage paths are properly configured!\n";
    echo "\nIf you're still experiencing file upload issues:\n";
    echo "1. Check your Laravel logs: storage/logs/laravel.log\n";
    echo "2. Ensure your web server user has proper ownership\n";
    echo "3. Verify Livewire configuration in your Livewire component\n";
    echo "4. Check PHP upload settings in php.ini (upload_max_filesize, post_max_size)\n";
} else {
    echo "⚠️  Some issues were found and may need manual intervention.\n";
    echo "\nTry these solutions:\n";
    echo "1. Run this script with Administrator/sudo privileges\n";
    echo "2. Manually create missing directories with proper permissions\n";
    echo "3. Check your web server user ownership (www-data, apache, nginx)\n";
    echo "4. Disable antivirus temporarily to test if it's interfering\n";
}

echo "\n";
echo "🔧 For mass-mailer specific issues:\n";
echo "1. Ensure storage/app/public/mass_mail is writable\n";
echo "2. Check that your Laravel disk configuration includes 'public'\n";
echo "3. Run 'php artisan storage:link' if symbolic links are needed\n";
echo "\n";
echo "Script completed. Check the output above for any issues.\n";
