<?php
/**
 * Ultimate Member 账户菜单项管理
 */

class RW_UM_Account_Tabs
{
    public function __construct()
    {
        // 1. 注册新的 Tab（并设置自定义图标）
        add_filter('um_account_page_default_tabs_hook', [$this, 'add_custom_tabs'], 20);

        // 2. 重新排序导航栏（UM 的 Tab 排序通常通过优先级控制，这里额外做一次数组干预）
        add_filter('um_account_page_default_tabs_hook', [$this, 'reorder_tabs'], 999);

        // 3. 隐藏不需要在导航菜单中显示的 Tab（比如 order_detail）
        add_filter('um_account_page_default_tabs_hook', [$this, 'hide_navigation_tabs'], 1000);

        // 4. 渲染自定义 Tab 的内容（加载你的 PHP 模板）
        // UM 核心是不允许直接通过 filter 引入 PHP 文件的，需要接管它的内容输出
        add_action('um_account_content_hook_my_licenses', [$this, 'render_my_licenses']);
        add_action('um_account_content_hook_my_orders', [$this, 'render_my_orders']);
        add_action('um_account_content_hook_order_detail', [$this, 'render_order_detail']);
    }

    /**
     * 注册新 Tab
     */
    public function add_custom_tabs($tabs)
    {
        // UM 的 Tab 数组结构：[优先级] => [属性数组]
        $tabs[30] = [
            'name' => 'My Licenses',
            'icon' => 'um-faicon-key', // UM 使用自带的图标类名
            'custom' => true, // 告诉 UM 这是自定义 Tab
        ];

        $tabs[31] = [
            'name' => 'My Orders',
            'icon' => 'um-faicon-shopping-cart',
            'custom' => true,
        ];

        // 订单详情（隐藏的子页面）
        $tabs[32] = [
            'name' => 'Order Detail',
            'icon' => 'um-faicon-file-text-o',
            'custom' => true,
        ];

        return $tabs;
    }

    /**
     * 重新排序
     * UM 的菜单顺序是由数组的 Key（数字）决定的，数字越小越靠前。
     * 默认的 account, notifications 等一般在 10, 20 左右。
     */
    public function reorder_tabs($tabs)
    {
        // 由于上面我们设置了 30, 31, 32，它们默认就会排在靠前。
        // 如果你需要精确调整，可以在 UM 后台的“账户”设置里调整默认 Tab 的顺序，
        // 或者在这里通过 unset 和重新赋值来精准控制。
        return $tabs;
    }

    /**
     * 隐藏导航中的 Tab
     */
    public function hide_navigation_tabs($tabs)
    {
        // 隐藏 order_detail
        foreach ($tabs as $key => $tab) {
            if ($tab['name'] === 'Order Detail') {
                unset($tabs[$key]);
            }
        }
        return $tabs;
    }

    /**
     * 渲染各个 Tab 的模板
     */
    public function render_my_licenses()
    {
        // 注意：原有的变量如果需要在模板中使用，需要在这里提前获取
        include ASTRA_CHILD_PATH . '/inc/ultimatemember/templates/licenses.php';
    }

    public function render_my_orders()
    {
        include ASTRA_CHILD_PATH . '/inc/ultimatemember/templates/orders.php';
    }

    public function render_order_detail()
    {
        include ASTRA_CHILD_PATH . '/inc/ultimatemember/templates/order-detail.php';
    }
}