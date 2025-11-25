<?php
/**
 * Mass Mailer Package Validation Script
 * Run this to ensure the package is ready for production
 */

echo "🔍 Validating Mass Mailer Package...\n\n";

// Check 1: Composer.json validation
echo "1. Checking composer.json...\n";
if (file_exists('composer.json')) {
    $composer = json_decode(file_get_contents('composer.json'), true);
    if ($composer) {
        echo "   ✅ Valid JSON\n";
        echo "   ✅ Name: " . ($composer['name'] ?? 'Missing') . "\n";
        echo "   ✅ Description: " . ($composer['description'] ?? 'Missing') . "\n";

        // Check Laravel version support
        $laravelVersions = $composer['require']['laravel/framework'] ?? '';
        if (strpos($laravelVersions, '^10.0|^11.0|^12.0') !== false) {
            echo "   ✅ Laravel support: 10, 11, 12\n";
        } else {
            echo "   ❌ Laravel version support issue\n";
        }
    } else {
        echo "   ❌ Invalid JSON\n";
    }
} else {
    echo "   ❌ composer.json not found\n";
}

echo "\n";

// Check 2: Package structure
echo "2. Checking package structure...\n";
$requiredFiles = [
    'src/Providers/MassMailerServiceProvider.php',
    'src/Livewire/MassMailer.php',
    'src/Config/mass-mailer.php',
    'src/Views/bootstrap/mass-mailer.blade.php',
    'src/Views/tailwind/mass-mailer.blade.php',
    'src/Jobs/SendMassMailJob.php',
    'src/Mail/MassMailerMail.php',
    'README.md'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ Missing: $file\n";
    }
}

echo "\n";

// Check 3: PHP syntax validation
echo "3. Checking PHP syntax...\n";
$phpFiles = glob('src/**/*.php');
foreach ($phpFiles as $file) {
    $output = shell_exec("php -l \"$file\" 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ Syntax error in $file: $output\n";
    }
}

echo "\n";

// Check 4: Namespace consistency
echo "4. Checking namespace consistency...\n";
$files = glob('src/**/*.php');
$namespaceIssues = 0;
// Files that should NOT have namespaces (Laravel convention)
$noNamespaceFiles = ['Config/mass-mailer.php', 'Migrations/'];
foreach ($files as $file) {
    $relativePath = str_replace('src/', '', $file);
    $shouldHaveNamespace = true;

    foreach ($noNamespaceFiles as $noNsFile) {
        if (strpos($relativePath, $noNsFile) === 0) {
            $shouldHaveNamespace = false;
            break;
        }
    }

    $content = file_get_contents($file);
    $hasNamespace = strpos($content, 'namespace Mrclln\\MassMailer') !== false;

    if ($shouldHaveNamespace && !$hasNamespace) {
        echo "   ❌ Missing namespace in $file\n";
        $namespaceIssues++;
    } elseif (!$shouldHaveNamespace && $hasNamespace) {
        echo "   ❌ Should not have namespace in $file\n";
        $namespaceIssues++;
    }
}
if ($namespaceIssues === 0) {
    echo "   ✅ All files have correct namespace structure\n";
}

echo "\n";

// Check 5: Configuration validation
echo "5. Checking configuration...\n";
$configFile = 'src/Config/mass-mailer.php';
if (file_exists($configFile)) {
    // Create mock env function for validation
    if (!function_exists('env')) {
        function env($key, $default = null) {
            return $default;
        }
    }

    // Create mock config function for validation
    if (!function_exists('config')) {
        function config($key, $default = null) {
            return $default;
        }
    }

    $config = include $configFile;
    if (is_array($config)) {
        echo "   ✅ Valid configuration array\n";
        $requiredKeys = ['enabled', 'ui', 'queue', 'batch_size'];
        foreach ($requiredKeys as $key) {
            if (array_key_exists($key, $config)) {
                echo "   ✅ Config key: $key\n";
            } else {
                echo "   ❌ Missing config key: $key\n";
            }
        }
    } else {
        echo "   ❌ Invalid configuration format\n";
    }
} else {
    echo "   ❌ Configuration file not found\n";
}

echo "\n";

// Final summary
echo "🎯 Package Validation Complete!\n";
echo "If all checks show ✅, your package is ready for production.\n";
echo "If you see any ❌, please fix those issues before publishing.\n";
