<?php
/**
 * I18n —— 欲蓝网盘多语言核心
 *
 * 设计：「中文原文」作为字典键，译文作为值。
 * 未翻译的键自动回退为中文原文，天然零遗漏、零缺省串。
 * 通过 GET ?lang= / cookie / session / Accept-Language 决定当前语言。
 */

class I18n {
    private static $locale = 'zh-CN';
    private static $dict   = [];
    private static $allowed = ['zh-CN', 'en', 'zh-classical'];

    /**
     * 初始化语言：检测优先级 GET > cookie > session > Accept-Language > zh-CN
     */
    public static function init() {
        $locale = null;

        if (isset($_GET['lang']) && in_array($_GET['lang'], self::$allowed, true)) {
            $locale = $_GET['lang'];
        }
        if ($locale === null && !empty($_COOKIE['lang']) && in_array($_COOKIE['lang'], self::$allowed, true)) {
            $locale = $_COOKIE['lang'];
        }
        if ($locale === null && Session::get('lang') && in_array(Session::get('lang'), self::$allowed, true)) {
            $locale = Session::get('lang');
        }
        if ($locale === null && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $al = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            if (strpos($al, 'en') === 0) {
                $locale = 'en';
            } else {
                $locale = 'zh-CN';
            }
        }
        if ($locale === null) {
            $locale = 'zh-CN';
        }

        self::$locale = $locale;
        Session::set('lang', $locale);
        if (!headers_sent()) {
            setcookie('lang', $locale, time() + 365 * 86400, '/', '', false, false);
        }

        $file = __DIR__ . '/../lang/' . $locale . '.php';
        self::$dict = (file_exists($file)) ? (array) require $file : [];
    }

    /**
     * 翻译：未命中字典则原样返回中文键；支持 %s 占位符替换
     */
    public static function t($key, ...$args) {
        $v = self::$dict[$key] ?? $key;
        if (!empty($args)) {
            $v = preg_replace_callback('/%s/', function () use (&$args) {
                return (string) array_shift($args);
            }, $v);
        }
        return $v;
    }

    public static function current() {
        return self::$locale;
    }

    public static function available() {
        return [
            ['code' => 'zh-CN',        'name' => '简体中文'],
            ['code' => 'en',           'name' => 'English'],
            ['code' => 'zh-classical', 'name' => '文言文'],
        ];
    }

    /**
     * 生成切换语言后的 URL，保留当前其它 query 参数（folder/page/sort…）
     */
    public static function switchUrl($code) {
        $path  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $query = $_GET;
        $query['lang'] = $code;
        $qs = http_build_query($query);
        return $path . ($qs ? '?' . $qs : '');
    }

    /**
     * 页脚语言切换器 + 前端翻译字典注入
     */
    public static function footer() {
        $items = [];
        foreach (self::available() as $l) {
            $active = (self::$locale === $l['code']) ? ' active' : '';
            $href   = htmlspecialchars(self::switchUrl($l['code']), ENT_QUOTES, 'UTF-8');
            $name   = htmlspecialchars($l['name'], ENT_QUOTES, 'UTF-8');
            $items[] = '<a href="' . $href . '" class="lang-opt' . $active . '" data-lang="' . $l['code'] . '">' . $name . '</a>';
        }
        $opts = implode('', $items);
        $dict = json_encode(self::$dict, JSON_UNESCAPED_UNICODE);

        return
'<div class="lang-switch" role="group" aria-label="语言切换">' . "\n" .
'  <span class="lang-label" title="语言"><i class="fas fa-language" aria-hidden="true"></i></span>' . "\n" .
'  ' . $opts . "\n" .
'</div>' . "\n" .
'<script>window.__dict=' . $dict . ';window.__=function(k){if(arguments.length>1){var a=Array.prototype.slice.call(arguments,1);return (window.__dict[k]||k).replace(/%s/g,function(){return a.shift();});}return window.__dict[k]||k;};</script>';
    }
}

if (!function_exists('lang')) {
    function lang($key, ...$args) { return I18n::t($key, ...$args); }
}
if (!function_exists('__')) {
    function __($key, ...$args) { return I18n::t($key, ...$args); }
}
