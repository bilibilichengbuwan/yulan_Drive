<?php

class Helper {
    
    
    public static function randomString($length = 32) {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $str;
    }
    
    
    public static function randomCode($length = 6) {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }
    
    
    public static function formatSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
    
    
    public static function getMimeType($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'bmp' => 'image/bmp', 'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain', 'html' => 'text/html', 'css' => 'text/css',
            'js' => 'application/javascript', 'json' => 'application/json',
            'xml' => 'application/xml', 'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed', '7z' => 'application/x-7z-compressed',
            'mp3' => 'audio/mpeg', 'mp4' => 'video/mp4', 'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska', 'mov' => 'video/quicktime',
            'php' => 'text/x-php', 'py' => 'text/x-python',
            'java' => 'text/x-java', 'c' => 'text/x-c', 'cpp' => 'text/x-c++',
            'rb' => 'text/x-ruby', 'go' => 'text/x-go', 'rs' => 'text/x-rust',
            'sql' => 'application/sql', 'sh' => 'application/x-sh',
        ];
        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }
    
    
    public static function getFileIcon($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $icons = [
            'jpg' => 'fa-file-image', 'jpeg' => 'fa-file-image', 'png' => 'fa-file-image', 'gif' => 'fa-file-image', 'bmp' => 'fa-file-image',
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word', 'docx' => 'fa-file-word',
            'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel',
            'ppt' => 'fa-file-powerpoint', 'pptx' => 'fa-file-powerpoint',
            'txt' => 'fa-file-lines', 'html' => 'fa-file-code', 'css' => 'fa-file-code', 'js' => 'fa-file-code',
            'php' => 'fa-file-code', 'py' => 'fa-file-code', 'java' => 'fa-file-code', 'c' => 'fa-file-code', 'cpp' => 'fa-file-code',
            'zip' => 'fa-file-zipper', 'rar' => 'fa-file-zipper', '7z' => 'fa-file-zipper',
            'mp3' => 'fa-file-audio',
            'mp4' => 'fa-file-video', 'avi' => 'fa-file-video', 'mkv' => 'fa-file-video', 'mov' => 'fa-file-video',
            'sql' => 'fa-database', 'json' => 'fa-file-code', 'xml' => 'fa-file-code',
        ];
        $icon = $icons[$ext] ?? 'fa-file';
        return '<i class="fas ' . $icon . ' file-type-icon" aria-hidden="true"></i>';
    }
    
    
    public static function isImage($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
    }
    
    
    public static function isVideo($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'avi', 'mkv', 'mov', 'webm']);
    }
    
    
    public static function isAudio($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['mp3', 'wav', 'ogg', 'flac', 'aac']);
    }
    
    
    public static function isPdf($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return $ext === 'pdf';
    }
    
    
    public static function isText($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['txt', 'html', 'css', 'js', 'json', 'xml', 'php', 'py', 'java', 'c', 'cpp', 'rb', 'go', 'rs', 'sql', 'sh', 'md', 'log', 'ini', 'conf', 'yaml', 'yml']);
    }
    
    
    public static function getClientIp() {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    
    public static function jsonResponse($data, $code = 200) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    
    public static function success($message = '操作成功', $data = null) {
        self::jsonResponse([
            'code' => 0,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    
    public static function error($message = '操作失败', $code = 400) {
        self::jsonResponse([
            'code' => 1,
            'message' => $message,
        ], $code);
    }
    
    
    public static function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    
    public static function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}
