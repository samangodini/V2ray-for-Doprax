<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gateway implementations follow each provider's publicly documented REST
 * shape at the time this plugin was built. Iranian SMS providers sometimes
 * tweak panel/API details per account tier, so use "تست اتصال" (test
 * connection) on the settings page after entering credentials, and fix the
 * relevant class below if a provider's response indicates otherwise — the
 * PKST_SMS_Gateway_Interface boundary keeps that a one-file change.
 */

class PKST_SMS_Gateway_Kavenegar implements PKST_SMS_Gateway_Interface {

	public function send( $to, $message ) {
		$api_key = PKST_Settings::get( 'kavenegar_api_key' );
		if ( ! $api_key ) {
			return array(
				'success'  => false,
				'response' => __( 'کلید API کاوه‌نگار تنظیم نشده است.', 'peykherfei-shipment-tracking' ),
			);
		}

		$sender = PKST_Settings::get( 'sms_sender_number' );
		$url    = sprintf( 'https://api.kavenegar.com/v1/%s/sms/send.json', rawurlencode( $api_key ) );

		$body = array(
			'receptor' => $to,
			'message'  => $message,
		);
		if ( $sender ) {
			$body['sender'] = $sender;
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'body'    => $body,
			)
		);

		return PKST_SMS_Gateway_Helper::parse_response( $response, function ( $data ) {
			return isset( $data['return']['status'] ) && 200 === (int) $data['return']['status'];
		} );
	}
}

class PKST_SMS_Gateway_Melipayamak implements PKST_SMS_Gateway_Interface {

	public function send( $to, $message ) {
		$api_key = PKST_Settings::get( 'melipayamak_api_key' );
		if ( ! $api_key ) {
			return array(
				'success'  => false,
				'response' => __( 'کلید API ملی‌پیامک تنظیم نشده است.', 'peykherfei-shipment-tracking' ),
			);
		}

		$sender = PKST_Settings::get( 'sms_sender_number' );
		$url    = sprintf( 'https://console.melipayamak.com/api/send/simple/%s', rawurlencode( $api_key ) );

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'from' => $sender,
						'to'   => $to,
						'text' => $message,
					)
				),
			)
		);

		return PKST_SMS_Gateway_Helper::parse_response( $response, function ( $data, $code ) {
			return $code >= 200 && $code < 300 && empty( $data['error'] );
		} );
	}
}

class PKST_SMS_Gateway_IPPanel implements PKST_SMS_Gateway_Interface {

	public function send( $to, $message ) {
		$api_key = PKST_Settings::get( 'ippanel_api_key' );
		if ( ! $api_key ) {
			return array(
				'success'  => false,
				'response' => __( 'کلید API آی‌پی‌پنل تنظیم نشده است.', 'peykherfei-shipment-tracking' ),
			);
		}

		$sender = PKST_Settings::get( 'sms_sender_number' );

		$response = wp_remote_post(
			'https://edge.ippanel.com/v1/api/send',
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'AccessKey ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'sending_type' => 'webservice',
						'from_number'  => $sender,
						'message'      => $message,
						'params'       => array( 'recipients' => array( $to ) ),
					)
				),
			)
		);

		return PKST_SMS_Gateway_Helper::parse_response( $response, function ( $data, $code ) {
			return $code >= 200 && $code < 300;
		} );
	}
}

class PKST_SMS_Gateway_SMSir implements PKST_SMS_Gateway_Interface {

	public function send( $to, $message ) {
		$api_key = PKST_Settings::get( 'smsir_api_key' );
		$line    = PKST_Settings::get( 'smsir_line_number' );

		if ( ! $api_key || ! $line ) {
			return array(
				'success'  => false,
				'response' => __( 'کلید API یا شماره خط sms.ir تنظیم نشده است.', 'peykherfei-shipment-tracking' ),
			);
		}

		$response = wp_remote_post(
			'https://api.sms.ir/v1/send/bulk',
			array(
				'timeout' => 15,
				'headers' => array(
					'X-API-KEY'    => $api_key,
					'ACCEPT'       => 'application/json',
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'lineNumber'    => $line,
						'messageText'   => $message,
						'mobiles'       => array( $to ),
						'sendDateTime'  => null,
					)
				),
			)
		);

