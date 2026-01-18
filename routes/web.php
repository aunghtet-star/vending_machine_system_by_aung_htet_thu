<?php
/**
 * Application Routes
 * 
 * Defines all web and API routes for the vending machine application.
 */

use App\Core\Router;

// Check if $container is available (passed from index.php)
$container = $container ?? null;
$router = new Router($container);

// ============================================
// Web Routes (with session-based auth)
// ============================================

$router->group(['middleware' => ['CsrfMiddleware']], function($router) {

    // Home
    $router->get('/', ['App\Controllers\HomeController', 'index'], 'home');

    // Authentication Routes
    $router->get('/login', ['App\Controllers\AuthController', 'showLoginForm'], 'login');
    $router->post('/login', ['App\Controllers\AuthController', 'login'], 'login.submit');
    $router->get('/register', ['App\Controllers\AuthController', 'showRegisterForm'], 'register');
    $router->post('/register', ['App\Controllers\AuthController', 'register'], 'register.submit');
    $router->post('/logout', ['App\Controllers\AuthController', 'logout'], 'logout');

    // Product Routes (Public)
    $router->get('/products', ['App\Controllers\ProductsController', 'index'], 'products.index');

    // Admin Routes (Product Management) - Must come before {id} routes
    $router->group(['middleware' => ['AdminMiddleware']], function($router) {
        // CRUD routes for products (Admin only)
        $router->get('/products/create', ['App\Controllers\ProductsController', 'create'], 'products.create');
        $router->post('/products', ['App\Controllers\ProductsController', 'store'], 'products.store');
        $router->get('/products/{id}/edit', ['App\Controllers\ProductsController', 'edit'], 'products.edit');
        $router->put('/products/{id}', ['App\Controllers\ProductsController', 'update'], 'products.update');
        $router->delete('/products/{id}', ['App\Controllers\ProductsController', 'destroy'], 'products.destroy');
        
        // Alternative SEO-friendly admin URLs
        $router->get('/admin/products', ['App\Controllers\ProductsController', 'index'], 'admin.products');
        $router->get('/admin/products/new', ['App\Controllers\ProductsController', 'create'], 'admin.products.new');
        $router->get('/admin/products/{id}/manage', ['App\Controllers\ProductsController', 'edit'], 'admin.products.manage');
    });

    // Product Routes (Public) - {id} route must come after specific routes
    $router->get('/products/{id}', ['App\Controllers\ProductsController', 'show'], 'products.show');

    // Product Routes (Authenticated Users)
    $router->group(['middleware' => ['AuthMiddleware']], function($router) {
        // Purchase routes with SEO-friendly URLs
        $router->get('/products/{id}/purchase', ['App\Controllers\ProductsController', 'purchaseForm'], 'products.purchase');
        $router->post('/products/{id}/purchase', ['App\Controllers\ProductsController', 'purchase'], 'products.purchase.submit');
        
        // Alternative SEO-friendly purchase URL
        $router->get('/buy/{id}', ['App\Controllers\ProductsController', 'purchaseForm'], 'buy.product');
        $router->post('/buy/{id}', ['App\Controllers\ProductsController', 'purchase'], 'buy.product.submit');
        
        // Transaction routes
        $router->get('/transactions', ['App\Controllers\TransactionsController', 'index'], 'transactions.index');
        $router->get('/transactions/{id}', ['App\Controllers\TransactionsController', 'show'], 'transactions.show');
        
        // Alternative SEO-friendly transaction URL
        $router->get('/my-orders', ['App\Controllers\TransactionsController', 'index'], 'my.orders');
        $router->get('/order/{id}', ['App\Controllers\TransactionsController', 'show'], 'order.show');
    });

});

// ============================================
// API Routes (with JWT authentication)
// ============================================

$router->group(['prefix' => '/api'], function($router) {
    
    // Public API Authentication Routes
    $router->post('/auth/login', ['App\Controllers\Api\AuthApiController', 'login'], 'api.auth.login');
    $router->post('/auth/register', ['App\Controllers\Api\AuthApiController', 'register'], 'api.auth.register');
    $router->post('/auth/refresh', ['App\Controllers\Api\AuthApiController', 'refresh'], 'api.auth.refresh');
    $router->post('/auth/logout', ['App\Controllers\Api\AuthApiController', 'logout'], 'api.auth.logout');
    
    // Public Product API Routes
    $router->get('/products', ['App\Controllers\Api\ProductsApiController', 'index'], 'api.products.index');
    $router->get('/products/{id}', ['App\Controllers\Api\ProductsApiController', 'show'], 'api.products.show');
    
    // Protected API Routes (requires JWT)
    $router->group(['middleware' => ['ApiAuthMiddleware']], function($router) {
        // User info
        $router->get('/auth/me', ['App\Controllers\Api\AuthApiController', 'me'], 'api.auth.me');
        
        // Purchase
        $router->post('/products/{id}/purchase', ['App\Controllers\Api\ProductsApiController', 'purchase'], 'api.products.purchase');
        
        // Transactions
        $router->get('/transactions', ['App\Controllers\Api\TransactionsApiController', 'index'], 'api.transactions.index');
        $router->get('/transactions/{id}', ['App\Controllers\Api\TransactionsApiController', 'show'], 'api.transactions.show');
        
        // Balance
        $router->get('/balance', ['App\Controllers\Api\TransactionsApiController', 'balance'], 'api.balance');
    });
    
    // Admin API Routes (requires JWT + Admin role)
    $router->group(['middleware' => ['ApiAdminMiddleware']], function($router) {
        $router->post('/products', ['App\Controllers\Api\ProductsApiController', 'store'], 'api.products.store');
        $router->put('/products/{id}', ['App\Controllers\Api\ProductsApiController', 'update'], 'api.products.update');
        $router->delete('/products/{id}', ['App\Controllers\Api\ProductsApiController', 'destroy'], 'api.products.destroy');
    });
});

return $router;
