-- SMS Integration Migration
-- Run this script to add SMS-related tables

USE bake_and_take;

-- SMS Log table - tracks all outbound and inbound SMS messages
CREATE TABLE IF NOT EXISTS sms_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    direction ENUM('outbound', 'inbound') NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'delivered', 'failed', 'received') DEFAULT 'pending',
    gateway_response TEXT,
    order_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_phone (phone_number),
    INDEX idx_direction (direction),
    INDEX idx_status (status)
);

-- OTP Verification table - for phone number verification
CREATE TABLE IF NOT EXISTS sms_otp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    purpose ENUM('order_verify', 'phone_verify', 'login', 'other') DEFAULT 'other',
    reference_id INT NULL,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone_otp (phone_number, otp_code),
    INDEX idx_expires (expires_at)
);
