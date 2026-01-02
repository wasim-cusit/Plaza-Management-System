-- Add Apartments and Parking tables to Plaza Management System

USE plaza_ms;

-- Apartments table
CREATE TABLE IF NOT EXISTS apartments (
    apartment_id INT PRIMARY KEY AUTO_INCREMENT,
    apartment_number VARCHAR(20) UNIQUE NOT NULL,
    apartment_name VARCHAR(100),
    floor_number INT,
    area_sqft DECIMAL(10,2),
    monthly_rent DECIMAL(10,2) NOT NULL,
    bedrooms INT DEFAULT 1,
    bathrooms INT DEFAULT 1,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    description TEXT,
    customer_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL
);

-- Parking table
CREATE TABLE IF NOT EXISTS parking (
    parking_id INT PRIMARY KEY AUTO_INCREMENT,
    parking_number VARCHAR(20) UNIQUE NOT NULL,
    parking_name VARCHAR(100),
    parking_type ENUM('covered', 'open', 'reserved') DEFAULT 'open',
    area_sqft DECIMAL(10,2),
    monthly_rent DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    description TEXT,
    customer_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL
);

