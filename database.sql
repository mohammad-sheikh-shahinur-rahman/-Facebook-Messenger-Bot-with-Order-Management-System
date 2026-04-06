-- ========================================
-- Facebook Messenger Bot Database Schema
-- Version: 1.0.0
-- ========================================

-- Create Database
CREATE DATABASE IF NOT EXISTS `fb_messenger_bot`;
USE `fb_messenger_bot`;

-- ========================================
-- Table: admins
-- Purpose: Store admin user accounts
-- ========================================
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `username` varchar(100) NOT NULL UNIQUE,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `status` enum('active', 'inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: orders
-- Purpose: Store customer orders from Messenger
-- ========================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `facebook_user_id` varchar(255) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `product` varchar(255) NOT NULL,
  `quantity` int NOT NULL DEFAULT 1,
  `notes` text,
  `status` enum('pending', 'confirmed', 'delivered', 'cancelled') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_facebook_user_id` (`facebook_user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  FULLTEXT INDEX `ft_search` (`customer_name`, `phone`, `product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: customer_sessions
-- Purpose: Store customer order session data
-- ========================================
CREATE TABLE IF NOT EXISTS `customer_sessions` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `facebook_user_id` varchar(255) NOT NULL UNIQUE,
  `session_id` varchar(255) NOT NULL,
  `session_data` longtext NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_facebook_user_id` (`facebook_user_id`),
  INDEX `idx_session_id` (`session_id`),
  INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: messages_log
-- Purpose: Log all incoming/outgoing messages
-- ========================================
CREATE TABLE IF NOT EXISTS `messages_log` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `facebook_user_id` varchar(255) NOT NULL,
  `message_type` enum('incoming', 'outgoing') NOT NULL,
  `message_text` text NOT NULL,
  `is_processed` boolean DEFAULT false,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_facebook_user_id` (`facebook_user_id`),
  INDEX `idx_message_type` (`message_type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: webhook_events
-- Purpose: Log webhook events from Facebook
-- ========================================
CREATE TABLE IF NOT EXISTS `webhook_events` (
  `id` bigint PRIMARY KEY AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `sender_id` varchar(255),
  `recipient_id` varchar(255),
  `payload` longtext NOT NULL,
  `processed` boolean DEFAULT false,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_sender_id` (`sender_id`),
  INDEX `idx_processed` (`processed`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Insert Sample Admin User
-- Default credentials: admin / admin123
-- CHANGE THIS AFTER FIRST LOGIN!
-- ========================================
INSERT IGNORE INTO `admins` (`username`, `email`, `password`, `status`) VALUES
('admin', 'admin@example.com', '$2y$12$6l2EZ3p6/s8X0o1.XvKOLu3c9/L9Zi.m1m2G0gG9QX.5X5G5Q0Epa', 'active');

-- ========================================
-- Stored Procedures
-- ========================================

-- Procedure to get orders summary
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `GetOrdersSummary`()
BEGIN
  SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
  FROM orders;
END$$

DELIMITER ;

-- ========================================
-- Views
-- ========================================

-- View for order statistics
CREATE OR REPLACE VIEW vw_order_stats AS
SELECT 
  DATE(created_at) as order_date,
  COUNT(*) as total_orders,
  COUNT(DISTINCT facebook_user_id) as unique_customers,
  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
  SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
  SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
  SUM(quantity) as total_quantity
FROM orders
GROUP BY DATE(created_at);

-- View for top products
CREATE OR REPLACE VIEW vw_top_products AS
SELECT 
  product,
  COUNT(*) as order_count,
  SUM(quantity) as total_quantity,
  ROUND(COUNT(*) / (SELECT COUNT(*) FROM orders) * 100, 2) as percentage
FROM orders
WHERE status != 'cancelled'
GROUP BY product
ORDER BY order_count DESC;

-- ========================================
-- Indexes for Performance
-- ========================================

-- Additional composite indexes for common queries
ALTER TABLE `orders` ADD INDEX `idx_status_created` (`status`, `created_at`);
ALTER TABLE `customer_sessions` ADD INDEX `idx_expires_created` (`expires_at`, `created_at`);
ALTER TABLE `messages_log` ADD INDEX `idx_type_created` (`message_type`, `created_at`);

-- ========================================
-- END OF SCHEMA
-- ========================================
