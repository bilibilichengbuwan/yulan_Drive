#yulan Drive - 欲蓝网盘 / Yulan Drive

## 用户系统 / User System
- 登录、注册、密码找回 / Login, registration, password recovery

## 文件功能 / File Features
- 大文件上传、分片上传、文件上传 / Large file upload, chunked upload, file upload

## 分享功能 / Sharing Features
- 分享密码保护、转存到我的网盘、直接下载文件 / Share password protection, save to my cloud disk, direct file download

## 环境要求 / Environment Requirements
- PHP 7.4
- MySQL 5.7+

Nginx 伪静态配置 / Nginx pseudo-static configuration:

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}

location ~ /\.(ht|git|svn) {
    deny all;
}

location ~ ^/(config|core|models|controllers|views|logs|tmp)/ {
    deny all;
}
