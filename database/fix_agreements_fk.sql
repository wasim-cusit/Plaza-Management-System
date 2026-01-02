-- Quick Fix for Agreements Foreign Key
-- Run this in phpMyAdmin or MySQL command line

USE plaza_ms;

-- Step 1: Drop the old foreign key constraint
SET @constraint_name = (
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'plaza_ms' 
    AND TABLE_NAME = 'agreements' 
    AND COLUMN_NAME = 'tenant_id' 
    AND REFERENCED_TABLE_NAME = 'users'
    LIMIT 1
);

SET @sql = CONCAT('ALTER TABLE agreements DROP FOREIGN KEY ', @constraint_name);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Add customer_id column if it doesn't exist
ALTER TABLE agreements ADD COLUMN customer_id INT AFTER tenant_id;

-- Step 3: Copy data from tenant_id to customer_id (if you have existing data)
-- This assumes you've already migrated users to customers
-- UPDATE agreements SET customer_id = tenant_id WHERE tenant_id IS NOT NULL;

-- Step 4: Add new foreign key constraint for customer_id
ALTER TABLE agreements ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE;

-- Step 5: (Optional) Drop tenant_id column after verifying everything works
-- ALTER TABLE agreements DROP COLUMN tenant_id;

