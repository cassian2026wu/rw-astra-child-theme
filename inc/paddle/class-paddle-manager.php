<?php

if (!defined('ABSPATH')) {
    exit;
}

class RW_Paddle_Manager {

    public function __construct() {
        // 配置传递：依赖 RW_Assets_Manager 已注册的 handle
        add_action('wp_enqueue_scripts', [$this, 'localize_paddle_config'], 20);
    }

    /**
     * 需要加载 Paddle 的页面 slug 清单
     * 新增插件时只需在数组里加一项，或用 filter 从外部追加
     */
    public static function get_paddle_pages(): array
    {
        static $pages = null;
        if ($pages === null) {
            $pages = apply_filters('rw_paddle_pages', [
                'rw-postviewstats-pro',
            ]);
        }
        return $pages;
    }

    public static function is_paddle_page(): bool
    {
        foreach (self::get_paddle_pages() as $slug) {
            if (is_page($slug)) {
                return true;
            }
        }
        // 自定义路由兜底
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($request_uri, PHP_URL_PATH);
        $path = trim($path, '/');
        foreach (self::get_paddle_pages() as $slug) {
            if ($path === $slug || str_starts_with($path, $slug . '/')) {
                return true;
            }
        }
        return false;
    }

    public function localize_paddle_config(): void
    {
        if (!wp_script_is('rw-paddle-init', 'enqueued')) {
            return;
        }

        wp_localize_script('rw-paddle-init', 'rwPaddleConfig', array(
            'token'       => $this->get_paddle_client_token(),
            'environment' => $this->get_paddle_environment(),
        ));
    }

    private function get_paddle_environment(): string
    {
        return defined('RW_PADDLE_ENV') ? RW_PADDLE_ENV : 'sandbox';
    }

    private function get_paddle_client_token() {
        return get_option('rw_paddle_client_token', '');
    }

}