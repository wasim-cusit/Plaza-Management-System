-- Add Customers table for plaza clients (not system users)
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    alternate_phone VARCHAR(20),
    cnic VARCHAR(20) UNIQUE,
    address TEXT,
    city VARCHAR(50),
    country VARCHAR(50) DEFAULT 'Pakistan',
    occupation VARCHAR(100),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    reference_name VARCHAR(100),
    reference_phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Update existing tables to use customer_id instead of tenant_id
-- Note: This is a migration script - run carefully

-- Add customer_id columns if they don't exist
ALTER TABLE shops ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE basements ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE agreements ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE ledger ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE maintenance_requests ADD COLUMN IF NOT EXISTS customer_id INT;

-- Add foreign keys
ALTER TABLE shops ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE rooms ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE basements ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE agreements ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE ledger ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE payments ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE maintenance_requests ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;

