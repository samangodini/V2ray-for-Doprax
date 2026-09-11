<?php
/**
 * Plugin Name: پنل رهگیری مرسولات پیک خرفه
 * Plugin URI: https://peykherfei.com/
 * Description: سامانه اختصاصی ثبت، رهگیری و مدیریت مرسولات به همراه پنل مدیریت (وردپرس) و پنل کاربری برای مشتریان و پیک‌ها؛ شامل اطلاع‌رسانی پیامکی، تأییدیه تحویل (POD)، گزارش‌گیری و وب‌سرویس اتصال به سامانه‌های خارجی.
 * Version: 1.0.0
 * Author: peykherfei.com
 * Text Domain: peykherfei-shipment-tracking
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PKST_VERSION', '1.0.0' );
define( 'PKST_DB_VERSION', '1.0.0' );
define( 'PKST_PLUGIN_FILE', __FILE__ );
define( 'PKST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PKST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PKST_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once PKST_PLUGIN_DIR . 'includes/class-pkst-db.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-status.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-roles.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-activator.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-deactivator.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-tracking-code.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-settings.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-shipment.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-pod.php';
require_once PKST_PLUGIN_DIR . 'includes/sms-gateways/class-pkst-sms-gateway-interface.php';
require_once PKST_PLUGIN_DIR . 'includes/sms-gateways/class-pkst-sms-gateways.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-sms-manager.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-export.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-reports.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-user-manager.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-rest-api.php';
require_once PKST_PLUGIN_DIR . 'includes/class-pkst-geolocation.php';

register_activation_hook( __FILE__, array( 'PKST_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PKST_Deactivator', 'deactivate' ) );

/**
 * Central bootstrap. Kept intentionally flat (no autoloader/DI container)
 * since the plugin is a single cohesive feature set, not a framework.
 */
final class PKST_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ) );

		PKST_SMS_Manager::instance();
		PKST_REST_API::instance();

		// PKST_Public registers shortcodes plus the admin-post/admin-ajax
		// handlers for courier/customer actions. Those handlers must load
		// even on admin-post.php and admin-ajax.php requests, which run
		// with is_admin() === true, so it is not gated on is_admin() at all
		// -- only the wp-admin management screens are.
		require_once PKST_PLUGIN_DIR . 'public/class-pkst-public.php';
		PKST_Public::instance();

		if ( is_admin() ) {
			require_once PKST_PLUGIN_DIR . 'admin/class-pkst-shipments-list-table.php';
			require_once PKST_PLUGIN_DIR . 'admin/class-pkst-admin.php';
			PKST_Admin::instance();
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'peykherfei-shipment-tracking', false, dirname( PKST_PLUGIN_BASENAME ) . '/languages' );
	}

	public function maybe_upgrade() {
		if ( get_option( 'pkst_db_version' ) !== PKST_DB_VERSION ) {
			PKST_Activator::activate();
		}
	}
}

PKST_Plugin::instance();
