<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PKST_Status {

	const REGISTERED = 'registered';
	const PICKED_UP  = 'picked_up';
	const IN_TRANSIT = 'in_transit';
	const ARRIVED    = 'arrived';
	const DELIVERED  = 'delivered';
	const FAILED     = 'failed';

	public static function all() {
		return array(
			self::REGISTERED,
			self::PICKED_UP,
			self::IN_TRANSIT,
			self::ARRIVED,
			self::DELIVERED,
			self::FAILED,
		);
	}

	/**
	 * Ordered for the customer-facing timeline UI.
	 */
	public static function timeline_order() {
		return array(
			self::REGISTERED,
			self::PICKED_UP,
			self::IN_TRANSIT,
			self::ARRIVED,
			self::DELIVERED,
		);
	}

	public static function labels() {
		return array(
			self::REGISTERED => __( 'ثبت مرسوله / تحویل به پیک', 'peykherfei-shipment-tracking' ),
			self::PICKED_UP  => __( 'دریافت توسط پیک و شروع ارسال', 'peykherfei-shipment-tracking' ),
			self::IN_TRANSIT => __( 'در حال ارسال', 'peykherfei-shipment-tracking' ),
			self::ARRIVED    => __( 'رسیدن به مقصد', 'peykherfei-shipment-tracking' ),
			self::DELIVERED  => __( 'تحویل به گیرنده', 'peykherfei-shipment-tracking' ),
			self::FAILED     => __( 'عدم امکان تحویل / برگشتی', 'peykherfei-shipment-tracking' ),
		);
	}

	public static function label( $status ) {
		$labels = self::labels();
		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}

	/**
	 * CSS class suffix used for admin/front-end status badges.
	 */
	public static function badge_class( $status ) {
		$map = array(
			self::REGISTERED => 'pkst-badge-neutral',
			self::PICKED_UP  => 'pkst-badge-info',
			self::IN_TRANSIT => 'pkst-badge-info',
			self::ARRIVED    => 'pkst-badge-warning',
			self::DELIVERED  => 'pkst-badge-success',
			self::FAILED     => 'pkst-badge-danger',
		);
		return isset( $map[ $status ] ) ? $map[ $status ] : 'pkst-badge-neutral';
	}

	public static function is_valid( $status ) {
		return in_array( $status, self::all(), true );
	}

	/**
	 * Which statuses count as "pending/in progress" vs "overdue" for the
	 * dashboard: overdue = handed to courier more than $hours ago and still
	 * not delivered/failed.
	 */
	public static function is_open( $status ) {
		return ! in_array( $status, array( self::DELIVERED, self::FAILED ), true );
	}
}
