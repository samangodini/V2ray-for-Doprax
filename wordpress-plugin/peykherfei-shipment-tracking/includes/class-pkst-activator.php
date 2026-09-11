<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PKST_Activator {

	public static function activate() {
		self::create_tables();
		PKST_Roles::register();
		PKST_Settings::set_defaults();

		if ( ! get_option( 'pkst_api_key' ) ) {
			update_option( 'pkst_api_key', wp_generate_password( 32, false ) );
		}

		update_option( 'pkst_db_version', PKST_DB_VERSION );
		flush_rewrite_rules();
	}

	private static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$shipments = PKST_DB::shipments_table();
		$status_log = PKST_DB::status_log_table();
		$sms_log = PKST_DB::sms_log_table();

		$sql = "CREATE TABLE {$shipments} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			tracking_code VARCHAR(40) NOT NULL,
			sender_name VARCHAR(191) NULL,
			sender_phone VARCHAR(32) NULL,
			recipient_name VARCHAR(191) NOT NULL,
			recipient_phone VARCHAR(32) NOT NULL,
			origin VARCHAR(255) NULL,
			destination TEXT NOT NULL,
			description VARCHAR(255) NULL,
			status VARCHAR(32) NOT NULL DEFAULT 'registered',
			courier_id BIGINT UNSIGNED NULL,
			customer_user_id BIGINT UNSIGNED NULL,
			handed_to_courier_at DATETIME NULL,
			delivered_at DATETIME NULL,
			pod_receiver_name VARCHAR(191) NULL,
			pod_status VARCHAR(32) NULL,
			pod_failure_reason TEXT NULL,
			pod_signature_path VARCHAR(255) NULL,
			pod_photo_path VARCHAR(255) NULL,
			created_by BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY tracking_code (tracking_code),
			KEY status (status),
			KEY recipient_phone (recipient_phone),
			KEY courier_id (courier_id),
			KEY customer_user_id (customer_user_id),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $sql );

		$sql = "CREATE TABLE {$status_log} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shipment_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(32) NOT NULL,
			note TEXT NULL,
			lat DECIMAL(10,7) NULL,
			lng DECIMAL(10,7) NULL,
			changed_by BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY shipment_id (shipment_id)
		) {$charset_collate};";
		dbDelta( $sql );

		$sql = "CREATE TABLE {$sms_log} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			shipment_id BIGINT UNSIGNED NOT NULL,
			phone VARCHAR(32) NOT NULL,
			status_trigger VARCHAR(32) NOT NULL,
			message TEXT NOT NULL,
			gateway VARCHAR(32) NOT NULL,
			result VARCHAR(16) NOT NULL,
			response TEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY shipment_id (shipment_id)
		) {$charset_collate};";
		dbDelta( $sql );
	}
}
