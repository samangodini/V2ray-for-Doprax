<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A customer's saved/favorite addresses (Snapp-style): a label, a
 * map-picked point, and the reverse-geocoded text. Kept in their own
 * table rather than user meta so they can be queried/ordered like normal
 * rows and so an admin picking a destination for a shipment can list a
 * specific customer's addresses without loading that customer's full
 * meta blob.
 */
class PKST_Address {

	public static function create( $user_id, array $data ) {
		global $wpdb;

		$user_id      = absint( $user_id );
		$label        = isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : '';
		$address_text = isset( $data['address_text'] ) ? sanitize_text_field( $data['address_text'] ) : '';
		$lat          = isset( $data['lat'] ) ? PKST_Shipment::sanitize_coordinate( $data['lat'] ) : null;
		$lng          = isset( $data['lng'] ) ? PKST_Shipment::sanitize_coordinate( $data['lng'] ) : null;

		if ( ! $user_id || '' === $label || '' === $address_text ) {
			return new WP_Error( 'pkst_missing_fields', __( 'عنوان و آدرس الزامی است.', 'peykherfei-shipment-tracking' ) );
		}
		if ( null === $lat || null === $lng ) {
			return new WP_Error( 'pkst_missing_location', __( 'لطفاً موقعیت را روی نقشه مشخص کنید.', 'peykherfei-shipment-tracking' ) );
		}

		$inserted = $wpdb->insert(
			PKST_DB::addresses_table(),
			array(
				'user_id'      => $user_id,
				'label'        => $label,
				'address_text' => $address_text,
				'lat'          => $lat,
				'lng'          => $lng,
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%f', '%f', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'pkst_db_error', __( 'ذخیره آدرس با خطا مواجه شد.', 'peykherfei-shipment-tracking' ) );
		}

		return (int) $wpdb->insert_id;
	}

	public static function get( $id ) {
		global $wpdb;
		$table = PKST_DB::addresses_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ), ARRAY_A );
	}

	public static function list_for_user( $user_id ) {
		global $wpdb;
		$table = PKST_DB::addresses_table();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d ORDER BY label ASC", absint( $user_id ) ), ARRAY_A );
	}

	/**
	 * @return true|WP_Error True on success. Errors rather than silently
	 *                       no-ops so a spoofed ID for someone else's
	 *                       address surfaces instead of looking like it
	 *                       worked.
	 */
	public static function delete( $id, $user_id ) {
		global $wpdb;

		$address = self::get( $id );
		if ( ! $address || (int) $address['user_id'] !== (int) $user_id ) {
			return new WP_Error( 'pkst_not_found', __( 'آدرس یافت نشد.', 'peykherfei-shipment-tracking' ) );
		}

		$wpdb->delete( PKST_DB::addresses_table(), array( 'id' => absint( $id ) ), array( '%d' ) );
		return true;
	}
}
