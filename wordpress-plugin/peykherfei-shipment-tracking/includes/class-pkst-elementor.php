<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the plugin's Elementor widget(s) -- entirely optional, safe to
 * load even when Elementor isn't installed. The widget class itself
 * extends \Elementor\Widget_Base, so it is only ever require_once'd lazily
 * from inside register_widgets(), which only runs if Elementor's own
 * 'elementor/widgets/register' action actually fires (i.e. Elementor is
 * active). Nothing in this loader file references an Elementor class
 * directly, so it is always safe to load unconditionally.
 */
class PKST_Elementor {

	public static function init() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_widgets( $widgets_manager ) {
		require_once PKST_PLUGIN_DIR . 'includes/class-pkst-elementor-account-widget.php';
		$widgets_manager->register( new PKST_Elementor_Account_Widget() );
	}

	/**
	 * Registered (not enqueued) here so Elementor's own
	 * get_style_depends()/get_script_depends() dependency system can pull
	 * these in only on pages where the widget is actually placed --
	 * exactly the same handles the widget class declares.
	 */
	public static function register_assets() {
		wp_register_style( 'pkst-account-widget', PKST_PLUGIN_URL . 'assets/css/account-widget.css', array(), PKST_VERSION );
		wp_register_script( 'pkst-account-widget', PKST_PLUGIN_URL . 'assets/js/account-widget.js', array(), PKST_VERSION, true );
	}
}
