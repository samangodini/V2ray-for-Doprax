<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lets a shipment manager create courier/customer accounts from inside the
 * plugin's own admin screens instead of sending them to wp-admin's generic
 * "Add New User" screen (which also exposes unrelated WP roles).
 */
class PKST_User_Manager {

	public static function create_user( array $args ) {
		$role  = isset( $args['role'] ) ? sanitize_key( $args['role'] ) : '';
		$name  = isset( $args['name'] ) ? sanitize_text_field( $args['name'] ) : '';
		$email = isset( $args['email'] ) ? sanitize_email( $args['email'] ) : '';
		$phone = isset( $args['phone'] ) ? PKST_Shipment::sanitize_phone( $args['phone'] ) : '';

		if ( ! in_array( $role, array( PKST_Roles::COURIER, PKST_Roles::CUSTOMER ), true ) ) {
			return new WP_Error( 'pkst_invalid_role', __( 'نقش کاربر نامعتبر است.', 'peykherfei-shipment-tracking' ) );
		}

		if ( '' === $name ) {
			return new WP_Error( 'pkst_missing_name', __( 'نام کاربر الزامی است.', 'peykherfei-shipment-tracking' ) );
		}

		if ( '' === $email || ! is_email( $email ) ) {
			return new WP_Error( 'pkst_invalid_email', __( 'ایمیل معتبر الزامی است.', 'peykherfei-shipment-tracking' ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'pkst_email_exists', __( 'این ایمیل قبلاً ثبت شده است.', 'peykherfei-shipment-tracking' ) );
		}

		$username = self::unique_username( $email, $name );
		$password = ! empty( $args['password'] ) ? $args['password'] : wp_generate_password( 12, false );

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $name,
				'first_name'   => $name,
				'role'         => $role,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		if ( $phone ) {
			update_user_meta( $user_id, 'pkst_phone', $phone );
		}
		if ( PKST_Roles::COURIER === $role && ! empty( $args['vehicle'] ) ) {
			update_user_meta( $user_id, 'pkst_vehicle', sanitize_text_field( $args['vehicle'] ) );
		}
		update_user_meta( $user_id, 'pkst_active', '1' );

		if ( ! empty( $args['notify'] ) ) {
			wp_new_user_notification( $user_id, null, 'both' );
		}

		/**
		 * WordPress never emails the raw password (only a reset link, via
		 * wp_new_user_notification above), so the password chosen/generated
		 * here has to be handed back to the caller if the admin is going to
		 * be able to see and relay it -- especially important on hosts
		 * where outbound mail isn't configured and the reset-link email
		 * never arrives.
		 */
		return array(
			'user_id'  => $user_id,
			'username' => $username,
			'password' => $password,
		);
	}

	public static function update_status_active( $user_id, $active ) {
		update_user_meta( $user_id, 'pkst_active', $active ? '1' : '0' );
	}

	/**
	 * Deletes a courier/customer account outright (deactivating only hides
	 * it from active use, it doesn't remove it). Shipment history is kept
	 * for the record, but its courier_id/customer_user_id are cleared
	 * first so a deleted account doesn't leave a dangling reference that
	 * silently resolves to nothing -- the shipment instead correctly shows
	 * "unassigned" going forward, same as one that was never assigned.
	 */
	public static function delete_user( $user_id ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			return new WP_Error( 'pkst_user_not_found', __( 'کاربر یافت نشد.', 'peykherfei-shipment-tracking' ) );
		}

		$table = PKST_DB::shipments_table();
		$wpdb->update( $table, array( 'courier_id' => null ), array( 'courier_id' => $user_id ), array( '%d' ), array( '%d' ) );
		$wpdb->update( $table, array( 'customer_user_id' => null ), array( 'customer_user_id' => $user_id ), array( '%d' ), array( '%d' ) );

		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		if ( ! wp_delete_user( $user_id ) ) {
			return new WP_Error( 'pkst_delete_failed', __( 'حذف کاربر با خطا مواجه شد.', 'peykherfei-shipment-tracking' ) );
		}

		return true;
	}

	public static function list_by_role( $role ) {
		return get_users(
			array(
				'role'    => $role,
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);
	}

	public static function get_phone( $user_id ) {
		return get_user_meta( $user_id, 'pkst_phone', true );
	}

	public static function is_active( $user_id ) {
		$active = get_user_meta( $user_id, 'pkst_active', true );
		return '' === $active || '1' === $active;
	}

	private static function unique_username( $email, $name ) {
		$base = sanitize_user( current( explode( '@', $email ) ), true );
		if ( '' === $base ) {
			$base = sanitize_user( $name, true );
		}
		if ( '' === $base ) {
			$base = 'pkst-user';
		}

		$username = $base;
		$i        = 1;
		while ( username_exists( $username ) ) {
			$username = $base . $i;
			$i++;
		}

		return $username;
	}
}
