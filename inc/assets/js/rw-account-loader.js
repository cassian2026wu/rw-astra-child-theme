
(function ($, window, document) {
    'use strict';

    const RW_Account_Loader = {

        // 资源加载状态
        loadedScripts: {},
        loadedStyles: {},

        // Base 初始化 Promise
        basePromise: null,

        // 当前正在处理的 Tab
        currentTab: null,

        // Tab 对应的资源
        tabs: {
            my_licenses: {
                css: 'rw-account-licenses.css',
                js: 'rw-account-licenses.js'
            },
            my_orders: {
                css: 'rw-account-orders.css',
                js: 'rw-account-orders.js'
            },
            order_detail: {
                css: 'rw-account-order-detail.css',
                js: 'rw-account-order-detail.js'
            }
        },

        init: function () {

            if (!window.wp || !wp.hooks) {
                console.error('[RW Account Loader] WordPress hooks unavailable.');
                return;
            }

            // UM 初始化当前 Tab
            wp.hooks.addAction(
                'um_account_active_tab_inited',
                'robertwp/account-loader',
                (tab) => {
                    this.handleTab(tab);
                }
            );

            // UM 切换 Tab
            wp.hooks.addAction(
                'um_after_account_tab_changed',
                'robertwp/account-loader',
                (tab) => {
                    this.handleTab(tab);
                }
            );

            // 兼容 Loader 加载时 UM 初始化事件已经触发的情况
            $(document).ready(() => {
                const currentTab = $('.um-account-main')
                    .attr('data-current_tab');

                if (currentTab) {
                    this.handleTab(currentTab);
                }
            });
        },

        /**
         * 处理 Tab 切换
         */
        handleTab: async function (tab) {

            const config = this.tabs[tab];

            // 非 RobertWP 自定义 Tab 不处理
            if (!config) {
                return;
            }

            // 防止重复执行同一个 Tab 的加载流程
            this.currentTab = tab;

            try {

                // 先加载 Base
                await this.loadBase();

                // 再加载当前 Tab 的 CSS
                await this.loadStyle(config.css);

                // 最后加载当前 Tab 的 JS
                await this.loadScript(config.js);

            } catch (error) {

                console.error(
                    '[RW Account Loader] Failed to load tab:',
                    tab,
                    error
                );

            }
        },

        /**
         * 加载 Base JS，并等待初始化完成
         */
        loadBase: function () {

            if (!this.basePromise) {

                this.basePromise = this.loadScript(
                    'rw-account-base.js'
                ).then(async () => {

                    if (!window.RW_Account) {
                        throw new Error('RW_Account is unavailable.');
                    }

                    if (window.RW_Account.ready) {
                        await window.RW_Account.ready;
                    }

                    if (!window.RW_Account.getJwtToken()) {
                        throw new Error('JWT token unavailable.');
                    }

                }).catch((error) => {

                    // 加载失败后允许再次尝试
                    this.basePromise = null;

                    throw error;
                });
            }

            return this.basePromise;
        },

        /**
         * 动态加载 JS
         */
        loadScript: function (filename) {

            if (this.loadedScripts[filename]) {
                return this.loadedScripts[filename];
            }

            const url = this.getAssetUrl(filename);

            this.loadedScripts[filename] = new Promise(
                (resolve, reject) => {

                    const script = document.createElement('script');

                    script.src = url;
                    script.async = true;

                    script.onload = resolve;

                    script.onerror = () => {
                        delete this.loadedScripts[filename];

                        reject(
                            new Error('Script load failed: ' + filename)
                        );
                    };

                    document.head.appendChild(script);
                }
            );

            return this.loadedScripts[filename];
        },

        /**
         * 动态加载 CSS
         */
        loadStyle: function (filename) {

            if (this.loadedStyles[filename]) {
                return this.loadedStyles[filename];
            }

            const config = window.rw_account_loader;

            if (!config || !config.css_url) {
                return Promise.reject(
                    new Error('CSS URL configuration unavailable.')
                );
            }

            const url = config.css_url + filename + '?ver=' + config.version;

            this.loadedStyles[filename] = new Promise(
                (resolve, reject) => {

                    const link = document.createElement('link');

                    link.rel = 'stylesheet';
                    link.href = url;

                    link.onload = resolve;

                    link.onerror = () => {
                        delete this.loadedStyles[filename];

                        reject(
                            new Error('Style load failed: ' + filename)
                        );
                    };

                    document.head.appendChild(link);
                }
            );

            return this.loadedStyles[filename];
        },

        /**
         * 获取资源 URL
         */
        getAssetUrl: function (filename, type) {

            const config = window.rw_account_loader;

            if (!config) {
                throw new Error('Asset configuration unavailable.');
            }

            const baseUrl = type === 'css'
                ? config.css_url
                : config.js_url;

            return baseUrl + filename + '?ver=' + config.version;
        },

    };

    RW_Account_Loader.init();

    window.RW_Account_Loader = RW_Account_Loader;

})(jQuery, window, document);
