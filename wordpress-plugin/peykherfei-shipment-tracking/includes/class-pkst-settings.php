<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wraps the plugin's general settings option. Kept as a plain option
 * rather than a DB table since this is small, rarely-written configuration.
 */
class PKST_Settings {

	const OPTION_KEY = 'pkst_settings';

	public static function defaults() {
		return array(
			'company_name'             => 'پیک خرفه',
			'tracking_code_prefix'     => 'PK',
			'overdue_hours'            => '48',
			'delete_data_on_uninstall' => '0',
		);
	}

	public static function set_defaults() {
		if ( false === get_option( self::OPTION_KEY ) ) {
			add_option( self::OPTION_KEY, self::defaults() );
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

	public static function api_key() {
		$key = get_option( 'pkst_api_key' );
		if ( ! $key ) {
			$key = wp_generate_password( 32, false );
			update_option( 'pkst_api_key', $key );
		}
		return $key;
	}
}
