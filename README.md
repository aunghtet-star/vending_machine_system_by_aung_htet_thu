# Vending Machine System

A robust PHP application for a vending machine system with product management, inventory tracking, purchase transactions, user authentication, and a RESTful API.

## Features

- **Product Management**: Full CRUD operations for products
- **User Authentication**: Session-based auth for web, JWT for API
- **Role-Based Access Control**: Admin and User roles
- **Purchase System**: Buy products with balance management
- **Transaction Logging**: Complete purchase history
- **RESTful API**: Complete API with JWT authentication
- **Pagination & Sorting**: For product listings
- **Form Validation**: Client-side and server-side validation

## Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer (for dependency management)
- Apache with mod_rewrite or Nginx

🏗️ System Architecture
This project follows a decoupled, Service-Oriented Architecture (SOA) within a custom MVC framework.

Dependency Inversion (SOLID): All core services (Authentication, Database, etc.) are injected via Interfaces. This allows the application to remain agnostic of specific implementations.

IoC Container: A centralized Service Container manages the instantiation and dependency resolution of all Controllers and Middleware, serving as the "Single Point of Change" for the entire application.

Middleware Pipeline: Security is handled through a non-blocking Middleware layer. This ensures that business logic in the Controllers is only executed for authenticated and authorized requests.




## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd vendingmachine
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Database

Copy the configuration file and update with your database credentials:

```php
// config/database.php
return [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'vending_machine',
    'username' => 'root',
    'password' => 'your_password',
    // ...
];
```

Or use environment variables:
```bash
export DB_HOST=localhost
export DB_NAME=vending_machine
export DB_USER=root
export DB_PASS=your_password
```

### 4. Create Database

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

### 5. Configure JWT Secret (for API)

Update the JWT secret in `config/app.php`:

```php
'jwt' => [
    'secret' => 'your-super-secret-key-change-this',
    // ...
],
```

### 6. Start the Server

For development:
```bash
composer serve
# or
php -S localhost:8000 -t public
```

For production, configure your Apache/Nginx to point to the `public` directory.

## Project Structure

```
vendingmachine/
├── config/
│   ├── app.php           # Application configuration
│   └── database.php      # Database configuration
├── database/
│   ├── schema.sql        # Database schema
│   └── seed.sql          # Seed data
├── public/
│   └── index.php         # Application entry point
├── routes/
│   └── web.php           # Route definitions
├── src/
│   ├── Controllers/      # Controller classes
│   │   ├── Api/          # API controllers
│   │   ├── AuthController.php
│   │   ├── HomeController.php
│   │   ├── ProductsController.php
│   │   └── TransactionsController.php
│   ├── Core/             # Core framework classes
│   │   ├── Controller.php
│   │   ├── Database.php
│   │   ├── Router.php
│   │   └── Session.php
│   ├── Middleware/       # Middleware classes
│   │   ├── AdminMiddleware.php
│   │   ├── ApiAdminMiddleware.php
│   │   ├── ApiAuthMiddleware.php
│   │   └── AuthMiddleware.php
│   ├── Models/           # Data models
│   │   ├── Product.php
│   │   ├── Transaction.php
│   │   └── User.php
│   └── Services/         # Service classes
│       ├── AuthService.php
│       └── JWTService.php
├── tests/                # PHPUnit tests
│   ├── Unit/
│   └── TestCase.php
├── views/                # View templates
│   ├── auth/
│   ├── home/
│   ├── layouts/
│   ├── products/
│   └── transactions/
├── .htaccess
├── composer.json
└── phpunit.xml
```

## Database Schema

### Users Table
- `id` - Primary key
- `username` - Unique username
- `email` - Unique email
- `password` - Hashed password
- `role` - 'admin' or 'user'
- `balance` - User's account balance
- `created_at`, `updated_at`, `last_login`
- `is_active` - Account status

### Products Table
- `id` - Primary key
- `name` - Product name
- `description` - Product description
- `price` - Decimal price (positive)
- `quantity_available` - Stock count (non-negative)
- `image_url` - Optional image URL
- `created_at`, `updated_at`
- `is_active` - Product availability

### Transactions Table
- `id` - Primary key
- `user_id` - Foreign key to users
- `product_id` - Foreign key to products
- `quantity` - Purchase quantity
- `unit_price` - Price at time of purchase
- `total_amount` - Total transaction amount
- `transaction_date` - Timestamp
- `status` - 'pending', 'completed', 'cancelled', 'refunded'
- `payment_method` - Payment method used

## Default Credentials

After running seed.sql:

| Username | Password | Role |
|----------|----------|------|
| admin    | password | Admin |
| user1    | password | User |
| user2    | password | User |

## Web Routes

### Public Routes
- `GET /` - Home page
- `GET /products` - Product listing
- `GET /products/{id}` - Product details
- `GET /login` - Login form
- `POST /login` - Process login
- `GET /register` - Registration form
- `POST /register` - Process registration

### Authenticated Routes
- `POST /logout` - Logout
- `GET /products/{id}/purchase` - Purchase form
- `POST /products/{id}/purchase` - Process purchase
- `GET /transactions` - Transaction history
- `GET /transactions/{id}` - Transaction details

### Admin Routes
- `GET /products/create` - Create product form
- `POST /products` - Store new product
- `GET /products/{id}/edit` - Edit product form
- `PUT /products/{id}` - Update product
- `DELETE /products/{id}` - Delete product

## API Endpoints

### Authentication
```
POST /api/auth/login        # Login and get tokens
POST /api/auth/register     # Register new user
POST /api/auth/refresh      # Refresh access token
POST /api/auth/logout       # Revoke tokens
GET  /api/auth/me           # Get current user (requires auth)
```

### Products
```
GET    /api/products            # List all products
GET    /api/products/{id}       # Get single product
POST   /api/products            # Create product (admin)
PUT    /api/products/{id}       # Update product (admin)
DELETE /api/products/{id}       # Delete product (admin)
POST   /api/products/{id}/purchase  # Purchase product (requires auth)
```

### Transactions
```
GET /api/transactions       # List transactions (requires auth)
GET /api/transactions/{id}  # Get transaction details (requires auth)
GET /api/balance            # Get user balance (requires auth)
```

## API Usage Examples

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username": "user1", "password": "password"}'
```

Response:
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ...",
    "refresh_token": "abc123...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 2,
      "username": "user1",
      "email": "user1@example.com",
      "role": "user",
      "balance": 50.00
    }
  }
}
```

### Get Products
```bash
curl http://localhost:8000/api/products
```

### Purchase Product
```bash
curl -X POST http://localhost:8000/api/products/1/purchase \
  -H "Authorization: Bearer <access_token>" \
  -H "Content-Type: application/json" \
  -d '{"quantity": 2}'
```

## Running Tests

```bash
# Run all tests
composer test

# Run with coverage report
composer test:coverage

# Run specific test file
./vendor/bin/phpunit tests/Unit/Controllers/ProductsControllerTest.php
```

## Validation Rules

### Product Validation
- **Name**: Required, max 100 characters
- **Price**: Required, must be positive number
- **Quantity**: Required, must be non-negative integer

### User Registration Validation
- **Username**: Required, 3-50 characters, alphanumeric + underscore
- **Email**: Required, valid email format
- **Password**: Required, minimum 8 characters

## Security Features

- Password hashing using `password_hash()` with bcrypt
- Session regeneration to prevent session fixation
- CSRF protection for forms (use `_method` for PUT/DELETE)
- SQL injection prevention with PDO prepared statements
- XSS protection with `htmlspecialchars()`
- JWT tokens with HMAC-SHA256 signing
- Role-based access control


