<?php
/**
 * Vending Machine Application Entry Point
 * 
 * This is the main entry point for the PHP vending machine application.
 * All requests are routed through this file.
 */

use App\Core\Container;
use App\Core\Request;
use App\Services\TokenServiceInterface;
use App\Services\JWTService;
use App\Core\Database;

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path (project root, not public directory)
define('BASE_PATH', dirname(__DIR__));

// Load Composer autoloader if available, otherwise use custom autoloader
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
    
    // Load environment variables
    if (file_exists(BASE_PATH . '/.env')) {
        $dotenv = \Dotenv\Dotenv::createImmutable(BASE_PATH);
        $dotenv->load();
    }
} else {
    // Custom autoloader for when Composer is not available
    spl_autoload_register(function ($class) {
        // Convert namespace to file path
        $prefix = 'App\\';
        $baseDir = BASE_PATH . '/src/';
        
        // Check if the class uses the namespace prefix
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        // Get the relative class name
        $relativeClass = substr($class, $len);
        
        // Replace namespace separators with directory separators
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        // Require the file if it exists
        if (file_exists($file)) {
            require $file;
        }
    });
}

// Start session
\App\Core\Session::start();

// Initialize Service Container
$container = new Container();

// Register Services
$container->bind(TokenServiceInterface::class, JWTService::class);
$container->bind(Database::class, function() {
    return Database::getInstance();
});

// Initialize Request and bind to Container
$request = new Request();
$container->singleton(Request::class, $request);

// Load application config
$config = require BASE_PATH . '/config/app.php';

// Set timezone
date_default_timezone_set($config['timezone']);

// Load routes
// $container is available in the scope of the included file
$router = require BASE_PATH . '/routes/web.php';

// Get request method and URI
$method = $request->method();
$uri = $request->uri();

// Dispatch the request
try {
    $result = $router->dispatch($method, $uri);
    
    // If result is an array (JSON response from middleware), output it
    if (is_array($result)) {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
} catch (\Exception $e) {
    // Handle exceptions
    if ($config['debug']) {
        http_response_code(500);
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>Internal Server Error</h1>';
    }
}
