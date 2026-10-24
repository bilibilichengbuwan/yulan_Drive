<?php

/**
 * 图片缩略图生成（基于 GD，零外部依赖）。
 * 支持 jpeg/png/gif/webp/bmp，输出统一为 jpeg，居中等比缩放。
 * 若环境无 GD 或未开启对应解码器，优雅返回 false，由调用方降级处理。
 */
class Thumbnail {

    /** 常见图片 mime -> GD 解码器 */
    private static $decoders = [
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png'  => 'imagecreatefrompng',
        'image/gif'  => 'imagecreatefromgif',
        'image/webp' => 'imagecreatefromwebp',
        'image/bmp'  => 'imagecreatefrombmp',
    ];

    /**
     * 生成缩略图。
     * @param string $srcPath   源文件绝对路径
     * @param string $destPath  目标文件绝对路径（建议 .jpg）
     * @param int    $maxSize   缩略图最长边像素
     * @return string|false     成功返回目标路径，失败返回 false
     */
    public static function generate($srcPath, $destPath, $maxSize = 360) {
        if (!function_exists('gd_info') || !is_file($srcPath) || !is_readable($srcPath)) {
            return false;
        }
        $info = @getimagesize($srcPath);
        if (!$info || empty($info['mime']) || !isset(self::$decoders[$info['mime']])) {
            return false;
        }
        $decode = self::$decoders[$info['mime']];
        $src = @$decode($srcPath);
        if (!$src) {
            return false;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $ratio = min($maxSize / $w, $maxSize / $h, 1);
        $tw = max(1, (int) round($w * $ratio));
        $th = max(1, (int) round($h * $ratio));

        $thumb = imagecreatetruecolor($tw, $th);
        // 保留透明通道（png/gif/webp）
        if (in_array($info['mime'], ['image/png', 'image/gif', 'image/webp'], true)) {
            imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        } else {
            $white = imagecolorallocate($thumb, 255, 255, 255);
            imagefill($thumb, 0, 0, $white);
        }

        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

        $dir = dirname($destPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ok = @imagejpeg($thumb, $destPath, 82);
        imagedestroy($src);
        imagedestroy($thumb);

        return $ok ? $destPath : false;
    }

    /** 判断 mime 是否为可生成缩略图的图片类型 */
    public static function isSupportedImage($mime) {
        return isset(self::$decoders[$mime]);
    }
}
