-- PayPal Integration Schema Updates
-- Run this migration after the initial schema.sql

USE bake_and_take;

-- Add PayPal-related columns to orders table
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS paypal_order_id VARCHAR(100) NULL AFTER status,
ADD COLUMN IF NOT EXISTS paypal_payer_id VARCHAR(100) NULL AFTER paypal_order_id,
ADD COLUMN IF NOT EXISTS paypal_capture_id VARCHAR(100) NULL AFTER paypal_payer_id,
ADD COLUMN IF NOT EXISTS paypal_payment_status VARCHAR(50) NULL AFTER paypal_capture_id;

-- Create PayPal transactions log table for auditing
CREATE TABLE IF NOT EXISTS paypal_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    paypal_order_id VARCHAR(100) NOT NULL,
    paypal_capture_id VARCHAR(100) NULL,
    paypal_payer_id VARCHAR(100) NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(50) NOT NULL,
    raw_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_paypal_order_id (paypal_order_id),
    INDEX idx_order_id (order_id)
);

-- Add index for PayPal order lookups
CREATE INDEX IF NOT EXISTS idx_orders_paypal_order_id ON orders(paypal_order_id);
CREATE INDEX IF NOT EXISTS idx_orders_paypal_capture_id ON orders(paypal_capture_id);
