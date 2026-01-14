-- Migration: Add email verification columns for profile verification
-- Run this migration to add the necessary columns for email verification from profile page

-- Add email_verify_token column (for storing verification token)
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verify_token VARCHAR(255) DEFAULT NULL;

-- Add email_verify_expires column (for token expiration)
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verify_expires DATETIME DEFAULT NULL;
