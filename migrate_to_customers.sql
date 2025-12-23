-- Migration Script: Convert from users/tenant_id to customers/customer_id
-- Run this script if you have an existing database with tenant_id structure
-- BACKUP YOUR DATABASE BEFORE RUNNING THIS!

USE plaza_ms;

-- Step 1: Create customers table
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL DEFAULT 'male',
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

-- Step 2: Migrate existing tenant users to customers table
INSERT INTO customers (full_name, gender, email, phone, address, status, created_at)
SELECT 
    full_name,
    'male' as gender,  -- Default gender, update manually if needed
    email,
    COALESCE(phone, 'N/A') as phone,
    address,
    status,
    created_at
FROM users
WHERE user_type = 'tenant';

-- Step 3: Add customer_id columns to existing tables
ALTER TABLE shops ADD COLUMN customer_id INT;
ALTER TABLE rooms ADD COLUMN customer_id INT;
ALTER TABLE basements ADD COLUMN customer_id INT;
ALTER TABLE agreements ADD COLUMN customer_id INT;
ALTER TABLE ledger ADD COLUMN customer_id INT;
ALTER TABLE payments ADD COLUMN customer_id INT;
ALTER TABLE maintenance_requests ADD COLUMN customer_id INT;

-- Step 4: Migrate data from tenant_id to customer_id
-- This maps old tenant_id (user_id) to new customer_id
UPDATE shops s
JOIN users u ON s.tenant_id = u.user_id
JOIN customers c ON u.full_name = c.full_name AND u.email = c.email
SET s.customer_id = c.customer_id
WHERE s.tenant_id IS NOT NULL;

UPDATE rooms r
JOIN users u ON r.tenant_id = u.user_id
JOIN customers c ON u.full_name = c.full_name AND u.email = c.email
SET r.customer_id = c.customer_id
WHERE r.tenant_id IS NOT NULL;

UPDATE basements b
JOIN users u ON b.tenant_id = u.user_id
JOIN customers c ON u.full_name = c.full_name AND u.email = c.email
SET b.customer_id = c.customer_id
WHERE b.tenant_id IS NOT NULL;

UPDATE agreements a
JOIN users u ON a.tenant_id = u.user_id
JOIN customers c ON u.full_name = c.full_name AND u.email = c.email
SET a.customer_id = c.customer_id
WHERE a.tenant_id IS NOT NULL;

UPDATE ledger l
JOIN users u ON l.tenant_id = u.user_id
JOIN customers c ON u.full_name = c.full_name AND u.email = c.email
SET l.customer_id = c.customer_id
WHERE l.tenant_id IS NOT NULL;

UPDATE payments p
JOIN users u ON p.tenant_id = u.user_id
JOIN customers c ON u.full_name = c.full_name AND u.email = c.email
SET p.customer_id = c.customer_id
WHERE p.tenant_id IS NOT NULL;

UPDATE maintenance_requests m
JOIN users u ON m.tenant_id = u.user_id
JOIN customers c ON u.full_name = c.full_name AND u.email = c.email
SET m.customer_id = c.customer_id
WHERE m.tenant_id IS NOT NULL;

-- Step 5: Add foreign key constraints
ALTER TABLE shops ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE rooms ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE basements ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE agreements ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE;
ALTER TABLE ledger ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE;
ALTER TABLE payments ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE;
ALTER TABLE maintenance_requests ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE;

-- Note: Keep tenant_id columns for now in case of rollback
-- You can drop them later after verifying everything works:
-- ALTER TABLE shops DROP COLUMN tenant_id;
-- ALTER TABLE rooms DROP COLUMN tenant_id;
-- ALTER TABLE basements DROP COLUMN tenant_id;
-- ALTER TABLE agreements DROP COLUMN tenant_id;
-- ALTER TABLE ledger DROP COLUMN tenant_id;
-- ALTER TABLE payments DROP COLUMN tenant_id;
-- ALTER TABLE maintenance_requests DROP COLUMN tenant_id;

