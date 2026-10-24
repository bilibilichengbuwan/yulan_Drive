<?php

/**
 * 增量 schema 迁移（幂等）。
 * 1.0.1 -> 1.0.2/1.0.3 升级时，数据库表结构不变，但 1.0.3 新增了
 * files.thumbnail（缩略图路径）与 shares.view_count（访问计数）两个字段。
 * 这里在每次请求时按需补齐，保证老库升级无需手动执行 SQL。
 */
class Schema {

    public static function migrate() {
        if (Setting::get('schema_v103') === '1') {
            return;
        }
        $db = Database::getInstance();
        $statements = [
            "ALTER TABLE files ADD COLUMN thumbnail VARCHAR(500) DEFAULT NULL",
            "ALTER TABLE shares ADD COLUMN view_count INT NOT NULL DEFAULT 0",
        ];
        foreach ($statements as $sql) {
            try {
                $db->execute($sql);
            } catch (\Throwable $e) {
                // 列已存在时 ALTER 会报错，忽略即可（幂等）
            }
        }
        Setting::set('schema_v103', '1');
    }
}
