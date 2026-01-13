-- Migration: Final Cleanup of Unused Elements
-- Removes unused tables and columns identified in code analysis

USE bake_and_take;

-- 1. Drop unused newsletter_subscribers table
DROP TABLE IF EXISTS newsletter_subscribers;

-- 2. Drop unused columns from users table
-- These address fields are never used; address data is stored in orders table
ALTER TABLE users DROP COLUMN IF EXISTS address;
ALTER TABLE users DROP COLUMN IF EXISTS city;
ALTER TABLE users DROP COLUMN IF EXISTS state;
ALTER TABLE users DROP COLUMN IF EXISTS zip;

-- 3. Drop unused column from cart table if it exists
-- (session_id was legacy from guest cart implementation)
ALTER TABLE cart DROP COLUMN IF EXISTS session_id;
