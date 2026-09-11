<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PKST_Roles {

	const MANAGER  = 'pkst_manager';
	const COURIER  = 'pkst_courier';
	const CUSTOMER = 'pkst_customer';

	public static function manage_caps() {
		return array(
			'pkst_manage_shipments',
			'pkst_manage_settings',
			'pkst_manage_users',
			'pkst_view_reports',
		);
	}

	/**
	 * Removes then re-adds the plugin's roles on every activation/upgrade
	 * run (add_role() is a no-op if the slug already exists) so a later
	 * version that changes the capability list actually takes effect for
	 * existing sites, not just fresh installs. Safe to do: role assignment
	 * on individual users is stored by slug and is untouched by this.
	 */
	public static function register() {
		self::remove();

		add_role(
			self::MANAGER,
			__( 'مدیر مرسولات', 'peykherfei-shipment-tracking' ),
			array_fill_keys( array_merge( array( 'read' ), self::manage_caps() ), true )
		);

		add_role(
			self::COURIER,
			__( 'پیک', 'peykherfei-shipment-tracking' ),
			array(
				'read'                      => true,
				'pkst_courier_access'       => true,
				'pkst_update_shipment_status' => true,
			)
		);

		add_role(
			self::CUSTOMER,
			__( 'مشتری', 'peykherfei-shipment-tracking' ),
			array(
				'read'                => true,
				'pkst_customer_access' => true,
			)
		);

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::manage_caps() as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	public static function remove() {
		remove_role( self::MANAGER );
		remove_role( self::COURIER );
		remove_role( self::CUSTOMER );

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::manage_caps() as $cap ) {
				$admin->remove_cap( $cap );
			}
		}
	}

	public static function current_user_is_courier() {
		return is_user_logged_in() && current_user_can( 'pkst_courier_access' );
	}

	public static function current_user_is_customer() {
		return is_user_logged_in() && current_user_can( 'pkst_customer_access' );
	}

	public static function current_user_can_manage() {
		return current_user_can( 'pkst_manage_shipments' );
	}
}
