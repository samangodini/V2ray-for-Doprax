<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wraps two options: pkst_settings (general + SMS gateway config) and
 * pkst_sms_templates (per-status message templates). Kept as plain options
 * rather than a DB table since this is small, rarely-written configuration.
 */
class PKST_Settings {

	const OPTION_KEY    = 'pkst_settings';
	const TEMPLATES_KEY = 'pkst_sms_templates';

	public static function defaults() {
		return array(
			'company_name'        => 'پیک خرفه',
			'tracking_code_prefix' => 'PK',
			'sms_enabled'         => '1',
			'sms_gateway'         => 'kavenegar',
			'sms_sender_number'   => '',
			'kavenegar_api_key'   => '',
			'melipayamak_api_key' => '',
			'ippanel_api_key'     => '',
			'smsir_api_key'       => '',
			'smsir_line_number'   => '',
			'custom_gateway_url'     => '',
			'custom_gateway_method'  => 'POST',
			'custom_gateway_headers' => '',
			'custom_gateway_body'    => '{"to":"{phone}","text":"{message}"}',
			'overdue_hours'       => '48',
			'track_page_url'      => '',
			'delete_data_on_uninstall' => '0',
		);
	}

	public static function default_templates() {
		$company = '{company_name}';

		return array(
			PKST_Status::REGISTERED => array(
				'enabled' => '1',
				'text'    => "مرسوله شما با کد رهگیری {tracking_code} در سامانه {$company} ثبت و به پیک تحویل شد.",
			),
			PKST_Status::PICKED_UP => array(
				'enabled' => '0',
				'text'    => "مرسوله {tracking_code} توسط پیک {$company} دریافت شد.",
			),
			PKST_Status::IN_TRANSIT => array(
				'enabled' => '1',
				'text'    => "مرسوله شما با کد رهگیری {tracking_code} در حال ارسال به مقصد است. {company_name}",
			),
			PKST_Status::ARRIVED => array(
				'enabled' => '1',
				'text'    => "مرسوله {tracking_code} به مقصد رسید و به‌زودی تحویل داده می‌شود. {company_name}",
			),
			PKST_Status::DELIVERED => array(
				'enabled' => '1',
				'text'    => "مرسوله {tracking_code} با موفقیت به گیرنده تحویل داده شد. با تشکر از {company_name}",
			),
			PKST_Status::FAILED => array(
				'enabled' => '1',
				'text'    => "متاسفانه تحویل مرسوله {tracking_code} امکان‌پذیر نبود ({failure_reason}). جهت پیگیری با {company_name} تماس بگیرید.",
			),
		);
	}

	public static function set_defaults() {
		if ( false === get_option( self::OPTION_KEY ) ) {
			add_option( self::OPTION_KEY, self::defaults() );
		}
		if ( false === get_option( self::TEMPLATES_KEY ) ) {
			add_option( self::TEMPLATES_KEY, self::default_templates() );
		}
	}

	public static function get_all() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	public static function get( $key, $default = '' ) {
		$all = self::get_all();
		return isset( $all[ $key ] ) && '' !== $all[ $key ] ? $all[ $key ] : $default;
	}

	public static function update_many( array $values ) {
		$all = self::get_all();
		foreach ( $values as $key => $value ) {
			$all[ $key ] = $value;
		}
		update_option( self::OPTION_KEY, $all );
	}

	public static function get_templates() {
		$stored   = get_option( self::TEMPLATES_KEY, array() );
		$defaults = self::default_templates();
		foreach ( $defaults as $status => $default_row ) {
			if ( ! isset( $stored[ $status ] ) ) {
				$stored[ $status ] = $default_row;
			}
		}
		return $stored;
	}

	public static function get_template( $status ) {
		$templates = self::get_templates();
		return isset( $templates[ $status ] ) ? $templates[ $status ] : array(
			'enabled' => '0',
			'text'    => '',
		);
	}

	public static function update_templates( array $templates ) {
		update_option( self::TEMPLATES_KEY, $templates );
	}

	public static function api_key() {
		$key = get_option( 'pkst_api_key' );
		if ( ! $key ) {
			$key = wp_generate_password( 32, false );
			update_option( 'pkst_api_key', $key );
		}
		return $key;
	}
}
