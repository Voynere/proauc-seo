<?php
namespace Itgalaxy\Cf7\AmoCRM\Integration\Includes;

use Itgalaxy\Cf7\AmoCRM\Integration\Admin\CF7 as CF7Admin;
use Itgalaxy\Cf7\AmoCRM\Integration\Admin\LogHelper;
use Itgalaxy\Cf7\AmoCRM\Integration\Includes\CF7 as CF7Includes;

class Bootstrap
{
    const OPTIONS_KEY = 'cf7-amocrm-lead-generation-settings';
    const TOKEN_DATA_KEY = 'cf7-amocrm-token-data';
    const PURCHASE_CODE_OPTIONS_KEY = 'cf7-amocrm_purchase_code';
    const META_PREFIX = '_cf7-amocrm-lead-generation-';
    const OPTIONS_PIPELINES = 'cf7-amocrm-pipelines';
    const OPTIONS_CUSTOM_FIELDS = 'cf7-amocrm-custom-fields';
    const OPTIONS_USERS = 'cf7-amocrm-users';
    const GOOGLE_ANALITICS_COOKIES = 'cf7-amocrm-ga-cookie';

    const SEND_CRON_TASK = 'cf7-amocrm-send-cron-task';
    const CRON = 'cf7-amocrm-cron';

    public static $plugin = '';

    public static $sendTypes = [
        'leads',
        'contacts',
        'unsorted'
    ];

    private static $instance = false;

    protected function __construct($file)
    {
        self::$plugin = $file;

        register_activation_hook(
            self::$plugin,
            ['Itgalaxy\Cf7\AmoCRM\Integration\Includes\Bootstrap', 'pluginActivation']
        );
        register_deactivation_hook(
            self::$plugin,
            ['Itgalaxy\Cf7\AmoCRM\Integration\Includes\Bootstrap', 'pluginDeactivation']
        );

        self::fixedOldTokenData();

        add_action('init', [$this, 'utmCookies']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);

        add_action('wp_ajax_cf7AmoAjaxSetUtm', [$this, 'utmCookies']);
        add_action('wp_ajax_nopriv_cf7AmoAjaxSetUtm', [$this, 'utmCookies']);

        Cron::getInstance();
        CF7Includes::getInstance();

        if (is_admin() && is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            add_action('plugins_loaded', function () {
                CF7Admin::getInstance();
                LogHelper::getInstance();
            });
        }
    }

    public static function getInstance($file)
    {
        if (!self::$instance) {
            self::$instance = new self($file);
        }

        return self::$instance;
    }

    public function utmCookies()
    {
        $strposFunction = 'mb_strpos';

        if (!function_exists('mb_strpos')) {
            $strposFunction = 'strpos';
        }

        if (!empty($_GET) && is_array($_GET)) {
            $utmParams = [];

            foreach ($_GET as $key => $value) {
                if ($strposFunction($key, 'utm_') === 0) {
                    $utmParams[$key] = wp_unslash($value);
                }
            }

            if (!empty($utmParams)) {
                setcookie(
                    self::GOOGLE_ANALITICS_COOKIES,
                    wp_json_encode($utmParams),
                    time() + 86400,
                    '/'
                );
            }
        }
    }

    public function enqueueScripts()
    {
        if (
            !is_plugin_active('wp-fastest-cache/wpFastestCache.php') &&
            !is_plugin_active('sg-cachepress/sg-cachepress.php') &&
            (!defined('WP_CACHE') || !WP_CACHE)
        ) {
            return;
        }

        wp_enqueue_script('cf7-amocrm-theme', AssetsHelper::getPathAssetFile('/theme/js/app.js'), false, false);
    }

    public static function pluginActivation()
    {
        PluginRequest::call('plugin_activate');

        if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
            wp_die(
                esc_html__(
                    'To run the plug-in, you must first install and activate the Contact Form 7 plugin.',
                    'cf7-amocrm-integration'
                ),
                esc_html__(
                    'Error while activating the Contact Form 7 plugin - amoCRM - Lead Generation',
                    'cf7-amocrm-integration'
                ),
                [
                    'back_link' => true
                ]
            );
            // Escape ok
        }

        $roles = new \WP_Roles();

        foreach (self::capabilities() as $capGroup) {
            foreach ($capGroup as $cap) {
                $roles->add_cap('administrator', $cap);

                if (is_multisite()) {
                    $roles->add_cap('super_admin', $cap);
                }
            }
        }
    }

    public static function pluginDeactivation()
    {
        PluginRequest::call('plugin_deactivate');
        \wp_clear_scheduled_hook(self::CRON);
    }

    public static function pluginUninstall()
    {
        PluginRequest::call('plugin_uninstall');
    }

    public static function capabilities()
    {
        $capabilities = [];
        $capabilities['core'] = ['manage_' . self::OPTIONS_KEY];
        flush_rewrite_rules(true);

        return $capabilities;
    }

    private function fixedOldTokenData()
    {
        $option = get_option('wpcf7', []);

        if (empty($option) || empty($option[Bootstrap::OPTIONS_KEY])) {
            return;
        }

        $settings = $option[Bootstrap::OPTIONS_KEY];

        if (empty($settings['refresh_token'])) {
            return;
        }

        $tokenData = [
            'access_token' => $settings['access-token'],
            'refresh_token' => $settings['refresh_token'],
            'expires_in' => time(),
        ];

        unset($settings['access-token']);
        unset($settings['refresh_token']);

        $option[Bootstrap::OPTIONS_KEY] = $settings;

        update_option('wpcf7', $option);
        update_option(Bootstrap::TOKEN_DATA_KEY, $tokenData);
    }

    private function __clone()
    {
        // Nothing
    }
}
