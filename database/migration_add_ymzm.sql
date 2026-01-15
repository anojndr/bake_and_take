-- Migration: Add YMZM user ID column to users table
-- This allows linking Bake & Take accounts with YMZM accounts

ALTER TABLE users ADD COLUMN ymzm_user_id INT NULL AFTER is_admin;

-- Add unique index to ensure one-to-one mapping with YMZM accounts
ALTER TABLE users ADD UNIQUE INDEX idx_ymzm_user_id (ymzm_user_id);

-- Note: ymzm_user_id can be NULL for users who registered directly on Bake & Take
