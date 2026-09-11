<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivation intentionally keeps custom tables, options and roles intact
 * so temporarily disabling the plugin never loses shipment data. Full
 * removal only happens from uninstall.php, and only if the admin opts in.
 */
class PKST_Deactivator {

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
