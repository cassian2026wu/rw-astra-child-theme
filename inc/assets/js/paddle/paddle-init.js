(function () {
    'use strict';

    if (typeof Paddle === 'undefined' || typeof rwPaddleConfig === 'undefined') {
        console.warn('[RW Paddle] Paddle.js or its configuration failed to load.');
        return;
    }

    if (!rwPaddleConfig.token) {
        console.warn('Missing Paddle Client-side Token.');
        return;
    }

    // 设置环境：沙箱用 sandbox，正式用 production
    Paddle.Environment.set(rwPaddleConfig.environment);

    // 初始化
    Paddle.Initialize({
        token: rwPaddleConfig.token,
        eventCallback: function(data) {
            // 支付完成 → 跳转交付页
            if (data.name === 'checkout.completed') {
                var transactionId = data.data.transaction_id;
                var custom = data.data.custom_data || {}; // 读取 Paddle 返回的自定义数据
                var slug = custom.plugin_slug || '';
                var version = custom.plugin_version || '';

                if (transactionId) {
                    window.location.href = 'https://robertwp.com/order-complete/'
                        + '?transaction_id=' + encodeURIComponent(transactionId)
                        + '&slug=' + encodeURIComponent(slug)
                        + '&version=' + encodeURIComponent(version);
                }
            }
            // 结账层加载完成 或 被关闭 → 恢复按钮
            if (data.name === 'checkout.loaded' || data.name === 'checkout.closed') {
                document.querySelectorAll('[data-loading="true"]').forEach(function (btn) {
                    btn.dataset.loading = 'false';
                    btn.style.pointerEvents = '';
                    btn.style.opacity = '';
                    if (btn.dataset.originalHtml) {
                        btn.innerHTML = btn.dataset.originalHtml;
                    }
                });
            }
        }
    });

    //绑定购买按钮
    var paddleButtons = {
        // rw-postviewstats-pro
        'rwpsp-buy-pro': {
            priceId: 'pri_01kffv6wpy2194ks3pg2bm15h3',
            slug: 'rw-postviewstats-pro',
            version: '1.0.0'
        },
        'rwpsp-buy-agency': {
            priceId: 'pri_01kffv8646ks077h1mbbsjedwj',
            slug: 'rw-postviewstats-pro',
            version: '1.0.0'
        }

        // next-plugin-name
    };

    Object.keys(paddleButtons).forEach(function (id) {
        var btn = document.getElementById(id);
        if (!btn) return;

        // 保存原始按钮内容，方便恢复
        btn.dataset.originalHtml = btn.innerHTML;

        btn.addEventListener('click', function (e) {
            e.preventDefault();

            // 防止重复点击：如果已经在加载中，直接返回
            if (btn.dataset.loading === 'true') return;
            btn.dataset.loading = 'true';

            // 禁用按钮 + 添加 loading 图标
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.6';
            //btn.innerHTML = '<span class="rw-loading-spinner"></span>' + btn.dataset.originalHtml;
            btn.innerHTML = btn.dataset.originalHtml;

            var btnData = paddleButtons[id];

            Paddle.Checkout.open({
                items: [{ priceId: btnData.priceId, quantity: 1 }],
                customData: {
                    plugin_slug: btnData.slug,
                    plugin_version: btnData.version
                }
            });
        });
    });

    // 可选：暴露给其他脚本使用
    window.rwPaddleReady = true;
})();