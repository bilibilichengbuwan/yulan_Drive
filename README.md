#yulan Drive
-欲蓝网盘是一个开源项目
##用户系统
-登录，注册，密码找回
##文件功能
-大文件上传，分片上传，文件上传
##分享功能
分享密码保护，转存到我的网盘，直接下载文件
##环境要求
-php 7.4 -mysql 5.7+
nginx需要配置伪静态
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