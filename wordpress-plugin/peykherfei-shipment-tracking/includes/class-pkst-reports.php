<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PKST_Reports {

	/**
	 * Shipments created per day for the last $days days, for a simple
	 * dependency-free bar chart on the dashboard.
	 *
	 * Uses current_datetime() (site-local, per WP's Settings > General
	 * timezone) rather than time()/gmdate(), because created_at is stored
	 * via current_time( 'mysql' ) -- comparing that against UTC-based day
	 * boundaries would misfile shipments near local midnight whenever the
	 * site isn't on UTC.
	 */
	public static function daily_counts( $days = 14 ) {
		global $wpdb;
		$table = PKST_DB::shipments_table();
		$days  = max( 1, absint( $days ) );
		$today = current_datetime();
		$since = $today->modify( '-' . ( $days - 1 ) . ' days' )->format( 'Y-m-d' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) as d, COUNT(*) as cnt FROM {$table} WHERE created_at >= %s GROUP BY DATE(created_at)",
				$since . ' 00:00:00'
			),
			ARRAY_A
		);

		$by_date = array();
		foreach ( $rows as $row ) {
			$by_date[ $row['d'] ] = (int) $row['cnt'];
		}

		$result = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$date            = $today->modify( '-' . $i . ' days' )->format( 'Y-m-d' );
			$result[ $date ] = isset( $by_date[ $date ] ) ? $by_date[ $date ] : 0;
		}

		return $result;
	}

	public static function avg_delivery_minutes() {
		global $wpdb;
		$table = PKST_DB::shipments_table();

		$avg = $wpdb->get_var(
			"SELECT AVG(TIMESTAMPDIFF(MINUTE, handed_to_courier_at, delivered_at))
			FROM {$table}
			WHERE status = 'delivered' AND delivered_at IS NOT NULL AND handed_to_courier_at IS NOT NULL"
		);

		return $avg ? round( (float) $avg ) : null;
	}

	public static function format_minutes( $minutes ) {
		if ( null === $minutes ) {
			return '—';
		}
		$hours = floor( $minutes / 60 );
		$mins  = $minutes % 60;

		if ( $hours > 0 ) {
			/* translators: 1: hours 2: minutes */
			return sprintf( __( '%1$d ساعت و %2$d دقیقه', 'peykherfei-shipment-tracking' ), $hours, $mins );
		}

		/* translators: %d: minutes */
		return sprintf( __( '%d دقیقه', 'peykherfei-shipment-tracking' ), $mins );
	}
}