		return PKST_SMS_Gateway_Helper::parse_response( $response, function ( $data ) {
			return isset( $data['status'] ) && 1 === (int) $data['status'];
		} );
	}
}

/**
 * Lets an admin wire up any provider not listed above (or fix a shape
 * mismatch) without touching code: URL/method/headers/body are all
 * templated with {phone} and {message} placeholders.
 */
class PKST_SMS_Gateway_Custom implements PKST_SMS_Gateway_Interface {

	public function send( $to, $message ) {
		$url = PKST_Settings::get( 'custom_gateway_url' );
		if ( ! $url ) {
			return array(
				'success'  => false,
				'response' => __( 'آدرس وب‌سرویس سفارشی تنظیم نشده است.', 'peykherfei-shipment-tracking' ),
			);
		}

		$method       = strtoupper( PKST_Settings::get( 'custom_gateway_method', 'POST' ) );
		$headers_raw  = (string) PKST_Settings::get( 'custom_gateway_headers', '' );
		$body_template = (string) PKST_Settings::get( 'custom_gateway_body', '' );

		// wp_json_encode() + trim the outer quotes, rather than addslashes(): the
		// body template is JSON, and addslashes() doesn't escape raw newlines,
		// which would produce invalid JSON for any multi-line SMS template.
		$json_safe_message = substr( wp_json_encode( (string) $message ), 1, -1 );

		$url  = str_replace( array( '{phone}', '{message}' ), array( rawurlencode( $to ), rawurlencode( $message ) ), $url );
		$body = str_replace( array( '{phone}', '{message}' ), array( $to, $json_safe_message ), $body_template );

		$headers = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $headers_raw ) as $line ) {
			if ( false !== strpos( $line, ':' ) ) {
				list( $name, $value ) = array_map( 'trim', explode( ':', $line, 2 ) );
				if ( $name ) {
					$headers[ $name ] = $value;
				}
			}
		}

		$args = array(
			'timeout' => 15,
			'method'  => 'GET' === $method ? 'GET' : 'POST',
			'headers' => $headers,
		);
		if ( 'GET' !== $method && '' !== $body ) {
			$args['body'] = $body;
		}

		$response = 'GET' === $method ? wp_remote_get( $url, $args ) : wp_remote_post( $url, $args );

		return PKST_SMS_Gateway_Helper::parse_response( $response, function ( $data, $code ) {
			return $code >= 200 && $code < 300;
		} );
	}
}

class PKST_SMS_Gateway_Helper {

	public static function parse_response( $response, callable $is_success ) {
		if ( is_wp_error( $response ) ) {
			return array(
				'success'  => false,
				'response' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );
		$data = is_array( $data ) ? $data : array();

		return array(
			'success'  => (bool) call_user_func( $is_success, $data, $code ),
			'response' => $raw ? $raw : sprintf( 'HTTP %d', $code ),
		);
	}
}

class PKST_SMS_Gateway_Factory {

	public static function get( $gateway_key = null ) {
		$gateway_key = $gateway_key ?: PKST_Settings::get( 'sms_gateway', 'kavenegar' );

		switch ( $gateway_key ) {
			case 'melipayamak':
				return new PKST_SMS_Gateway_Melipayamak();
			case 'ippanel':
				return new PKST_SMS_Gateway_IPPanel();
			case 'smsir':
				return new PKST_SMS_Gateway_SMSir();
			case 'custom':
				return new PKST_SMS_Gateway_Custom();
			case 'kavenegar':
			default:
				return new PKST_SMS_Gateway_Kavenegar();
		}
	}

	public static function options() {
		return array(
			'kavenegar'   => __( 'کاوه‌نگار (Kavenegar)', 'peykherfei-shipment-tracking' ),
			'melipayamak' => __( 'ملی‌پیامک (Melipayamak)', 'peykherfei-shipment-tracking' ),
			'ippanel'     => __( 'آی‌پی‌پنل (IPPanel)', 'peykherfei-shipment-tracking' ),
			'smsir'       => __( 'sms.ir', 'peykherfei-shipment-tracking' ),
			'custom'      => __( 'وب‌سرویس سفارشی (سایر پنل‌ها)', 'peykherfei-shipment-tracking' ),
		);
	}
}
