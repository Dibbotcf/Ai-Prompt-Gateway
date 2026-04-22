-- ============================================================
-- AI Prompt Security Gateway — Migration v2
-- Activities & AI Model Integration
-- ============================================================

USE `prompt_gateway`;

-- ============================================================
-- Activities (named sessions grouping multiple prompts)
-- ============================================================
CREATE TABLE IF NOT EXISTS `activities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `user_model` VARCHAR(100) NOT NULL DEFAULT 'custom',
  `destination_model` VARCHAR(100) NOT NULL DEFAULT 'simulated',
  `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
  `total_prompts` INT DEFAULT 0,
  `blocked_prompts` INT DEFAULT 0,
  `avg_risk_score` DECIMAL(5,2) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `closed_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB;

-- ============================================================
-- Activity Prompts (individual prompts within an activity)
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_prompts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `activity_id` INT NOT NULL,
  `log_id` INT DEFAULT NULL,
  `prompt_text` TEXT NOT NULL,
  `risk_score` INT NOT NULL DEFAULT 0,
  `verdict` ENUM('safe','suspicious','blocked') NOT NULL DEFAULT 'safe',
  `ai_response` TEXT DEFAULT NULL,
  `ai_model_used` VARCHAR(100) DEFAULT NULL,
  `ai_response_time_ms` INT DEFAULT 0,
  `sequence_order` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`activity_id`) REFERENCES `activities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`log_id`) REFERENCES `prompt_logs`(`id`) ON DELETE SET NULL,
  INDEX `idx_activity` (`activity_id`),
  INDEX `idx_sequence` (`activity_id`, `sequence_order`)
) ENGINE=InnoDB;

-- ============================================================
-- Add AI API Keys to settings
-- ============================================================
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('ai_gemini_api_key', '', 'string', 'Google Gemini API Key'),
('ai_gemini_model', 'gemini-2.0-flash', 'string', 'Gemini model name'),
('ai_openai_api_key', '', 'string', 'OpenAI API Key'),
('ai_openai_model', 'gpt-3.5-turbo', 'string', 'OpenAI model name'),
('ai_groq_api_key', '', 'string', 'Groq API Key'),
('ai_groq_model', 'llama3-8b-8192', 'string', 'Groq model name'),
('ai_default_provider', 'simulated', 'string', 'Default AI provider (simulated, gemini, openai, groq)');
