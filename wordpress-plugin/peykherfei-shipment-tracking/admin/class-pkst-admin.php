<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PKST_Admin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'admin_post_pkst_save_shipment', array( $this, 'handle_save_shipment' ) );
		add_action( 'admin_post_pkst_delete_shipment', array( $this, 'handle_delete_shipment' ) );
		add_action( 'admin_post_pkst_update_status', array( $this, 'handle_update_status' ) );
		add_action( 'admin_post_pkst_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_pkst_regenerate_api_key', array( $this, 'handle_regenerate_api_key' ) );
		add_action( 'admin_post_pkst_create_user', array( $this, 'handle_create_user' ) );
		add_action( 'admin_post_pkst_toggle_user_active', array( $this, 'handle_toggle_user_active' ) );
		add_action( 'admin_post_pkst_export_csv', array( 'PKST_Export', 'handle_csv_export' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'پنل مرسولات', 'peykherfei-shipment-tracking' ),
			__( 'مرسولات', 'peykherfei-shipment-tracking' ),
			'pkst_manage_shipments',
			'pkst-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-car',
			26
		);

		add_submenu_page( 'pkst-dashboard', __( 'داشبورد', 'peykherfei-shipment-tracking' ), __( 'داشبورد', 'peykherfei-shipment-tracking' ), 'pkst_manage_shipments', 'pkst-dashboard', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'pkst-dashboard', __( 'همه مرسولات', 'peykherfei-shipment-tracking' ), __( 'همه مرسولات', 'peykherfei-shipment-tracking' ), 'pkst_manage_shipments', 'pkst-shipments', array( $this, 'render_shipments' ) );
		add_submenu_page( 'pkst-dashboard', __( 'افزودن مرسوله', 'peykherfei-shipment-tracking' ), __( 'افزودن مرسوله', 'peykherfei-shipment-tracking' ), 'pkst_manage_shipments', 'pkst-shipment-add', array( $this, 'render_shipment_form' ) );
		add_submenu_page( 'pkst-dashboard', __( 'کاربران و پیک‌ها', 'peykherfei-shipment-tracking' ), __( 'کاربران و پیک‌ها', 'peykherfei-shipment-tracking' ), 'pkst_manage_users', 'pkst-users', array( $this, 'render_users' ) );
		add_submenu_page( 'pkst-dashboard', __( 'تنظیمات', 'peykherfei-shipment-tracking' ), __( 'تنظیمات', 'peykherfei-shipment-tracking' ), 'pkst_manage_settings', 'pkst-settings', array( $this, 'render_settings' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( ! isset( $_GET['page'] ) || 0 !== strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'pkst-' ) ) {
			return;
		}

		wp_enqueue_style( 'pkst-admin', PKST_PLUGIN_URL . 'admin/css/admin.css', array(), PKST_VERSION );
		wp_enqueue_script( 'pkst-admin', PKST_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery' ), PKST_VERSION, true );
		wp_localize_script(
			'pkst-admin',
			'PKST_ADMIN',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pkst_admin_nonce' ),
				'i18n'    => array(
					'clearSignature' => __( 'پاک کردن امضا', 'peykherfei-shipment-tracking' ),
				),
			)
		);
	}

	/* ---------------------------------------------------------------- */
	/* Renders                                                          */
	/* ---------------------------------------------------------------- */

	public function render_dashboard() {
		if ( ! current_user_can( 'pkst_manage_shipments' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}
		$counts       = PKST_Shipment::counts();
		$daily        = PKST_Reports::daily_counts( 14 );
		$avg_minutes  = PKST_Reports::avg_delivery_minutes();
		$courier_perf = PKST_Shipment::courier_performance();
		include PKST_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public function render_shipments() {
		if ( ! current_user_can( 'pkst_manage_shipments' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		if ( isset( $_GET['action'] ) && 'view' === $_GET['action'] && isset( $_GET['id'] ) ) {
			$shipment = PKST_Shipment::get( absint( $_GET['id'] ) );
			if ( ! $shipment ) {
				echo '<div class="wrap"><p>' . esc_html__( 'مرسوله یافت نشد.', 'peykherfei-shipment-tracking' ) . '</p></div>';
				return;
			}
			$status_log = PKST_Shipment::get_status_log( $shipment['id'] );
			$courier_location = $shipment['courier_id'] ? PKST_Geolocation::get_courier_location( $shipment['courier_id'] ) : null;
			include PKST_PLUGIN_DIR . 'admin/views/shipment-view.php';
			return;
		}

		$list_table = new PKST_Shipments_List_Table();
		$list_table->prepare_items();
		include PKST_PLUGIN_DIR . 'admin/views/shipments.php';
	}

	public function render_shipment_form() {
		if ( ! current_user_can( 'pkst_manage_shipments' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		$shipment = null;
		if ( isset( $_GET['id'] ) ) {
			$shipment = PKST_Shipment::get( absint( $_GET['id'] ) );
		}

		$couriers  = PKST_User_Manager::list_by_role( PKST_Roles::COURIER );
		$customers = PKST_User_Manager::list_by_role( PKST_Roles::CUSTOMER );

		include PKST_PLUGIN_DIR . 'admin/views/shipment-form.php';
	}

	public function render_users() {
		if ( ! current_user_can( 'pkst_manage_users' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}
		$couriers  = PKST_User_Manager::list_by_role( PKST_Roles::COURIER );
		$customers = PKST_User_Manager::list_by_role( PKST_Roles::CUSTOMER );

		$transient_key      = 'pkst_new_user_' . get_current_user_id();
		$new_user_credentials = get_transient( $transient_key );
		if ( $new_user_credentials ) {
			delete_transient( $transient_key );
		}

		include PKST_PLUGIN_DIR . 'admin/views/users.php';
	}

	public function render_settings() {
		if ( ! current_user_can( 'pkst_manage_settings' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}
		$settings = PKST_Settings::get_all();
		$api_key  = PKST_Settings::api_key();
		include PKST_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/* ---------------------------------------------------------------- */
	/* Handlers                                                         */
	/* ---------------------------------------------------------------- */

	public function handle_save_shipment() {
		check_admin_referer( 'pkst_save_shipment' );
		if ( ! current_user_can( 'pkst_manage_shipments' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$data = array(
			'sender_name'          => wp_unslash( $_POST['sender_name'] ?? '' ),
			'sender_phone'         => wp_unslash( $_POST['sender_phone'] ?? '' ),
			'recipient_name'       => wp_unslash( $_POST['recipient_name'] ?? '' ),
			'recipient_phone'      => wp_unslash( $_POST['recipient_phone'] ?? '' ),
			'origin'               => wp_unslash( $_POST['origin'] ?? '' ),
			'destination'          => wp_unslash( $_POST['destination'] ?? '' ),
			'description'          => wp_unslash( $_POST['description'] ?? '' ),
			'courier_id'           => absint( $_POST['courier_id'] ?? 0 ),
			'customer_user_id'     => absint( $_POST['customer_user_id'] ?? 0 ),
			'handed_to_courier_at' => wp_unslash( $_POST['handed_to_courier_at'] ?? '' ),
		);

		if ( $id ) {
			$result = PKST_Shipment::update( $id, $data );
			$redirect_id = $id;
		} else {
			$result      = PKST_Shipment::create( $data );
			$redirect_id = is_wp_error( $result ) ? 0 : $result;
		}

		if ( is_wp_error( $result ) ) {
			$url = add_query_arg(
				array(
					'page'       => 'pkst-shipment-add',
					'id'         => $id ?: '',
					'pkst_notice' => 'error',
					'pkst_msg'    => rawurlencode( $result->get_error_message() ),
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $url );
			exit;
		}

		$url = add_query_arg(
			array(
				'page'        => 'pkst-shipments',
				'action'      => 'view',
				'id'          => $redirect_id,
				'pkst_notice' => 'saved',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	public function handle_delete_shipment() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'pkst_delete_shipment_' . $id );
		if ( ! current_user_can( 'pkst_manage_shipments' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		PKST_Shipment::delete( $id );

		wp_safe_redirect( add_query_arg( array( 'page' => 'pkst-shipments', 'pkst_notice' => 'deleted' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_update_status() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		check_admin_referer( 'pkst_update_status_' . $id );
		if ( ! current_user_can( 'pkst_manage_shipments' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		$status = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';
		$args   = array(
			'note' => wp_unslash( $_POST['note'] ?? '' ),
		);

		if ( in_array( $status, array( PKST_Status::DELIVERED, PKST_Status::FAILED ), true ) ) {
			$args['pod_receiver_name']   = wp_unslash( $_POST['pod_receiver_name'] ?? '' );
			$args['pod_failure_reason']  = wp_unslash( $_POST['pod_failure_reason'] ?? '' );

			if ( ! empty( $_POST['pod_signature_data'] ) ) {
				$saved = PKST_POD::save_signature( $id, wp_unslash( $_POST['pod_signature_data'] ) );
				if ( ! is_wp_error( $saved ) ) {
					$args['pod_signature_path'] = $saved;
				}
			}
			if ( ! empty( $_FILES['pod_photo']['tmp_name'] ) ) {
				$saved = PKST_POD::save_photo( $id, $_FILES['pod_photo'] );
				if ( ! is_wp_error( $saved ) ) {
					$args['pod_photo_path'] = $saved;
				}
			}
		}

		$result = PKST_Shipment::update_status( $id, $status, $args );

		$notice = is_wp_error( $result ) ? 'error' : 'status_updated';
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'pkst-shipments',
					'action'      => 'view',
					'id'          => $id,
					'pkst_notice' => $notice,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public function handle_save_settings() {
		check_admin_referer( 'pkst_save_settings' );
		if ( ! current_user_can( 'pkst_manage_settings' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		$fields = array_keys( PKST_Settings::defaults() );
		$values = array();
		foreach ( $fields as $field ) {
			$values[ $field ] = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
		}
		$values['delete_data_on_uninstall'] = isset( $_POST['delete_data_on_uninstall'] ) ? '1' : '0';

		PKST_Settings::update_many( $values );

		wp_safe_redirect( add_query_arg( array( 'page' => 'pkst-settings', 'pkst_notice' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_regenerate_api_key() {
		check_admin_referer( 'pkst_regenerate_api_key' );
		if ( ! current_user_can( 'pkst_manage_settings' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		update_option( 'pkst_api_key', wp_generate_password( 32, false ) );

		wp_safe_redirect( add_query_arg( array( 'page' => 'pkst-settings', 'tab' => 'api', 'pkst_notice' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_create_user() {
		check_admin_referer( 'pkst_create_user' );
		if ( ! current_user_can( 'pkst_manage_users' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		$result = PKST_User_Manager::create_user(
			array(
				'role'     => wp_unslash( $_POST['role'] ?? '' ),
				'name'     => wp_unslash( $_POST['name'] ?? '' ),
				'email'    => wp_unslash( $_POST['email'] ?? '' ),
				'phone'    => wp_unslash( $_POST['phone'] ?? '' ),
				'vehicle'  => wp_unslash( $_POST['vehicle'] ?? '' ),
				'password' => wp_unslash( $_POST['password'] ?? '' ),
				'notify'   => ! empty( $_POST['notify'] ),
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'        => 'pkst-users',
						'pkst_notice' => 'error',
						'pkst_msg'    => rawurlencode( $result->get_error_message() ),
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		/**
		 * Stashed server-side (not in the redirect URL, which would leak
		 * the plaintext password into server/browser logs) so render_users()
		 * can show it exactly once on the very next page load.
		 */
		set_transient(
			'pkst_new_user_' . get_current_user_id(),
			array(
				'username' => $result['username'],
				'password' => $result['password'],
			),
			MINUTE_IN_SECONDS
		);

		wp_safe_redirect( add_query_arg( array( 'page' => 'pkst-users', 'pkst_notice' => 'user_created' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_toggle_user_active() {
		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		check_admin_referer( 'pkst_toggle_active_' . $user_id );
		if ( ! current_user_can( 'pkst_manage_users' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		PKST_User_Manager::update_status_active( $user_id, ! PKST_User_Manager::is_active( $user_id ) );

		wp_safe_redirect( add_query_arg( array( 'page' => 'pkst-users', 'pkst_notice' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function notice_from_query() {
		if ( empty( $_GET['pkst_notice'] ) ) {
			return;
		}

		$notice = sanitize_key( $_GET['pkst_notice'] );
		$msg    = isset( $_GET['pkst_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['pkst_msg'] ) ) : '';

		$map = array(
			'saved'          => array( 'success', __( 'با موفقیت ذخیره شد.', 'peykherfei-shipment-tracking' ) ),
			'deleted'        => array( 'success', __( 'مرسوله حذف شد.', 'peykherfei-shipment-tracking' ) ),
			'bulk_done'      => array( 'success', __( 'عملیات گروهی انجام شد.', 'peykherfei-shipment-tracking' ) ),
			'status_updated' => array( 'success', __( 'وضعیت مرسوله به‌روزرسانی شد.', 'peykherfei-shipment-tracking' ) ),
			'user_created'   => array( 'success', __( 'کاربر با موفقیت ایجاد شد.', 'peykherfei-shipment-tracking' ) ),
			'error'          => array( 'error', $msg ? $msg : __( 'خطایی رخ داد.', 'peykherfei-shipment-tracking' ) ),
		);

		if ( ! isset( $map[ $notice ] ) ) {
			return;
		}

		list( $type, $text ) = $map[ $notice ];
		printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $type ), esc_html( $text ) );
	}
}
