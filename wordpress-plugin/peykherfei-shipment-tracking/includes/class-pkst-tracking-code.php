<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PKST_Tracking_Code {

	public static function generate() {
		global $wpdb;

		$prefix = PKST_Settings::get( 'tracking_code_prefix', 'PK' );
		$table  = PKST_DB::shipments_table();

		for ( $attempt = 0; $attempt < 10; $attempt++ ) {
			$code = sprintf(
				'%s%s%s',
				$prefix,
				current_time( 'ymd' ),
				strtoupper( substr( str_shuffle( 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789' ), 0, 5 ) )
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table is a fixed internal identifier, not user input.
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE tracking_code = %s", $code ) );

			if ( ! $exists ) {
				return $code;
			}
		}

		return $prefix . current_time( 'ymdHis' ) . wp_rand( 100, 999 );
	}
}
