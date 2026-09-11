<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central place for custom table names. Custom tables (rather than a CPT)
 * are used because shipments need fast filtered/paginated queries by
 * status, date range, phone number and tracking code at volumes where
 * postmeta lookups would not scale.
 */
class PKST_DB {

	public static function shipments_table() {
		global $wpdb;
		return $wpdb->prefix . 'pkst_shipments';
	}

	public static function status_log_table() {
		global $wpdb;
		return $wpdb->prefix . 'pkst_status_log';
	}

	public static function sms_log_table() {
		global $wpdb;
		return $wpdb->prefix . 'pkst_sms_log';
	}
}
