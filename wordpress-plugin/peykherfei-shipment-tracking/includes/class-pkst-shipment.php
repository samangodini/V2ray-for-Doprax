<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD + query layer for shipments. All writes funnel through create()/
 * update()/update_status() so the status log and the pkst_status_changed
 * action (a generic extension point other code can hook into) stay
 * consistent no matter which caller (admin form, front-end courier panel,
 * REST API) is involved.
 */
class PKST_Shipment {

	public static function create( array $data ) {
		global $wpdb;

		$recipient_name  = isset( $data['recipient_name'] ) ? sanitize_text_field( $data['recipient_name'] ) : '';
		$recipient_phone = isset( $data['recipient_phone'] ) ? self::sanitize_phone( $data['recipient_phone'] ) : '';
		$destination     = isset( $data['destination'] ) ? sanitize_textarea_field( $data['destination'] ) : '';

		if ( '' === $recipient_name || '' === $recipient_phone || '' === $destination ) {
			return new WP_Error( 'pkst_missing_fields', __( 'نام گیرنده، شماره تماس و مقصد الزامی است.', 'peykherfei-shipment-tracking' ) );
		}

		$now    = current_time( 'mysql' );
		$status = ! empty( $data['status'] ) && PKST_Status::is_valid( $data['status'] ) ? $data['status'] : PKST_Status::REGISTERED;

		$row = array(
			// Only the backup-restore path (PKST_Backup) ever passes a
			// pre-existing tracking_code, to preserve codes customers may
			// already have been given; every normal caller (admin form,
			// REST API) omits it and gets a freshly generated one.
			'tracking_code'         => ! empty( $data['tracking_code'] ) ? sanitize_text_field( $data['tracking_code'] ) : PKST_Tracking_Code::generate(),
			'sender_name'           => isset( $data['sender_name'] ) ? sanitize_text_field( $data['sender_name'] ) : '',
			'sender_phone'          => isset( $data['sender_phone'] ) ? self::sanitize_phone( $data['sender_phone'] ) : '',
			'recipient_name'        => $recipient_name,
			'recipient_phone'       => $recipient_phone,
			'origin'                => isset( $data['origin'] ) ? sanitize_text_field( $data['origin'] ) : '',
			'destination'           => $destination,
			'destination_lat'       => self::sanitize_coordinate( $data['destination_lat'] ?? null ),
			'destination_lng'       => self::sanitize_coordinate( $data['destination_lng'] ?? null ),
			'description'           => isset( $data['description'] ) ? sanitize_text_field( $data['description'] ) : '',
			'price'                 => self::sanitize_price( $data['price'] ?? null ),
			'status'                => $status,
			'courier_id'            => ! empty( $data['courier_id'] ) ? absint( $data['courier_id'] ) : null,
			'customer_user_id'      => ! empty( $data['customer_user_id'] ) ? absint( $data['customer_user_id'] ) : null,
			'handed_to_courier_at'  => ! empty( $data['handed_to_courier_at'] ) ? self::sanitize_datetime( $data['handed_to_courier_at'] ) : $now,
			'created_by'            => get_current_user_id() ?: null,
			'created_at'            => $now,
			'updated_at'            => $now,
		);

		$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s', '%d', '%d', '%s', '%d', '%s', '%s' );

		$wpdb->insert( PKST_DB::shipments_table(), $row, $formats );

		if ( ! $wpdb->insert_id ) {
			return new WP_Error( 'pkst_db_error', __( 'خطا در ثبت مرسوله.', 'peykherfei-shipment-tracking' ) );
		}

		$shipment_id = (int) $wpdb->insert_id;

		self::log_status( $shipment_id, $status, __( 'ثبت مرسوله', 'peykherfei-shipment-tracking' ), null, null, get_current_user_id() );

		/**
		 * Fires after a shipment is created, and after every status change
		 * (see update_status() below). $context tells subscribers whether
		 * this is a brand-new shipment or a transition.
		 */
		do_action( 'pkst_status_changed', $shipment_id, null, $status, array( 'is_new' => true ) );

		return $shipment_id;
	}

