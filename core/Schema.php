<?php

/**
 * 增量 schema 迁移（幂等）。
 *
 * v1.0.3：files.thumbnail（缩略图路径）、shares.view_count（访问计数）
 * v1.0.5：多语言（中/英/文言文）、文件大小列清晰化、移动端长文本适配
 * v1.0.4：files.starred / files.tags（星标与标签）、users.nickname / totp_secret /
 *         totp_enabled / notify_email、user_sessions（登录设备）、api_tokens（开放 API）、
 *         offline_tasks（离线下载）
 *
 * 每次请求按需补齐，保证老库升级无需手动执行 SQL。
 */
class Schema {

    public static function migrate() {
        $db = Database::getInstance();

        if (Setting::get('schema_v103') !== '1') {
            $statements = [
                "ALTER TABLE files ADD COLUMN thumbnail VARCHAR(500) DEFAULT NULL",
                "ALTER TABLE shares ADD COLUMN view_count INT NOT NULL DEFAULT 0",
            ];
            foreach ($statements as $sql) {
                try { $db->execute($sql); } catch (\Throwable $e) {}
            }
            Setting::set('schema_v103', '1');
        }

        if (Setting::get('schema_v104') !== '1') {
            $statements = [
                // 星标与标签
                "ALTER TABLE files ADD COLUMN starred TINYINT NOT NULL DEFAULT 0",
                "ALTER TABLE files ADD COLUMN tags VARCHAR(500) NOT NULL DEFAULT ''",
                // 用户扩展字段
                "ALTER TABLE users ADD COLUMN nickname VARCHAR(64) NOT NULL DEFAULT ''",
                "ALTER TABLE users ADD COLUMN totp_secret VARCHAR(64) NOT NULL DEFAULT ''",
                "ALTER TABLE users ADD COLUMN totp_enabled TINYINT NOT NULL DEFAULT 0",
                "ALTER TABLE users ADD COLUMN notify_email TINYINT NOT NULL DEFAULT 1",
                "ALTER TABLE users ADD COLUMN session_revoked_at DATETIME DEFAULT NULL",
                // 登录设备（会话级）
                "CREATE TABLE IF NOT EXISTS user_sessions (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    session_id VARCHAR(128) NOT NULL,
                    user_agent VARCHAR(500) NOT NULL DEFAULT '',
                    ip VARCHAR(64) NOT NULL DEFAULT '',
                    last_seen DATETIME NOT NULL,
                    created_at DATETIME NOT NULL,
                    UNIQUE KEY uk_session (session_id),
                    KEY idx_user (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                // 开放 API Token（哈希存储）
                "CREATE TABLE IF NOT EXISTS api_tokens (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    name VARCHAR(100) NOT NULL DEFAULT '',
                    token_hash VARCHAR(64) NOT NULL,
                    last_used_at DATETIME DEFAULT NULL,
                    created_at DATETIME NOT NULL,
                    UNIQUE KEY uk_hash (token_hash),
                    KEY idx_user (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                // 离线下载任务
                "CREATE TABLE IF NOT EXISTS offline_tasks (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    url VARCHAR(1000) NOT NULL,
                    filename VARCHAR(255) NOT NULL DEFAULT '',
                    parent_id INT NOT NULL DEFAULT 0,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    message VARCHAR(500) NOT NULL DEFAULT '',
                    file_id INT DEFAULT NULL,
                    size BIGINT NOT NULL DEFAULT 0,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME DEFAULT NULL,
                    KEY idx_user (user_id, status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            ];
            foreach ($statements as $sql) {
                try { $db->execute($sql); } catch (\Throwable $e) {}
            }
            Setting::set('schema_v104', '1');
        }
    }
}
