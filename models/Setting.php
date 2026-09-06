<?php

class Setting extends Model {
    protected $table = 'settings';
    protected $primaryKey = 'key';
    
    
    public static function get($key, $default = '') {
        $db = Database::getInstance();
        $result = $db->fetchOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
        return $result ? $result['value'] : $default;
    }
    
    
    public static function set($key, $value) {
        $db = Database::getInstance();
        $existing = $db->fetchOne("SELECT `key` FROM settings WHERE `key` = ?", [$key]);
        
        if ($existing) {
            $db->execute("UPDATE settings SET value = ? WHERE `key` = ?", [$value, $key]);
        } else {
            $db->insert("INSERT INTO settings (`key`, value) VALUES (?, ?)", [$key, $value]);
        }
    }
    
    
    public static function all() {
        $db = Database::getInstance();
        $results = $db->fetchAll("SELECT * FROM settings");
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }
    
    
    public static function setMany($data) {
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
    }
    
    
    public static function getBrandName() {
        return self::get('brand_name', '欲蓝网盘');
    }
}
