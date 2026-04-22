-- ============================================================
-- AI Prompt Security Gateway — Database Schema
-- Database: prompt_gateway
-- ============================================================

CREATE DATABASE IF NOT EXISTS `prompt_gateway` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `prompt_gateway`;

-- ============================================================
-- Attack Categories
-- ============================================================
CREATE TABLE IF NOT EXISTS `attack_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `severity_weight` DECIMAL(3,2) DEFAULT 1.00,
  `color` VARCHAR(7) DEFAULT '#ff4444',
  `icon` VARCHAR(50) DEFAULT 'fa-shield-alt',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Detection Rules
-- ============================================================
CREATE TABLE IF NOT EXISTS `rules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `category_id` INT NOT NULL,
  `pattern` TEXT NOT NULL,
  `pattern_type` ENUM('regex','keyword','phrase') NOT NULL DEFAULT 'regex',
  `severity` ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `severity_score` INT NOT NULL DEFAULT 50,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `false_positive_rate` DECIMAL(5,2) DEFAULT 0.00,
  `match_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `attack_categories`(`id`) ON DELETE CASCADE,
  INDEX `idx_category` (`category_id`),
  INDEX `idx_active` (`is_active`),
  INDEX `idx_severity` (`severity`)
) ENGINE=InnoDB;

-- ============================================================
-- Prompt Logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `prompt_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `prompt_text` TEXT NOT NULL,
  `sanitized_text` TEXT,
  `risk_score` INT NOT NULL DEFAULT 0,
  `verdict` ENUM('safe','suspicious','blocked') NOT NULL DEFAULT 'safe',
  `matched_rules_count` INT DEFAULT 0,
  `categories_matched` VARCHAR(500) DEFAULT NULL,
  `analysis_time_ms` INT DEFAULT 0,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `source` ENUM('testbench','api','batch') DEFAULT 'api',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_verdict` (`verdict`),
  INDEX `idx_risk_score` (`risk_score`),
  INDEX `idx_created` (`created_at`),
  INDEX `idx_source` (`source`)
) ENGINE=InnoDB;

-- ============================================================
-- Rule Matches (junction table)
-- ============================================================
CREATE TABLE IF NOT EXISTS `rule_matches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `log_id` INT NOT NULL,
  `rule_id` INT NOT NULL,
  `matched_text` TEXT,
  `position_start` INT DEFAULT 0,
  `position_end` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`log_id`) REFERENCES `prompt_logs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`rule_id`) REFERENCES `rules`(`id`) ON DELETE CASCADE,
  INDEX `idx_log` (`log_id`),
  INDEX `idx_rule` (`rule_id`)
) ENGINE=InnoDB;

-- ============================================================
-- System Settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `setting_type` ENUM('string','int','float','bool','json') DEFAULT 'string',
  `description` VARCHAR(500),
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Sanitization History
-- ============================================================
CREATE TABLE IF NOT EXISTS `sanitization_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `log_id` INT NOT NULL,
  `original_fragment` TEXT NOT NULL,
  `sanitized_fragment` TEXT NOT NULL,
  `sanitization_type` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`log_id`) REFERENCES `prompt_logs`(`id`) ON DELETE CASCADE,
  INDEX `idx_log` (`log_id`)
) ENGINE=InnoDB;
