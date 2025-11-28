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
-- Note: This requires appropriate permissions

-- Check if we can alter the table directly
DO $$
BEGIN
    -- Try to create a new ENUM type with the desired values
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'order_status_enum_new') THEN
        CREATE TYPE aiolia.order_status_enum_new AS ENUM ('pending', 'paid', 'cancelled', 'failed');
    END IF;
    
    -- Try to alter the column to use the new type
    BEGIN
        ALTER TABLE aiolia.orders 
        ALTER COLUMN status TYPE aiolia.order_status_enum_new 
        USING status::text::aiolia.order_status_enum_new;
        
        -- If successful, drop the old type
        DROP TYPE IF EXISTS aiolia.order_status_enum;
        
        -- Rename the new type to the original name
        ALTER TYPE aiolia.order_status_enum_new RENAME TO order_status_enum;
        
        RAISE NOTICE 'Migration completed successfully';
    EXCEPTION WHEN OTHERS THEN
        RAISE NOTICE 'Could not alter table directly. Error: %', SQLERRM;
        RAISE NOTICE 'You may need to run this as a database superuser or owner';
        RAISE NOTICE 'Alternative: The application code will filter out these statuses';
    END;
END $$;

-- Verification query (uncomment to check)
-- SELECT DISTINCT status FROM aiolia.orders ORDER BY status;
