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
        // 渲染自定义 Tab 内容
        add_filter('um_account_content_hook_my_licenses', [$this, 'render_my_licenses']);
        add_filter('um_account_content_hook_my_orders', [$this, 'render_my_orders']);
        add_filter('um_account_content_hook_order_detail', [$this, 'render_order_detail']);

        //
        add_action('um_account_tab__my_licenses', [$this, 'tab_hook_my_licenses']);
        add_action('um_account_tab__my_orders', [$this, 'tab_hook_my_orders']);
        add_action('um_account_tab__order_detail', [$this, 'tab_hook_order_detail']);
    }

    /**
     * 注册新 Tab
     */
    public function add_custom_tabs($tabs): array
    {
        $tabs[30]['my_licenses'] = [
            'title'  => 'My Licenses',   // 注意：UM 用的是 'title' 而不是 'name'
            'icon'   => 'um-faicon-key',
            'custom' => true,
            'show_button' => false,
        ];

        $tabs[31]['my_orders'] = [
            'title'  => 'My Orders',
            'icon'   => 'um-faicon-shopping-cart',
            'custom' => true,
            'show_button' => false,
        ];

        $tabs[32]['order_detail'] = [
            'title'  => 'Order Detail',
            'icon'   => 'um-faicon-file-text-o',
            'custom' => true,
            'show_button' => false,
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
        // 保留 Order Detail Tab，只隐藏导航按钮
        if (isset($tabs[32]['order_detail'])) {
            $tabs[32]['order_detail']['show_button'] = false;
        }

        return $tabs;
    }

    /**
     * 渲染各个 Tab 的模板
     */
    public function render_my_licenses($output = ''): string
    {
        ob_start();
        // 注意：原有的变量如果需要在模板中使用，需要在这里提前获取
        include ASTRA_CHILD_PATH . '/inc/ultimatemember/templates/licenses.php';
        return $output . ob_get_clean();
    }

    public function render_my_orders($output = ''): string
    {
        ob_start();
        include ASTRA_CHILD_PATH . '/inc/ultimatemember/templates/orders.php';
        return $output . ob_get_clean();
    }

    public function render_order_detail($output = ''): string
    {
        ob_start();

        include ASTRA_CHILD_PATH . '/inc/ultimatemember/templates/order-detail.php';

        $html = ob_get_clean();

        return $output . $html;
    }

    public function tab_hook_my_licenses( $info ) {
        global $ultimatemember;
        extract( $info );
        $output = $ultimatemember->account->get_tab_output('my_licenses');
        if ( $output ) { echo $output; }
    }

    public function tab_hook_my_orders( $info ) {
        global $ultimatemember;
        extract( $info );
        $output = $ultimatemember->account->get_tab_output('my_orders');
        if ( $output ) { echo $output; }
    }

    public function tab_hook_order_detail( $info ) {

        error_log('[RW Order Detail] tab_hook_order_detail() called');

        global $ultimatemember;
        extract( $info );

        $output = $ultimatemember->account->get_tab_output('order_detail');

        error_log('[RW Order Detail] get_tab_output() result length: ' . strlen((string) $output));

        if ( $output ) {
            echo $output;
        }
    }
}