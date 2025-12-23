-- Plaza Management System Database Schema
-- Simple and clear column names

CREATE DATABASE IF NOT EXISTS plaza_ms;
USE plaza_ms;

-- Users table (Admin and Tenant)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    user_type ENUM('admin', 'tenant') NOT NULL DEFAULT 'tenant',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Shops table
CREATE TABLE shops (
    shop_id INT PRIMARY KEY AUTO_INCREMENT,
    shop_number VARCHAR(20) UNIQUE NOT NULL,
    shop_name VARCHAR(100),
    floor_number INT,
    area_sqft DECIMAL(10,2),
    monthly_rent DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    description TEXT,
    tenant_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Rooms table
CREATE TABLE rooms (
    room_id INT PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(20) UNIQUE NOT NULL,
    room_name VARCHAR(100),
    floor_number INT,
    area_sqft DECIMAL(10,2),
    monthly_rent DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    description TEXT,
    tenant_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Basements table
CREATE TABLE basements (
    basement_id INT PRIMARY KEY AUTO_INCREMENT,
    basement_number VARCHAR(20) UNIQUE NOT NULL,
    basement_name VARCHAR(100),
    area_sqft DECIMAL(10,2),
    monthly_rent DECIMAL(10,2) NOT NULL,
    space_type ENUM('parking', 'storage', 'other') DEFAULT 'parking',
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    description TEXT,
    tenant_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Agreements table (Lease Agreements)
CREATE TABLE agreements (
    agreement_id INT PRIMARY KEY AUTO_INCREMENT,
    agreement_number VARCHAR(50) UNIQUE NOT NULL,
    tenant_id INT NOT NULL,
    space_type ENUM('shop', 'room', 'basement') NOT NULL,
    space_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    monthly_rent DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2) DEFAULT 0,
    terms TEXT,
    status ENUM('active', 'expired', 'terminated', 'renewed') DEFAULT 'active',
    document_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Ledger table (Financial transactions)
CREATE TABLE ledger (
    ledger_id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    agreement_id INT,
    transaction_type ENUM('rent', 'maintenance', 'service_charge', 'deposit', 'refund', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE,
    payment_method ENUM('cash', 'bank_transfer', 'online', 'check') DEFAULT 'cash',
    description TEXT,
    status ENUM('paid', 'pending', 'overdue') DEFAULT 'pending',
    invoice_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (agreement_id) REFERENCES agreements(agreement_id) ON DELETE SET NULL
);

-- Payments table
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    agreement_id INT,
    ledger_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'online', 'check') NOT NULL,
    transaction_id VARCHAR(100),
    receipt_file VARCHAR(255),
    status ENUM('completed', 'pending', 'failed') DEFAULT 'completed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (agreement_id) REFERENCES agreements(agreement_id) ON DELETE SET NULL,
    FOREIGN KEY (ledger_id) REFERENCES ledger(ledger_id) ON DELETE SET NULL
);

-- Maintenance requests table
CREATE TABLE maintenance_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    space_type ENUM('shop', 'room', 'basement') NOT NULL,
    space_id INT NOT NULL,
    issue_type VARCHAR(50),
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    assigned_to VARCHAR(100),
    cost DECIMAL(10,2) DEFAULT 0,
    completed_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('payment', 'lease', 'maintenance', 'general') DEFAULT 'general',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Note: Admin user should be created using setup.php script
-- Or manually insert with: password_hash('admin123', PASSWORD_DEFAULT)

