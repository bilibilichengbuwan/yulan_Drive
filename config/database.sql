CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `storage_total` BIGINT DEFAULT 10737418240,
    `storage_used` BIGINT DEFAULT 0,
    `is_admin` TINYINT(1) DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` VARCHAR(20) DEFAULT '',
    `updated_at` VARCHAR(20) DEFAULT '',
    `last_login` VARCHAR(20) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `verify_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) NOT NULL,
    `code` VARCHAR(6) NOT NULL,
    `type` ENUM('register', 'reset_password') NOT NULL,
    `expire_at` VARCHAR(20) NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` VARCHAR(20) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `parent_id` INT DEFAULT 0,
    `name` VARCHAR(255) NOT NULL,
    `type` ENUM('file', 'folder') NOT NULL,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `size` BIGINT DEFAULT 0,
    `path` VARCHAR(500) DEFAULT NULL,
    `md5` VARCHAR(32) DEFAULT NULL,
    `extension` VARCHAR(20) DEFAULT NULL,
    `is_deleted` TINYINT(1) DEFAULT 0,
    `deleted_at` VARCHAR(20) DEFAULT '',
    `created_at` VARCHAR(20) DEFAULT '',
    `updated_at` VARCHAR(20) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `upload_chunks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `file_md5` VARCHAR(64) NOT NULL,
    `chunk_index` INT NOT NULL,
    `chunk_path` VARCHAR(500) NOT NULL,
    `created_at` VARCHAR(20) DEFAULT '',
    UNIQUE KEY `unique_chunk` (`user_id`, `file_md5`, `chunk_index`),
    KEY `idx_upload_user` (`user_id`, `file_md5`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `shares` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `file_id` INT NOT NULL,
    `share_code` VARCHAR(32) NOT NULL UNIQUE,
    `password` VARCHAR(255) DEFAULT NULL,
    `expire_at` VARCHAR(20) DEFAULT '',
    `max_downloads` INT DEFAULT 0,
    `download_count` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` VARCHAR(20) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `operation_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `target_type` VARCHAR(20) DEFAULT NULL,
    `target_id` INT DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` VARCHAR(20) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `download_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `file_id` INT NOT NULL,
    `share_id` INT DEFAULT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `expires_at` VARCHAR(20) NOT NULL,
    `created_at` VARCHAR(20) NOT NULL,
    `used` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`key`, `value`) VALUES 
('brand_name', '欲蓝网盘'),
('brand_slogan', '干净的私人云盘'),
('register_enabled', '1'),
('trash_days', '7')
ON DUPLICATE KEY UPDATE value = VALUES(value);