	public static function update( $id, array $data ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id || ! self::get( $id ) ) {
			return new WP_Error( 'pkst_not_found', __( 'مرسوله یافت نشد.', 'peykherfei-shipment-tracking' ) );
		}

		$editable = array(
			'sender_name'          => 'text',
			'sender_phone'         => 'phone',
			'recipient_name'       => 'text',
			'recipient_phone'      => 'phone',
			'origin'               => 'text',
			'destination'          => 'textarea',
			'destination_lat'      => 'coordinate',
			'destination_lng'      => 'coordinate',
			'description'          => 'text',
			'price'                => 'price',
			'courier_id'           => 'int',
			'customer_user_id'     => 'int',
			'handed_to_courier_at' => 'datetime',
		);

		$row     = array();
		$formats = array();

		foreach ( $editable as $field => $type ) {
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}
			switch ( $type ) {
				case 'phone':
					$row[ $field ] = self::sanitize_phone( $data[ $field ] );
					$formats[]     = '%s';
					break;
				case 'textarea':
					$row[ $field ] = sanitize_textarea_field( $data[ $field ] );
					$formats[]     = '%s';
					break;
				case 'int':
					$row[ $field ] = $data[ $field ] ? absint( $data[ $field ] ) : null;
					$formats[]     = '%d';
					break;
				case 'coordinate':
					$row[ $field ] = self::sanitize_coordinate( $data[ $field ] );
					$formats[]     = '%f';
					break;
				case 'price':
					$row[ $field ] = self::sanitize_price( $data[ $field ] );
					$formats[]     = '%d';
					break;
				case 'datetime':
					if ( '' === $data[ $field ] ) {
						break;
					}
					$row[ $field ] = self::sanitize_datetime( $data[ $field ] );
					$formats[]     = '%s';
					break;
				default:
					$row[ $field ] = sanitize_text_field( $data[ $field ] );
					$formats[]     = '%s';
			}
		}

		if ( empty( $row ) ) {
			return true;
		}

		if ( isset( $row['recipient_name'] ) && '' === $row['recipient_name'] ) {
			return new WP_Error( 'pkst_missing_fields', __( 'نام گیرنده الزامی است.', 'peykherfei-shipment-tracking' ) );
		}

		$row['updated_at'] = current_time( 'mysql' );
		$formats[]          = '%s';

		$wpdb->update( PKST_DB::shipments_table(), $row, array( 'id' => $id ), $formats, array( '%d' ) );

		return true;
	}

	/**
	 * The only path allowed to change `status`. Writes a status_log row,
	 * updates the shipments row, and fires pkst_status_changed so anything
	 * hooked into it can react.
	 */
	public static function update_status( $id, $status, array $args = array() ) {
		global $wpdb;

		$id = absint( $id );
		$shipment = self::get( $id );

		if ( ! $shipment ) {
			return new WP_Error( 'pkst_not_found', __( 'مرسوله یافت نشد.', 'peykherfei-shipment-tracking' ) );
		}

		if ( ! PKST_Status::is_valid( $status ) ) {
			return new WP_Error( 'pkst_invalid_status', __( 'وضعیت نامعتبر است.', 'peykherfei-shipment-tracking' ) );
		}

		$old_status = $shipment['status'];
		$now        = current_time( 'mysql' );

		$update = array(
			'status'     => $status,
			'updated_at' => $now,
		);
		$formats = array( '%s', '%s' );

		if ( in_array( $status, array( PKST_Status::DELIVERED, PKST_Status::FAILED ), true ) ) {
			$update['delivered_at'] = $now;
			$formats[]              = '%s';

			if ( isset( $args['pod_receiver_name'] ) ) {
				$update['pod_receiver_name'] = sanitize_text_field( $args['pod_receiver_name'] );
				$formats[]                    = '%s';
			}
			$update['pod_status'] = PKST_Status::DELIVERED === $status ? 'delivered' : 'failed';
			$formats[]             = '%s';

			if ( isset( $args['pod_failure_reason'] ) ) {
				$update['pod_failure_reason'] = sanitize_textarea_field( $args['pod_failure_reason'] );
				$formats[]                     = '%s';
			}
			if ( isset( $args['pod_signature_path'] ) ) {
				$update['pod_signature_path'] = sanitize_text_field( $args['pod_signature_path'] );
				$formats[]                     = '%s';
			}
			if ( isset( $args['pod_photo_path'] ) ) {
				$update['pod_photo_path'] = sanitize_text_field( $args['pod_photo_path'] );
				$formats[]                 = '%s';
			}
		}

		$wpdb->update( PKST_DB::shipments_table(), $update, array( 'id' => $id ), $formats, array( '%d' ) );

		$changed_by = ! empty( $args['changed_by'] ) ? absint( $args['changed_by'] ) : get_current_user_id();
		$lat        = isset( $args['lat'] ) && '' !== $args['lat'] ? (float) $args['lat'] : null;
		$lng        = isset( $args['lng'] ) && '' !== $args['lng'] ? (float) $args['lng'] : null;
		$note       = isset( $args['note'] ) ? sanitize_textarea_field( $args['note'] ) : '';

		self::log_status( $id, $status, $note, $lat, $lng, $changed_by );

		if ( $lat && $lng && $shipment['courier_id'] ) {
			update_user_meta( $shipment['courier_id'], 'pkst_last_lat', $lat );
			update_user_meta( $shipment['courier_id'], 'pkst_last_lng', $lng );
			update_user_meta( $shipment['courier_id'], 'pkst_last_location_at', $now );
		}

		do_action( 'pkst_status_changed', $id, $old_status, $status, array( 'is_new' => false ) );

		return true;
	}

	public static function log_status( $shipment_id, $status, $note = '', $lat = null, $lng = null, $changed_by = null ) {
		global $wpdb;

		$wpdb->insert(
			PKST_DB::status_log_table(),
			array(
				'shipment_id' => absint( $shipment_id ),
				'status'      => $status,
				'note'        => $note,
				'lat'         => $lat,
				'lng'         => $lng,
				'changed_by'  => $changed_by ?: null,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%f', '%f', '%d', '%s' )
		);
	}

	public static function get_status_log( $shipment_id ) {
		global $wpdb;
		$table = PKST_DB::status_log_table();
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE shipment_id = %d ORDER BY created_at ASC, id ASC", absint( $shipment_id ) ),
			ARRAY_A
		);
	}

	public static function get( $id ) {
		global $wpdb;
		$table = PKST_DB::shipments_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ), ARRAY_A );
	}

	public static function get_by_tracking_code( $code ) {
		global $wpdb;
		$table = PKST_DB::shipments_table();
		$code  = sanitize_text_field( $code );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE tracking_code = %s", $code ), ARRAY_A );
	}

	/**
	 * A customer sees a shipment either because the admin explicitly linked
	 * it to their account, or because the recipient phone on the shipment
	 * matches the phone on file for their account -- so admins don't have
	 * to manually link every single shipment for a known customer.
	 */
	public static function get_for_customer( $user_id, $limit = 100 ) {
		global $wpdb;
		$table = PKST_DB::shipments_table();
		$phone = PKST_User_Manager::get_phone( $user_id );

		if ( $phone ) {
			$sql = "SELECT * FROM {$table} WHERE customer_user_id = %d OR recipient_phone = %s ORDER BY created_at DESC LIMIT %d";
			return $wpdb->get_results( $wpdb->prepare( $sql, absint( $user_id ), $phone, absint( $limit ) ), ARRAY_A );
		}

		$sql = "SELECT * FROM {$table} WHERE customer_user_id = %d ORDER BY created_at DESC LIMIT %d";
		return $wpdb->get_results( $wpdb->prepare( $sql, absint( $user_id ), absint( $limit ) ), ARRAY_A );
	}

	public static function get_for_courier( $user_id, $limit = 100 ) {
		global $wpdb;
		$table = PKST_DB::shipments_table();
		$sql   = "SELECT * FROM {$table} WHERE courier_id = %d ORDER BY created_at DESC LIMIT %d";
		return $wpdb->get_results( $wpdb->prepare( $sql, absint( $user_id ), absint( $limit ) ), ARRAY_A );
	}

	public static function delete( $id ) {
		global $wpdb;
		$id = absint( $id );
		$wpdb->delete( PKST_DB::status_log_table(), array( 'shipment_id' => $id ), array( '%d' ) );
		return (bool) $wpdb->delete( PKST_DB::shipments_table(), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Shared search/filter/paginate query used by the admin list table,
	 * Excel export, and the courier/customer panels.
	 *
	 * @return array{items: array, total: int}
	 */
	public static function query( array $args = array() ) {
		global $wpdb;
		$table = PKST_DB::shipments_table();

		$defaults = array(
			'search'      => '',
			'status'      => '',
			'date_from'   => '',
			'date_to'     => '',
			'courier_id'  => 0,
			'customer_id' => 0,
			'recipient_phone' => '',
			'orderby'     => 'created_at',
			'order'       => 'DESC',
			'page'        => 1,
			'per_page'    => 20,
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();

		if ( '' !== $args['search'] ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(tracking_code LIKE %s OR recipient_name LIKE %s OR recipient_phone LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}

		if ( '' !== $args['status'] && PKST_Status::is_valid( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		if ( '' !== $args['date_from'] ) {
			$where[]  = 'created_at >= %s';
			$values[] = self::sanitize_datetime( $args['date_from'] . ' 00:00:00' );
		}

		if ( '' !== $args['date_to'] ) {
			$where[]  = 'created_at <= %s';
			$values[] = self::sanitize_datetime( $args['date_to'] . ' 23:59:59' );
		}

		if ( $args['courier_id'] ) {
			$where[]  = 'courier_id = %d';
			$values[] = absint( $args['courier_id'] );
		}

		if ( $args['customer_id'] ) {
			$where[]  = 'customer_user_id = %d';
			$values[] = absint( $args['customer_id'] );
		}

		if ( '' !== $args['recipient_phone'] ) {
			$where[]  = 'recipient_phone = %s';
			$values[] = self::sanitize_phone( $args['recipient_phone'] );
		}

		$allowed_orderby = array( 'created_at', 'updated_at', 'status', 'recipient_name', 'handed_to_courier_at', 'price' );
		$orderby          = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order            = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$total     = (int) ( empty( $values ) ? $wpdb->get_var( $count_sql ) : $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) ) );

		$per_page = max( 1, absint( $args['per_page'] ) );
		$page     = max( 1, absint( $args['page'] ) );
		$offset   = ( $page - 1 ) * $per_page;

		$data_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$data_values = array_merge( $values, array( $per_page, $offset ) );
		$items = $wpdb->get_results( $wpdb->prepare( $data_sql, $data_values ), ARRAY_A );

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	public static function counts() {
		global $wpdb;
		$table = PKST_DB::shipments_table();

		$rows = $wpdb->get_results( "SELECT status, COUNT(*) as cnt FROM {$table} GROUP BY status", ARRAY_A );

		$counts = array_fill_keys( PKST_Status::all(), 0 );
		$counts['total'] = 0;

		foreach ( $rows as $row ) {
			$counts[ $row['status'] ] = (int) $row['cnt'];
			$counts['total']         += (int) $row['cnt'];
		}

		$overdue_hours    = (int) PKST_Settings::get( 'overdue_hours', 48 );
		$threshold        = current_datetime()->modify( '-' . $overdue_hours . ' hours' )->format( 'Y-m-d H:i:s' );
		$open_statuses    = array_diff( PKST_Status::all(), array( PKST_Status::DELIVERED, PKST_Status::FAILED ) );
		$placeholders     = implode( ',', array_fill( 0, count( $open_statuses ), '%s' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $placeholders is a fixed-size list of %s tokens, not user input.
		$overdue_sql = "SELECT COUNT(*) FROM {$table} WHERE status IN ({$placeholders}) AND created_at <= %s";
		$counts['overdue'] = (int) $wpdb->get_var( $wpdb->prepare( $overdue_sql, array_merge( $open_statuses, array( $threshold ) ) ) );

		return $counts;
	}

	public static function total_revenue() {
		global $wpdb;
		$table = PKST_DB::shipments_table();
		return (int) $wpdb->get_var( "SELECT SUM(price) FROM {$table}" );
	}

	public static function courier_performance() {
		global $wpdb;
		$table = PKST_DB::shipments_table();

		$rows = $wpdb->get_results(
			"SELECT courier_id,
				COUNT(*) as total,
				SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
				SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
				AVG(CASE WHEN status = 'delivered' AND delivered_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, handed_to_courier_at, delivered_at) END) as avg_minutes
			FROM {$table}
			WHERE courier_id IS NOT NULL
			GROUP BY courier_id
			ORDER BY total DESC",
			ARRAY_A
		);

		foreach ( $rows as &$row ) {
			$user            = get_userdata( $row['courier_id'] );
			$row['name']     = $user ? $user->display_name : sprintf( '#%d', $row['courier_id'] );
			$row['avg_minutes'] = $row['avg_minutes'] ? round( $row['avg_minutes'] ) : null;
		}

		return $rows;
	}

	public static function sanitize_phone( $phone ) {
		$phone = preg_replace( '/[^0-9+]/', '', (string) $phone );
		return sanitize_text_field( $phone );
	}

	/**
	 * Accepts a price typed with Persian digits and/or thousands separators
	 * (as the admin form's live-formatting JS produces) and returns a plain
	 * non-negative integer, or null when nothing usable was given.
	 */
	public static function sanitize_price( $price ) {
		if ( null === $price || '' === $price ) {
			return null;
		}
		$normalized = PKST_Jalali::from_persian_digits( (string) $price );
		$normalized = preg_replace( '/[^0-9]/', '', $normalized );
		return '' === $normalized ? null : absint( $normalized );
	}

	public static function sanitize_coordinate( $value ) {
		if ( null === $value || '' === $value || ! is_numeric( $value ) ) {
			return null;
		}
		return round( (float) $value, 7 );
	}

	/**
	 * @return string Formatted with thousands separators and Persian
	 *                digits, e.g. "۱٬۵۰۰٬۰۰۰ تومان", or '' when unset.
	 */
	public static function format_price( $price ) {
		if ( null === $price || '' === $price ) {
			return '';
		}
		$formatted = number_format( (float) $price, 0, '.', '٬' );
		return PKST_Jalali::to_persian_digits( $formatted ) . ' تومان';
	}

	/**
	 * Normalizes a `datetime-local` input (or a `date` + fixed time-of-day)
	 * into MySQL DATETIME format, treating it as wall-clock site-local time
	 * -- the same thing current_time( 'mysql' ) produces elsewhere in this
	 * class. Deliberately does not round-trip through strtotime()/gmdate(),
	 * which would silently shift by the server's PHP timezone offset and
	 * make this column inconsistent with created_at/updated_at.
	 */
	public static function sanitize_datetime( $value ) {
		$value = str_replace( 'T', ' ', trim( (string) $value ) );

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return $value . ' 00:00:00';
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value ) ) {
			return $value . ':00';
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value ) ) {
			return $value;
		}

		return current_time( 'mysql' );
	}
}
