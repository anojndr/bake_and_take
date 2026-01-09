-- Migration: Add admin support to users table
-- Run this if you already have an existing database

-- Add is_admin column if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE;

-- Create an admin account (password: admin123)
-- Note: This is a bcrypt hash for 'admin123'
INSERT INTO users (first_name, last_name, email, password, is_admin) VALUES
('Admin', 'User', 'admin@bakeandtake.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE)
ON DUPLICATE KEY UPDATE is_admin = TRUE;

-- Or, to make an existing user an admin (replace with actual email):
-- UPDATE users SET is_admin = TRUE WHERE email = 'your-email@example.com';
