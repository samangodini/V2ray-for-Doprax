<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Listens for pkst_status_changed and fires the matching SMS template, if
 * enabled, through whichever gateway is configured. Decoupled from
 * PKST_Shipment on purpose: anything that changes a shipment's status
 * (admin, courier panel, REST API) goes through the same action, so
 * notifications stay consistent regardless of the trigger.
 */
class PKST_SMS_Manager {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'pkst_status_changed', array( $this, 'maybe_send' ), 10, 4 );
	}

	public function maybe_send( $shipment_id, $old_status, $new_status, $context ) {
		if ( '1' !== (string) PKST_Settings::get( 'sms_enabled', '1' ) ) {
			return;
		}

		if ( $old_status === $new_status && empty( $context['is_new'] ) ) {
			return;
		}

		$template = PKST_Settings::get_template( $new_status );
		if ( empty( $template['enabled'] ) || '1' !== (string) $template['enabled'] || '' === trim( (string) $template['text'] ) ) {
			return;
		}

		$shipment = PKST_Shipment::get( $shipment_id );
		if ( ! $shipment || empty( $shipment['recipient_phone'] ) ) {
			return;
		}

		$message = self::render_template( $template['text'], $shipment );
		$this->dispatch( $shipment_id, $shipment['recipient_phone'], $new_status, $message );
	}

	private function dispatch( $shipment_id, $phone, $status_trigger, $message ) {
		$gateway_key = PKST_Settings::get( 'sms_gateway', 'kavenegar' );
		$gateway     = PKST_SMS_Gateway_Factory::get( $gateway_key );
		$result      = $gateway->send( $phone, $message );

		self::log( $shipment_id, $phone, $status_trigger, $message, $gateway_key, $result );

		return $result;
	}

	public static function render_template( $text, array $shipment ) {
		$track_page = PKST_Settings::get( 'track_page_url' );
		$tracking_url = $track_page ? add_query_arg( 'code', $shipment['tracking_code'], $track_page ) : '';

		$placeholders = array(
			'{tracking_code}'  => $shipment['tracking_code'],
			'{recipient_name}' => $shipment['recipient_name'],
			'{destination}'    => $shipment['destination'],
			'{company_name}'   => PKST_Settings::get( 'company_name', 'پیک خرفه' ),
			'{failure_reason}' => ! empty( $shipment['pod_failure_reason'] ) ? $shipment['pod_failure_reason'] : __( 'نامشخص', 'peykherfei-shipment-tracking' ),
			'{status_text}'    => PKST_Status::label( $shipment['status'] ),
			'{tracking_url}'   => $tracking_url,
		);

		return strtr( $text, $placeholders );
	}

	public static function log( $shipment_id, $phone, $status_trigger, $message, $gateway_key, array $result ) {
		global $wpdb;

		$wpdb->insert(
			PKST_DB::sms_log_table(),
			array(
				'shipment_id'    => absint( $shipment_id ),
				'phone'          => $phone,
				'status_trigger' => $status_trigger,
				'message'        => $message,
				'gateway'        => $gateway_key,
				'result'         => ! empty( $result['success'] ) ? 'sent' : 'failed',
				'response'       => isset( $result['response'] ) ? wp_strip_all_tags( $result['response'] ) : '',
				'created_at'     => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	public static function get_log_for_shipment( $shipment_id ) {
		global $wpdb;
		$table = PKST_DB::sms_log_table();
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE shipment_id = %d ORDER BY created_at DESC", absint( $shipment_id ) ),
			ARRAY_A
		);
	}

	/**
	 * Used by the settings page "test connection" button so a misconfigured
	 * gateway/API key is caught immediately instead of failing silently on
	 * the first real shipment.
	 */
	public static function send_test( $phone, $gateway_key = null ) {
		$gateway = PKST_SMS_Gateway_Factory::get( $gateway_key );
		$company = PKST_Settings::get( 'company_name', 'پیک خرفه' );
		$message = sprintf( 'پیامک آزمایشی از سامانه %s با موفقیت ارسال شد.', $company );

		return $gateway->send( PKST_Shipment::sanitize_phone( $phone ), $message );
	}
}
