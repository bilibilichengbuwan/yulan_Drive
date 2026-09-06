<?php
return [
    'name' => '欲蓝网盘',
    'version' => '1.0.0',
    'debug' => false,
    
    'base_url' => 'http://localhost',
    
    'session_lifetime' => 7200,
    
    'upload_max_size' => 1024 * 1024 * 1024 * 2,
    'upload_storage_dir' => dirname(__DIR__) . '/storage/uploads',
    'upload_chunk_size' => 5 * 1024 * 1024,
    'upload_allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', '7z', 'mp3', 'mp4', 'avi', 'mkv', 'mov', 'html', 'css', 'js', 'json', 'xml', 'sql', 'py', 'java', 'c', 'cpp', 'rb', 'go', 'rs'],
    
    'default_user_space' => 1024 * 1024 * 1024 * 10,
    
    'trash_keep_days' => 30,
    
    'share_default_expire' => 7 * 24 * 3600,
    
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 465,
        'username' => 'your-email@example.com',
        'password' => 'your-password',
        'from_name' => '欲蓝网盘',
        'from_email' => 'your-email@example.com',
        'encryption' => 'ssl',
    ],
    
    'admin_email' => 'admin@example.com',
];
