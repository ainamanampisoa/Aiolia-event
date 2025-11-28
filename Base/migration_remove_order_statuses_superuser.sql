-- Migration script to remove 'awaiting_payment' and 'refunded' from order_status_enum
-- This script MUST be run as a database superuser or the owner of the orders table
-- Run with: psql -h localhost -U postgres -d aiolia_event -f migration_remove_order_statuses_superuser.sql

-- Step 1: Update any existing orders with 'awaiting_payment' to 'pending'
UPDATE aiolia.orders 
SET status = 'pending' 
WHERE status = 'awaiting_payment';

-- Step 2: Update any existing orders with 'refunded' to 'cancelled'
UPDATE aiolia.orders 
SET status = 'cancelled' 
WHERE status = 'refunded';

-- Step 3: Create a new ENUM type with the desired values
DROP TYPE IF EXISTS aiolia.order_status_enum_new CASCADE;
CREATE TYPE aiolia.order_status_enum_new AS ENUM ('pending', 'paid', 'cancelled', 'failed');

-- Step 4: Alter the orders table to use the new type
ALTER TABLE aiolia.orders 
ALTER COLUMN status TYPE aiolia.order_status_enum_new 
USING status::text::aiolia.order_status_enum_new;

-- Step 5: Drop the old ENUM type
DROP TYPE IF EXISTS aiolia.order_status_enum CASCADE;

-- Step 6: Rename the new type to the original name
ALTER TYPE aiolia.order_status_enum_new RENAME TO order_status_enum;

-- Step 7: Verification
SELECT 'Migration completed successfully!' AS message;
SELECT DISTINCT status FROM aiolia.orders ORDER BY status;

