-- Fix Foreign Key Constraints
-- Run this script to update your database from tenant_id to customer_id

USE plaza_ms;

-- Drop old foreign key constraints
ALTER TABLE agreements DROP FOREIGN KEY IF EXISTS agreements_ibfk_1;
ALTER TABLE shops DROP FOREIGN KEY IF EXISTS shops_ibfk_1;
ALTER TABLE rooms DROP FOREIGN KEY IF EXISTS rooms_ibfk_1;
ALTER TABLE basements DROP FOREIGN KEY IF EXISTS basements_ibfk_1;
ALTER TABLE ledger DROP FOREIGN KEY IF EXISTS ledger_ibfk_1;
ALTER TABLE payments DROP FOREIGN KEY IF EXISTS payments_ibfk_1;
ALTER TABLE maintenance_requests DROP FOREIGN KEY IF EXISTS maintenance_requests_ibfk_1;

-- Drop old tenant_id columns if they exist (after ensuring customer_id is populated)
-- First, make sure customer_id columns exist
ALTER TABLE shops ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE rooms ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE basements ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE agreements ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE ledger ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE payments ADD COLUMN IF NOT EXISTS customer_id INT;
ALTER TABLE maintenance_requests ADD COLUMN IF NOT EXISTS customer_id INT;

-- Add new foreign key constraints for customer_id
ALTER TABLE shops ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE rooms ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE basements ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL;
ALTER TABLE agreements ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE;
ALTER TABLE ledger ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE;
ALTER TABLE payments ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE;
ALTER TABLE maintenance_requests ADD FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE;

-- Note: If you have existing data, you may need to migrate it first using migrate_to_customers.sql

