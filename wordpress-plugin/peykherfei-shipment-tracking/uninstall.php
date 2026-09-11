<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'pkst_settings', array() );
if ( empty( $settings['delete_data_on_uninstall'] ) || '1' !== (string) $settings['delete_data_on_uninstall'] ) {
	return;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pkst_shipments" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pkst_status_log" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

delete_option( 'pkst_settings' );
delete_option( 'pkst_sms_templates' ); // cleans up a leftover option from earlier dev builds that had per-status SMS templates.
delete_option( 'pkst_api_key' );
delete_option( 'pkst_db_version' );

remove_role( 'pkst_manager' );
remove_role( 'pkst_courier' );
remove_role( 'pkst_customer' );

$admin = get_role( 'administrator' );
if ( $admin ) {
	foreach ( array( 'pkst_manage_shipments', 'pkst_manage_settings', 'pkst_manage_users', 'pkst_view_reports' ) as $cap ) {
		$admin->remove_cap( $cap );
	}
}

$upload = wp_upload_dir();
$pkst_dir = trailingslashit( $upload['basedir'] ) . 'pkst';
if ( is_dir( $pkst_dir ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;
	if ( $wp_filesystem ) {
		$wp_filesystem->delete( $pkst_dir, true );
	}
}
