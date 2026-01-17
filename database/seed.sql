-- ============================================
-- Vending Machine Database Seed Data
-- ============================================

USE vending_machine;

-- ============================================
-- Insert Default Admin User
-- Password: admin123 (hashed with PHP password_hash)
-- ============================================
INSERT INTO users (username, email, password, role, balance) VALUES
('admin', 'admin@vendingmachine.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1000.00),
('user1', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 50.00),
('user2', 'user2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 25.00);

-- ============================================
-- Insert Default Products
-- ============================================
INSERT INTO products (name, description, price, quantity_available, image_url) VALUES
('Coke', 'Classic Coca-Cola soft drink', 3.99, 50, '/images/products/coke.png'),
('Pepsi', 'Pepsi Cola refreshing beverage', 6.885, 30, '/images/products/pepsi.png'),
('Water', 'Pure mineral water', 0.50, 100, '/images/products/water.png'),
('Sprite', 'Lemon-lime flavored soda', 3.50, 40, '/images/products/sprite.png'),
('Fanta', 'Orange flavored soft drink', 3.75, 35, '/images/products/fanta.png'),
('Red Bull', 'Energy drink', 4.99, 25, '/images/products/redbull.png'),
('Chips', 'Crispy potato chips', 2.50, 60, '/images/products/chips.png'),
('Chocolate Bar', 'Milk chocolate bar', 2.25, 45, '/images/products/chocolate.png'),
('Candy', 'Assorted candies pack', 1.50, 80, '/images/products/candy.png'),
('Coffee', 'Hot brewed coffee', 3.00, 40, '/images/products/coffee.png');

-- ============================================
-- Insert Sample Transactions
-- ============================================
INSERT INTO transactions (user_id, product_id, quantity, unit_price, total_amount, status) VALUES
(2, 1, 2, 3.99, 7.98, 'completed'),
(2, 3, 1, 0.50, 0.50, 'completed'),
(3, 2, 1, 6.885, 6.885, 'completed'),
(3, 1, 1, 3.99, 3.99, 'completed');
