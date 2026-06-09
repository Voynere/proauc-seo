<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Wpcf7_Redirect
 * @subpackage Wpcf7_Redirect
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpcf7_Redirect
 * @subpackage Wpcf7_Redirect
 * @author     Lior Regev <regevlio@gmail.com>
 */
class Wpcf7_Redirect {

	/**
	 * Instance of the main plugin class object.
	 *
	 * @var [object]
	 */
	public $cf7_redirect_base;

	/**
	 * Constructor
	 */
	public function init() {
		$this->define();
		$this->load_dependencies();
		$this->cf7_redirect_base = new WPCF7R_Base();

		add_action( 'plugins_loaded', array( $this, 'notice_to_remove_old_plugin' ) );
	}

	/**
	 * Load dependencies
	 */
	public function load_dependencies() {
		// Load all actions.
		foreach ( glob( WPCF7_PRO_REDIRECT_BASE_PATH . 'modules/*.php' ) as $filename ) {
			require_once $filename;
		}
		require_once WPCF7_PRO_REDIRECT_CLASSES_PATH . 'class-wpcf7r-base.php';
	}

	/**
	 * Notice to remove old plugin
	 */
	public function notice_to_remove_old_plugin() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'cf7-to-api/cf7-to-api.php' ) ) {
			add_action( 'admin_notices', 'wpcf7_remove_contact_form_7_to_api' );
		}
	}

	/**
	 * Defines
	 */
	public function define() {
		define( 'WPCF7_PRO_REDIRECT_BASE_NAME', plugin_basename( __FILE__ ) );
		define( 'WPCF7_PRO_REDIRECT_BASE_PATH', plugin_dir_path( __FILE__ ) );
		define( 'WPCF7_PRO_REDIRECT_BASE_URL', plugin_dir_url( __FILE__ ) );
		define( 'WPCF7_PRO_REDIRECT_PLUGINS_PATH', plugin_dir_path( __DIR__ ) );
		define( 'WPCF7_PRO_REDIRECT_TEMPLATE_PATH', WPCF7_PRO_REDIRECT_BASE_PATH . 'templates/' );
		define( 'WPCF7_PRO_REDIRECT_ACTIONS_TEMPLATE_PATH', WPCF7_PRO_REDIRECT_CLASSES_PATH . 'actions/html/' );
		define( 'WPCF7_PRO_REDIRECT_ADDONS_PATH', WPCF7_PRO_REDIRECT_PLUGINS_PATH . 'wpcf7r-addons/' );
		define( 'WPCF7_PRO_REDIRECT_ACTIONS_PATH', WPCF7_PRO_REDIRECT_CLASSES_PATH . 'actions/' );
		define( 'WPCF7_PRO_REDIRECT_FIELDS_PATH', WPCF7_PRO_REDIRECT_TEMPLATE_PATH . 'fields/' );
		define( 'WPCF7_PRO_REDIRECT_POPUP_TEMPLATES_PATH', WPCF7_PRO_REDIRECT_TEMPLATE_PATH . 'popups/' );
		define( 'WPCF7_PRO_REDIRECT_POPUP_TEMPLATES_URL', WPCF7_PRO_REDIRECT_BASE_URL . '/templates/popups/' );
		define( 'WPCF7_PRO_REDIRECT_ASSETS_PATH', WPCF7_PRO_REDIRECT_BASE_URL . 'assets/' );
		define( 'WPCF7_PRO_REDIRECT_BUILD_PATH', WPCF7_PRO_REDIRECT_BASE_URL . 'build/' );

		define( 'QFORM_BASE', WPCF7_PRO_REDIRECT_BASE_PATH . 'form/' );
	}
}
