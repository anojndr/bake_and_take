-- Verification Choice Migration
-- Run this script to update the users table for verification options
-- This allows users to verify via email OR phone

USE bake_and_take;

-- First, check and add columns one by one (ignoring errors if they exist)
-- Note: Run these one at a time if some columns already exist

-- Add is_verified column if it doesn't exist
-- If you get a "Duplicate column" error, the column already exists - safe to ignore
ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE;

-- Add verification_method column
ALTER TABLE users ADD COLUMN verification_method ENUM('email', 'phone') NULL;

-- Add verification_token column  
ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL;

-- Add verification_token_expires_at column
ALTER TABLE users ADD COLUMN verification_token_expires_at TIMESTAMP NULL;

-- Add phone_verified column
ALTER TABLE users ADD COLUMN phone_verified BOOLEAN DEFAULT FALSE;

-- Add email_verified column
ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE;

-- Add index for verification token lookups (ignore if exists)
CREATE INDEX idx_verification_token ON users(verification_token);

-- Update the sms_otp table to add 'registration' as a purpose option
ALTER TABLE sms_otp 
MODIFY COLUMN purpose ENUM('order_verify', 'phone_verify', 'login', 'registration', 'other') DEFAULT 'other';

-- For existing users without verification, set them as verified
UPDATE users SET is_verified = TRUE WHERE is_verified IS NULL OR is_verified = FALSE AND created_at < NOW() - INTERVAL 1 MINUTE;

