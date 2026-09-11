<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Foundation REST API for connecting external/internal systems (spec
 * section 7). Public tracking lookup needs no auth; everything that reads
 * or writes shipment data needs either the plugin's own API key header or
 * a logged-in user capable of managing shipments (covers WP Application
 * Passwords for server-to-server use automatically).
 */
class PKST_REST_API {

	const NAMESPACE_ = 'pkst/v1';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_,
			'/track/(?P<code>[A-Za-z0-9\-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'track' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/shipments',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_shipment' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/shipments/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipment' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/shipments/(?P<id>\d+)/status',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_status' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);
	}

	public function check_auth( WP_REST_Request $request ) {
		if ( current_user_can( 'pkst_manage_shipments' ) ) {
			return true;
		}

		$provided = $request->get_header( 'x-pkst-api-key' );
		if ( $provided && hash_equals( PKST_Settings::api_key(), $provided ) ) {
			return true;
		}

		return new WP_Error( 'pkst_rest_forbidden', __( 'کلید API نامعتبر است.', 'peykherfei-shipment-tracking' ), array( 'status' => 401 ) );
	}

	public function track( WP_REST_Request $request ) {
		$shipment = PKST_Shipment::get_by_tracking_code( $request['code'] );
		if ( ! $shipment ) {
			return new WP_Error( 'pkst_not_found', __( 'مرسوله‌ای با این کد یافت نشد.', 'peykherfei-shipment-tracking' ), array( 'status' => 404 ) );
		}

		$log = PKST_Shipment::get_status_log( $shipment['id'] );

		$timeline = array_map(
			function ( $entry ) {
				return array(
					'status'     => $entry['status'],
					'label'      => PKST_Status::label( $entry['status'] ),
					'created_at' => $entry['created_at'],
				);
			},
			$log
		);

		return rest_ensure_response(
			array(
				'tracking_code' => $shipment['tracking_code'],
				'status'        => $shipment['status'],
				'status_label'  => PKST_Status::label( $shipment['status'] ),
				'origin'        => $shipment['origin'],
				'destination'   => $shipment['destination'],
				'created_at'    => $shipment['created_at'],
				'delivered_at'  => $shipment['delivered_at'],
				'timeline'      => $timeline,
			)
		);
	}

	public function create_shipment( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_body_params();
		}

		$id = PKST_Shipment::create( $params );
		if ( is_wp_error( $id ) ) {
			$id->add_data( array( 'status' => 400 ) );
			return $id;
		}

		return rest_ensure_response( PKST_Shipment::get( $id ) );
	}

	public function get_shipment( WP_REST_Request $request ) {
		$shipment = PKST_Shipment::get( (int) $request['id'] );
		if ( ! $shipment ) {
			return new WP_Error( 'pkst_not_found', __( 'مرسوله یافت نشد.', 'peykherfei-shipment-tracking' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $shipment );
	}

	public function update_status( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_body_params();
		}

		if ( empty( $params['status'] ) ) {
			return new WP_Error( 'pkst_missing_status', __( 'وضعیت الزامی است.', 'peykherfei-shipment-tracking' ), array( 'status' => 400 ) );
		}

		$result = PKST_Shipment::update_status( (int) $request['id'], sanitize_key( $params['status'] ), $params );
		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 400 ) );
			return $result;
		}

		return rest_ensure_response( PKST_Shipment::get( (int) $request['id'] ) );
	}
}
