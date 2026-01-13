-- Migration: Add Profile Verification Columns
-- This migration adds columns for email and phone change verification

-- Add pending email change columns
ALTER TABLE users ADD COLUMN IF NOT EXISTS pending_email VARCHAR(100) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS pending_email_token VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS pending_email_expires TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS pending_email_old_otp VARCHAR(10) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_change_step VARCHAR(20) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_change_cancel_token VARCHAR(255) NULL;

-- Add pending phone change columns
ALTER TABLE users ADD COLUMN IF NOT EXISTS pending_phone VARCHAR(20) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS pending_phone_otp VARCHAR(10) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS pending_phone_expires TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_change_step VARCHAR(20) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_recovery_token VARCHAR(255) NULL;

-- Add email_verified and phone_verified columns if they don't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone_verified BOOLEAN DEFAULT FALSE;

-- Add indexes for lookup performance
CREATE INDEX IF NOT EXISTS idx_pending_email_token ON users(pending_email_token);
CREATE INDEX IF NOT EXISTS idx_pending_phone ON users(pending_phone);

-- Update existing verified users to have email_verified = TRUE
UPDATE users SET email_verified = TRUE WHERE is_verified = TRUE AND email_verified = FALSE;
