<?php

class Controller {
    protected $viewPath = __DIR__ . '/../views/';
    
    
    protected function view($view, $data = []) {
        extract($data);
        $viewFile = $this->viewPath . $view . '.php';
        
        if (!file_exists($viewFile)) {
            die("视图文件不存在: {$view}");
        }
        
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        if (strpos($content, '{{layout}}') !== false) {
            $layoutFile = $this->viewPath . 'layouts/main.php';
            $content = str_replace('{{layout}}', $content, file_get_contents($layoutFile));
        }
        
        echo $content;
    }
    
    
    protected function json($data, $code = 200) {
        Helper::jsonResponse($data, $code);
    }
    
    
    protected function success($message = '操作成功', $data = null) {
        Helper::success($message, $data);
    }
    
    
    protected function error($message = '操作失败', $code = 400) {
        Helper::error($message, $code);
    }
    
    
    protected function redirect($url) {
        Helper::redirect($url);
    }
    
    
    protected function input($key = null, $default = null) {
        $input = $_REQUEST;
        
        if ($key === null) {
            return $input;
        }
        
        return $input[$key] ?? $default;
    }
    
    
    protected function post($key = null, $default = null) {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        if ($key === null) {
            return $data;
        }
        
        return $data[$key] ?? $default;
    }
    
    
    protected function get($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        
        return $_GET[$key] ?? $default;
    }
}
