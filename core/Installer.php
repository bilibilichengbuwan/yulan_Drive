<?php

class Installer {
    
    public static function check() {
        $config = require dirname(__DIR__) . '/config/database.php';
        
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
            $db = new PDO($dsn, $config['username'], $config['password']);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

            $result = $db->query("SHOW TABLES LIKE 'users'");
            if ($result && $result->rowCount() > 0) {
                return true; // 已安装，直接返回
            }

            if (strpos($_SERVER['REQUEST_URI'], '/install.php') === false) {
                header('Location: /install.php');
                exit;
            }
            
        } catch (Exception $e) {

            if (strpos($_SERVER['REQUEST_URI'], '/install.php') === false) {
                header('Location: /install.php');
                exit;
            }
        }
        
        return true;
    }
}
