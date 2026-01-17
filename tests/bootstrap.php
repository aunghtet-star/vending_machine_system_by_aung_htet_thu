<?php
/**
 * PHPUnit Bootstrap File
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Check for Test namespace
        $testPrefix = 'Tests\\';
        $testBaseDir = BASE_PATH . '/tests/';
        
        $testLen = strlen($testPrefix);
        if (strncmp($testPrefix, $class, $testLen) === 0) {
            $relativeClass = substr($class, $testLen);
            $file = $testBaseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require $file;
            }
        }
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Mock session for testing
if (!function_exists('session_start_mock')) {
    $_SESSION = [];
}
