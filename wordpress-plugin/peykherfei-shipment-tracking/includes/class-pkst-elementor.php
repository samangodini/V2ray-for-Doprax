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
		add_action( 'wp_ajax_pkst_widget_login', array( __CLASS__, 'ajax_login' ) );
		add_action( 'wp_ajax_nopriv_pkst_widget_login', array( __CLASS__, 'ajax_login' ) );
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
		wp_localize_script(
			'pkst-account-widget',
			'PKST_ACCT',
			array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) )
		);
	}

	/**
	 * Same wp_signon() call the [pkst_panel] login form itself uses
	 * (PKST_Public::maybe_handle_login()), just answered as JSON instead of
	 * a full-page redirect so the widget's popup can log a visitor in
	 * without leaving the page they were on. Never reveals whether the
	 * username or the password was the wrong part, to avoid account
	 * enumeration.
	 */
	public static function ajax_login() {
		check_ajax_referer( 'pkst_widget_login' );

		$username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';

		if ( '' === $username || '' === $password ) {
			wp_send_json_error( array( 'message' => __( 'لطفاً نام کاربری و رمز عبور را وارد کنید.', 'peykherfei-shipment-tracking' ) ) );
		}

		$user = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => true,
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			wp_send_json_error( array( 'message' => __( 'نام کاربری یا رمز عبور اشتباه است.', 'peykherfei-shipment-tracking' ) ) );
		}

		$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : home_url( '/' );
		wp_send_json_success( array( 'redirect' => $redirect ) );
	}
}
