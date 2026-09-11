<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end: the [pkst_panel] (login-aware courier/customer dashboard) and
 * [pkst_track] (public, no-login tracking lookup) shortcodes, plus the
 * form handlers behind them. Status updates are plain POST+redirect (not
 * AJAX) on purpose -- couriers use this from mobile browsers in the field,
 * and a full-page submit degrades far better than JS on a flaky connection.
 */
class PKST_Public {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'pkst_panel', array( $this, 'shortcode_panel' ) );
		add_shortcode( 'pkst_track', array( $this, 'shortcode_track' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
		add_action( 'template_redirect', array( $this, 'maybe_handle_login' ) );

		add_action( 'admin_post_pkst_courier_update_status', array( $this, 'handle_courier_update_status' ) );
	}

	public function maybe_enqueue() {
		if ( ! is_singular() ) {
			return;
		}
		global $post;
		if ( ! $post || ! has_shortcode( $post->post_content, 'pkst_panel' ) && ! has_shortcode( $post->post_content, 'pkst_track' ) ) {
			return;
		}

		wp_enqueue_style( 'pkst-public', PKST_PLUGIN_URL . 'public/css/public.css', array(), PKST_VERSION );
		wp_enqueue_script( 'pkst-public', PKST_PLUGIN_URL . 'public/js/public.js', array(), PKST_VERSION, true );
	}

	/* ---------------------------------------------------------------- */
	/* [pkst_panel]                                                      */
	/* ---------------------------------------------------------------- */

	public function shortcode_panel( $atts ) {
		if ( ! is_user_logged_in() ) {
			return $this->render( 'panel-login', array( 'error' => ! empty( $_GET['pkst_login_error'] ) ) );
		}

		if ( PKST_Roles::current_user_is_courier() ) {
			return $this->render_courier_panel();
		}

		if ( PKST_Roles::current_user_is_customer() ) {
			$shipments = PKST_Shipment::get_for_customer( get_current_user_id() );
			return $this->render( 'panel-customer', array( 'shipments' => $shipments ) );
		}

		if ( current_user_can( 'pkst_manage_shipments' ) ) {
			return '<p class="pkst-front-notice">' . sprintf(
				/* translators: %s: link to wp-admin shipments page */
				wp_kses_post( __( 'شما مدیر سامانه هستید؛ برای مدیریت مرسولات به %s مراجعه کنید.', 'peykherfei-shipment-tracking' ) ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=pkst-dashboard' ) ) . '">' . esc_html__( 'پنل مدیریت', 'peykherfei-shipment-tracking' ) . '</a>'
			) . '</p>';
		}

		return '<p class="pkst-front-notice">' . esc_html__( 'حساب شما به این پنل دسترسی ندارد.', 'peykherfei-shipment-tracking' ) . '</p>';
	}

	private function render_courier_panel() {
		$user_id = get_current_user_id();
		$action  = isset( $_GET['pkst_action'] ) ? sanitize_key( $_GET['pkst_action'] ) : 'list';

		if ( 'update' === $action && isset( $_GET['shipment_id'] ) ) {
			$shipment = PKST_Shipment::get( absint( $_GET['shipment_id'] ) );
			if ( $shipment && (int) $shipment['courier_id'] === $user_id ) {
				return $this->render( 'panel-courier-update', array( 'shipment' => $shipment ) );
			}
		}

		$all    = PKST_Shipment::get_for_courier( $user_id );
		$open   = array_values( array_filter( $all, function ( $s ) { return PKST_Status::is_open( $s['status'] ); } ) );
		$closed = array_slice( array_values( array_filter( $all, function ( $s ) { return ! PKST_Status::is_open( $s['status'] ); } ) ), 0, 20 );

		return $this->render(
			'panel-courier',
			array(
				'open_shipments'   => $open,
				'closed_shipments' => $closed,
				'saved'            => ! empty( $_GET['pkst_notice'] ),
			)
		);
	}

	/* ---------------------------------------------------------------- */
	/* [pkst_track]                                                      */
	/* ---------------------------------------------------------------- */

	public function shortcode_track( $atts ) {
		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		$shipment   = null;
		$status_log = array();
		$not_found  = false;

		if ( '' !== $code ) {
			$shipment = PKST_Shipment::get_by_tracking_code( $code );
			if ( $shipment ) {
				$status_log = PKST_Shipment::get_status_log( $shipment['id'] );
			} else {
				$not_found = true;
			}
		}

		return $this->render(
			'track-form',
			array(
				'code'       => $code,
				'shipment'   => $shipment,
				'status_log' => $status_log,
				'not_found'  => $not_found,
			)
		);
	}

	/* ---------------------------------------------------------------- */
	/* Handlers                                                          */
	/* ---------------------------------------------------------------- */

	public function maybe_handle_login() {
		if ( empty( $_POST['pkst_login_action'] ) ) {
			return;
		}

		$redirect = wp_get_referer() ? wp_get_referer() : home_url();

		if ( ! isset( $_POST['pkst_login_nonce'] ) || ! wp_verify_nonce( $_POST['pkst_login_nonce'], 'pkst_login' ) ) {
			wp_safe_redirect( add_query_arg( 'pkst_login_error', '1', $redirect ) );
			exit;
		}

		$creds = array(
			'user_login'    => isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( $_POST['log'] ) ) : '',
			'user_password' => isset( $_POST['pwd'] ) ? wp_unslash( $_POST['pwd'] ) : '',
			'remember'      => true,
		);

		$user = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $user ) ) {
			wp_safe_redirect( add_query_arg( 'pkst_login_error', '1', $redirect ) );
			exit;
		}

		wp_safe_redirect( remove_query_arg( 'pkst_login_error', $redirect ) );
		exit;
	}

	public function handle_courier_update_status() {
		$shipment_id = isset( $_POST['shipment_id'] ) ? absint( $_POST['shipment_id'] ) : 0;
		check_admin_referer( 'pkst_courier_update_' . $shipment_id );

		if ( ! PKST_Roles::current_user_is_courier() ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		$shipment = PKST_Shipment::get( $shipment_id );
		if ( ! $shipment || (int) $shipment['courier_id'] !== get_current_user_id() ) {
			wp_die( esc_html__( 'این مرسوله به شما تخصیص داده نشده است.', 'peykherfei-shipment-tracking' ) );
		}

		$status = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';
		$args   = array( 'note' => isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '' );

		if ( ! empty( $_POST['lat'] ) && ! empty( $_POST['lng'] ) ) {
			$args['lat'] = $_POST['lat'];
			$args['lng'] = $_POST['lng'];
		}

		if ( in_array( $status, array( PKST_Status::DELIVERED, PKST_Status::FAILED ), true ) ) {
			$args['pod_receiver_name']  = isset( $_POST['pod_receiver_name'] ) ? wp_unslash( $_POST['pod_receiver_name'] ) : '';
			$args['pod_failure_reason'] = isset( $_POST['pod_failure_reason'] ) ? wp_unslash( $_POST['pod_failure_reason'] ) : '';

			if ( ! empty( $_POST['pod_signature_data'] ) ) {
				$saved = PKST_POD::save_signature( $shipment_id, wp_unslash( $_POST['pod_signature_data'] ) );
				if ( ! is_wp_error( $saved ) ) {
					$args['pod_signature_path'] = $saved;
				}
			}
			if ( ! empty( $_FILES['pod_photo']['tmp_name'] ) ) {
				$saved = PKST_POD::save_photo( $shipment_id, $_FILES['pod_photo'] );
				if ( ! is_wp_error( $saved ) ) {
					$args['pod_photo_path'] = $saved;
				}
			}
		}

		PKST_Shipment::update_status( $shipment_id, $status, $args );

		$redirect = wp_get_referer() ? remove_query_arg( array( 'pkst_action', 'shipment_id', 'pkst_notice' ), wp_get_referer() ) : home_url();
		wp_safe_redirect( add_query_arg( 'pkst_notice', 'saved', $redirect ) );
		exit;
	}

	/* ---------------------------------------------------------------- */

	private function render( $view, array $vars = array() ) {
		extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		ob_start();
		include PKST_PLUGIN_DIR . 'public/views/' . $view . '.php';
		return ob_get_clean();
	}
}
