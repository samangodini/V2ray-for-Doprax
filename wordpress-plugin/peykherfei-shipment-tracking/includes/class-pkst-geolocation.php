<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "At least the last known position" per the spec: no live map SDK is
 * embedded (keeps the plugin dependency-free and avoids a paid Google Maps
 * key), instead the last coordinates a courier's browser reported are
 * shown with links to open them in Google Maps / Neshan.
 */
class PKST_Geolocation {

	public static function update_courier_location( $user_id, $lat, $lng ) {
		update_user_meta( $user_id, 'pkst_last_lat', (float) $lat );
		update_user_meta( $user_id, 'pkst_last_lng', (float) $lng );
		update_user_meta( $user_id, 'pkst_last_location_at', current_time( 'mysql' ) );
	}

	public static function get_courier_location( $user_id ) {
		$lat = get_user_meta( $user_id, 'pkst_last_lat', true );
		$lng = get_user_meta( $user_id, 'pkst_last_lng', true );
		$at  = get_user_meta( $user_id, 'pkst_last_location_at', true );

		if ( '' === $lat || '' === $lng ) {
			return null;
		}

		return array(
			'lat' => (float) $lat,
			'lng' => (float) $lng,
			'at'  => $at,
		);
	}

	public static function google_maps_url( $lat, $lng ) {
		return sprintf( 'https://www.google.com/maps?q=%s,%s', rawurlencode( $lat ), rawurlencode( $lng ) );
	}

	public static function neshan_url( $lat, $lng ) {
		return sprintf( 'https://neshan.org/maps/@%s,%s', rawurlencode( $lat ), rawurlencode( $lng ) );
	}
}
