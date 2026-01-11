-- Migration: Remove unused tables and columns
-- Run this script on existing databases to clean up unused schema elements

USE bake_and_take;

-- Drop unused newsletter_subscribers table
-- The newsletter form exists on the home page but has no backend processing
DROP TABLE IF EXISTS newsletter_subscribers;

-- Remove unused session_id column from cart table
-- Cart requires login, so guest cart feature via session_id is never used
ALTER TABLE cart DROP COLUMN IF EXISTS session_id;

-- Alternative for MySQL versions that don't support DROP COLUMN IF EXISTS:
-- SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
--                WHERE TABLE_SCHEMA = 'bake_and_take' 
--                AND TABLE_NAME = 'cart' 
--                AND COLUMN_NAME = 'session_id');
-- SET @sql := IF(@exist > 0, 'ALTER TABLE cart DROP COLUMN session_id', 'SELECT "Column session_id does not exist"');
-- PREPARE stmt FROM @sql;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;
