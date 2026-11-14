<?php
/**
 * 前端资源管理器
 *
 * @package Astra-Child
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Assets_Manager {

    const STATIC_VERSION = '1.2.14';

    public function __construct() {
        // 基础样式加载
        add_action('wp_enqueue_scripts', [$this, 'enqueue_parent_styles']);

        // 账户页面资源（从原 register-assets.php 迁移）
        add_action('wp_enqueue_scripts', [$this, 'rw_enqueue_account_tabs_assets']);

        // 订单完成页面资源（从 functions.php 迁移）
        add_action('wp_enqueue_scripts', [$this, 'enqueue_order_complete_assets']);

        //
        add_action('wp_enqueue_scripts', [$this, 'enqueue_paddle_scripts']);
    }

    /**
     * 加载父主题样式
     */
    public function enqueue_parent_styles(): void
    {
        wp_enqueue_style(
            'astra-parent-style',
            get_template_directory_uri() . '/style.css'
        );

        wp_enqueue_style(
            'astra-child-style',
            get_stylesheet_uri(),
            ['astra-parent-style']
        );
    }

    /**
     *
     * */
    public function enqueue_paddle_scripts(): void
    {
        if (!class_exists('RW_Paddle_Manager') || !RW_Paddle_Manager::is_paddle_page()) {
            return;
        }

        if (!RW_Paddle_Manager::is_paddle_page()) {
            return;
        }

        // 1. 加载 Paddle 官方 CDN 脚本（v2）
        wp_enqueue_script(
            'paddle-js',
            'https://cdn.paddle.com/paddle/v2/paddle.js',
            array(),
            null,
            true
        );

        // 2. 加载自定义初始化脚本
        wp_enqueue_script(
            'rw-paddle-init',
            ASTRA_CHILD_URL . '/inc/assets/js/paddle/paddle-init.js',
            array('paddle-js'),
            self::STATIC_VERSION,//ASTRA_CHILD_VERSION,
            true
        );

    }

    /**
     * 账户页面资源加载（从原 register-assets.php 迁移）
     */
    public function rw_enqueue_account_tabs_assets(): void
    {
        // 1. 获取当前请求路径
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($request_uri, PHP_URL_PATH);

        // 2. 检查是否在 /account/ 路径下
        if (!preg_match('#/account(?:/|$)#', $path)) {
            return;
        }

        // 3. 准备本地化数据
        $localize_data = array(
            'ajax_url'     => admin_url('admin-ajax.php'),
            'rest_url'     => rest_url(),
            'rest_nonce'   => wp_create_nonce('wp_rest'),
            'user_id'      => get_current_user_id(),
            'nonce'        => wp_create_nonce('rw_account_nonce'),
            'is_logged_in' => is_user_logged_in(),
        );

        // 4. 加载基础 CSS
        wp_enqueue_style(
            'rw-account-base-css',
            ASTRA_CHILD_URL . '/inc/assets/css/rw-account-base.css',
            array(),
            self::STATIC_VERSION
        );

        // 5. 加载 Loader
        wp_enqueue_script(
            'rw-account-loader-js',
            ASTRA_CHILD_URL . '/inc/assets/js/rw-account-loader.js',
            array('jquery', 'wp-hooks'),
            self::STATIC_VERSION,
            true
        );

        // 6. 为 Loader 提供资源路径和版本
        wp_localize_script(
            'rw-account-loader-js',
            'rw_account_loader',
            array(
                'js_url'  => ASTRA_CHILD_URL . '/inc/assets/js/',
                'css_url' => ASTRA_CHILD_URL . '/inc/assets/css/',
                'version' => self::STATIC_VERSION,
            )
        );

        // 7. 将原有的账号数据本地化到 Loader
        wp_localize_script(
            'rw-account-loader-js',
            'rw_ajax',
            $localize_data
        );
    }


    /**
     * 订单完成页面资源加载（从 functions.php 迁移）
     */
    public function enqueue_order_complete_assets(): void
    {
        // 检查是否是订单完成页面
        if (!$this->is_order_complete_page()) {
            return;
        }

        $this->enqueue_order_complete_css();
        $this->enqueue_order_complete_js();
    }

    /**
     * 判断是否是订单完成页面
     */
    private function is_order_complete_page(): bool
    {
        // 通过模板检查
        if (is_page()) {
            $template_slug = get_page_template_slug();
            return $template_slug === 'page-templates/order-complete.php';
        }

        // 或者通过页面 slug 检查（二选一）
        // return is_page('order-complete');

        return false;
    }

    /**
     * 加载订单完成页面的 CSS
     */
    private function enqueue_order_complete_css(): void
    {
        $css_file = '/assets/css/order-complete.min.css';
        $css_path = ASTRA_CHILD_PATH . $css_file;

        if (file_exists($css_path)) {
            wp_enqueue_style(
                'order-complete-min-css',
                ASTRA_CHILD_URL . $css_file,
                [],
                filemtime($css_path)
            );
        }
    }

    /**
     * 加载订单完成页面的 JavaScript
     */
    private function enqueue_order_complete_js(): void
    {
        $js_file = '/assets/js/order-complete.min.js';
        $js_path = ASTRA_CHILD_PATH . $js_file;

        if (file_exists($js_path)) {
            wp_enqueue_script(
                'order-complete-min-js',
                ASTRA_CHILD_URL . $js_file,
                [], // 如果有依赖如 jQuery，可以添加 ['jquery']
                filemtime($js_path),
                true
            );

            // 传递数据到 JavaScript
            $this->localize_order_complete_script();
        }
    }

    /**
     * 本地化订单完成页面的脚本数据
     */
    private function localize_order_complete_script(): void
    {
        $transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';
        $plugin_slug = isset($_GET['slug']) ? sanitize_text_field($_GET['slug']) : '';
        $plugin_version = isset($_GET['version']) ? sanitize_text_field($_GET['version']) : '';

        wp_localize_script('order-complete-min-js', 'orderConfig', [
            'transactionId' => $transaction_id,
            'pluginSlug' => $plugin_slug,
            'pluginVersion' => $plugin_version,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('order_complete_nonce')
        ]);
    }
}