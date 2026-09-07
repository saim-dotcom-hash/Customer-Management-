-- Customer Management & Balance Management System Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.2+

DROP DATABASE IF EXISTS customer_management;
CREATE DATABASE customer_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE customer_management;

-- 1. Admins Table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Customers Table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mobile VARCHAR(15) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mobile (mobile),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Powers Table (Optical Power Options)
CREATE TABLE powers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Lens Types Table
CREATE TABLE lens_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Frame Types Table
CREATE TABLE frame_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Customer Entries Table
CREATE TABLE customer_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    power_id INT NULL,
    lens_type_id INT NULL,
    frame_type_id INT NULL,
    entry_date DATE NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    advanced DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('Paid', 'Pending') NOT NULL DEFAULT 'Pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (power_id) REFERENCES powers(id) ON DELETE SET NULL,
    FOREIGN KEY (lens_type_id) REFERENCES lens_types(id) ON DELETE SET NULL,
    FOREIGN KEY (frame_type_id) REFERENCES frame_types(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_entry_date (entry_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Payments Table (Payment History Log)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_entry_id INT NOT NULL,
    customer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    notes VARCHAR(255) NULL,
    created_by VARCHAR(50) DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_entry_id) REFERENCES customer_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_entry_id (customer_entry_id),
    INDEX idx_pay_cust_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- SEED DATA
-- ========================================================

-- Insert Default Admin (Username: admin, Email: admin@example.com, Password: admin123)
-- Hash generated using password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO admins (username, email, password) VALUES 
('admin', 'admin@example.com', '$2y$10$JOQjVtPc8EQHpRjZKyd3nOy3GsZhNAyfK834awADHqiro/vHqQwdu');

-- Seed Powers
INSERT INTO powers (name, status) VALUES
('+1.00', 'active'),
('+1.50', 'active'),
('+2.00', 'active'),
('+2.50', 'active'),
('+3.00', 'active'),
('-1.00', 'active'),
('-1.50', 'active'),
('-2.00', 'active'),
('-2.50', 'active'),
('-3.00', 'active');

-- Seed Lens Types
INSERT INTO lens_types (name, status) VALUES
('Single Vision', 'active'),
('Progressive', 'active'),
('Bifocal', 'active'),
('Blue Cut', 'active'),
('Photochromic', 'active'),
('Anti-Glare', 'active'),
('UV Protection', 'active'),
('High Index', 'active');

-- Seed Frame Types
INSERT INTO frame_types (name, status) VALUES
('Metal', 'active'),
('Plastic', 'active'),
('Rimless', 'active'),
('Semi Rimless', 'active'),
('Acetate', 'active'),
('Full Frame', 'active'),
('Half Frame', 'active');

-- Seed Sample Customers
INSERT INTO customers (id, name, mobile) VALUES
(1, 'Rahul Sharma', '9876543210'),
(2, 'Priya Patel', '9812345678'),
(3, 'Amit Verma', '9988776655');

-- Seed Sample Customer Entries
INSERT INTO customer_entries (id, customer_id, power_id, lens_type_id, frame_type_id, entry_date, price, advanced, balance, status, notes) VALUES
(1, 1, 2, 1, 1, CURDATE(), 3000.00, 1000.00, 2000.00, 'Pending', 'First eyeglasses order'),
(2, 1, 4, 2, 2, CURDATE(), 5000.00, 2000.00, 3000.00, 'Pending', 'Progressive blue-cut glasses'),
(3, 2, 1, 4, 3, CURDATE(), 4500.00, 4500.00, 0.00, 'Paid', 'Full payment done on order'),
(4, 3, 7, 3, 5, CURDATE(), 6000.00, 2500.00, 3500.00, 'Pending', 'Bifocal photochromic frame');

-- Seed Sample Payments
INSERT INTO payments (customer_entry_id, customer_id, amount, payment_date, notes, created_by) VALUES
(1, 1, 1000.00, CURDATE(), 'Initial Advance', 'Admin'),
(2, 1, 2000.00, CURDATE(), 'Initial Advance', 'Admin'),
(3, 2, 4500.00, CURDATE(), 'Full Advance Payment', 'Admin'),
(4, 3, 2500.00, CURDATE(), 'Initial Advance', 'Admin');
