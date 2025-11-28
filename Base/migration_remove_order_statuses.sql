-- Migration script to remove 'awaiting_payment' and 'refunded' from order_status_enum
-- This script updates the ENUM type in PostgreSQL

-- Step 1: Update any existing orders with 'awaiting_payment' to 'pending'
UPDATE aiolia.orders 
SET status = 'pending' 
WHERE status = 'awaiting_payment';

-- Step 2: Update any existing orders with 'refunded' to 'cancelled'
UPDATE aiolia.orders 
SET status = 'cancelled' 
WHERE status = 'refunded';

-- Step 3: Drop the old ENUM type and recreate it without the removed statuses
-- Note: This requires dropping dependent objects first

-- Create a new ENUM type with the desired values
CREATE TYPE order_status_enum_new AS ENUM ('pending', 'paid', 'cancelled', 'failed');

-- Step 4: Alter the orders table to use the new type
ALTER TABLE aiolia.orders 
ALTER COLUMN status TYPE order_status_enum_new 
USING status::text::order_status_enum_new;

-- Step 5: Drop the old ENUM type
DROP TYPE order_status_enum;

-- Step 6: Rename the new type to the original name
ALTER TYPE order_status_enum_new RENAME TO order_status_enum;

-- Verification query (uncomment to check)
-- SELECT DISTINCT status FROM aiolia.orders ORDER BY status;

