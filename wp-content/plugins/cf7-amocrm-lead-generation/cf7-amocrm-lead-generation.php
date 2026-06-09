<?php
/**
 * Plugin Name: Contact Form 7 - amoCRM - Integration
 * Plugin URI: https://codecanyon.net/item/contact-form-7-amocrm-lead-generation/20129763
 * Description: Allows you to integrate your forms and amoCRM
 * Version: 2.4.9
 * Author: itgalaxycompany
 * Author URI: https://codecanyon.net/user/itgalaxycompany
 * License: GPLv3
 * Text Domain: cf7-amocrm-integration
 * Domain Path: /languages/
 */

use Itgalaxy\Cf7\AmoCRM\Integration\Includes\Bootstrap;

if (!defined('ABSPATH')) {
    exit();
}

/*
 * Require for `is_plugin_active` function.
 */
require_once ABSPATH . 'wp-admin/includes/plugin.php';

define('CF7_AMOCRM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CF7_AMOCRM_PLUGIN_VERSION', '2.4.9');
define('CF7_AMOCRM_PLUGIN_DIR', plugin_dir_path(__FILE__));

if (!defined('CF7_AMOCRM_PLUGIN_LOG_FILE')) {
    define('CF7_AMOCRM_PLUGIN_LOG_FILE', wp_upload_dir()['basedir'] . '/logs/.cf7amo.log');
}

/**
 * Registration and load of translations.
 *
 * @link https://developer.wordpress.org/reference/functions/load_theme_textdomain/
 */
load_theme_textdomain('cf7-amocrm-integration', CF7_AMOCRM_PLUGIN_DIR . 'languages');
update_site_option('cf7-amocrm_purchase_code', '72fb7156-b9eb-41ab-889a-0f4bb2b5859f');
/**
 * Use composer autoloader.
 */
require CF7_AMOCRM_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * Register plugin uninstall hook.
 *
 * @link https://developer.wordpress.org/reference/functions/register_uninstall_hook/
 */
register_uninstall_hook(__FILE__, ['Itgalaxy\Cf7\AmoCRM\Integration\Includes\Bootstrap', 'pluginUninstall']);

/**
 * Load plugin.
 */
Bootstrap::getInstance(__FILE__);
