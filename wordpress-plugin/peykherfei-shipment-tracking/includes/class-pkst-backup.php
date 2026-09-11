<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Full-data backup/restore, independent of the per-list CSV exports
 * (PKST_Export): a single portable JSON file containing every shipment,
 * their full status history, every customer's saved addresses, and
 * (optionally, on restore) the plugin's settings -- so an admin can
 * recover from an accidental deletion or move the data to a fresh
 * install of the same plugin.
 *
 * Restore is strictly additive/idempotent: shipments already present
 * (matched by their unique tracking_code) are left untouched and
 * reported as skipped rather than duplicated or overwritten, so running
 * the same backup file twice is always safe.
 */
class PKST_Backup {

	const EXPORT_VERSION = 1;

	private static function require_access() {
		if ( ! current_user_can( 'pkst_manage_settings' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}
	}

	public static function export_data() {
		global $wpdb;

		$shipments  = $wpdb->get_results( 'SELECT * FROM ' . PKST_DB::shipments_table() . ' ORDER BY id ASC', ARRAY_A );
		$status_log = $wpdb->get_results( 'SELECT * FROM ' . PKST_DB::status_log_table() . ' ORDER BY id ASC', ARRAY_A );

		return array(
			'export_version' => self::EXPORT_VERSION,
			'plugin_version' => PKST_VERSION,
			'site_url'       => home_url(),
			'exported_at'    => current_time( 'mysql' ),
			'shipments'      => $shipments,
			'status_log'     => $status_log,
			'addresses'      => PKST_Address::list_all(),
			'settings'       => PKST_Settings::get_all(),
		);
	}

	public static function handle_export() {
		self::require_access();
		check_admin_referer( 'pkst_backup_export' );

		$data = self::export_data();

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=peykherfei-backup-' . gmdate( 'Y-m-d-His' ) . '.json' );
		echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw JSON file download, not HTML.
		exit;
	}

	public static function handle_import() {
		self::require_access();
		check_admin_referer( 'pkst_backup_import' );

		if ( empty( $_FILES['backup_file']['tmp_name'] ) || UPLOAD_ERR_OK !== $_FILES['backup_file']['error'] ) {
			self::redirect_with_error( __( 'فایلی برای بازیابی انتخاب نشد.', 'peykherfei-shipment-tracking' ) );
		}

		$file = $_FILES['backup_file'];
		if ( $file['size'] > 20 * MB_IN_BYTES ) {
			self::redirect_with_error( __( 'حجم فایل بک‌آپ بیش از حد مجاز است.', 'peykherfei-shipment-tracking' ) );
		}

		$contents = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents -- reading our own just-uploaded tmp file, not a remote URL.
		$data     = json_decode( (string) $contents, true );

		if ( ! is_array( $data ) || ! isset( $data['shipments'] ) || ! is_array( $data['shipments'] ) ) {
			self::redirect_with_error( __( 'فایل بک‌آپ نامعتبر است یا خراب شده.', 'peykherfei-shipment-tracking' ) );
		}

		list( $shipments_done, $shipments_skipped, $id_map ) = self::restore_shipments( $data['shipments'] );

		if ( ! empty( $data['status_log'] ) && is_array( $data['status_log'] ) ) {
			self::restore_status_log( $data['status_log'], $id_map );
		}

		list( $addresses_done, $addresses_skipped ) = self::restore_addresses( ! empty( $data['addresses'] ) && is_array( $data['addresses'] ) ? $data['addresses'] : array() );

		$settings_restored = false;
		if ( ! empty( $_POST['restore_settings'] ) && ! empty( $data['settings'] ) && is_array( $data['settings'] ) ) {
			$values = array();
			foreach ( array_keys( PKST_Settings::defaults() ) as $key ) {
				if ( isset( $data['settings'][ $key ] ) ) {
					$values[ $key ] = sanitize_text_field( $data['settings'][ $key ] );
				}
			}
			if ( $values ) {
				PKST_Settings::update_many( $values );
				$settings_restored = true;
			}
		}

		$msg = sprintf(
			/* translators: 1: shipments restored 2: shipments skipped (already existed) 3: addresses restored 4: addresses skipped */
			__( '%1$d مرسوله بازیابی شد (%2$d مورد از قبل موجود بود)، %3$d آدرس بازیابی شد (%4$d مورد از قبل موجود بود).', 'peykherfei-shipment-tracking' ),
			$shipments_done,
			$shipments_skipped,
			$addresses_done,
			$addresses_skipped
		);
		if ( $settings_restored ) {
			$msg .= ' ' . __( 'تنظیمات نیز بازیابی شد.', 'peykherfei-shipment-tracking' );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'pkst-backup',
					'pkst_notice' => 'backup_restored',
					'pkst_msg'    => rawurlencode( $msg ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * @return array{0: int, 1: int, 2: array<int,int>} done count, skipped
	 *              count, and a map of old shipment id => newly-inserted id
	 *              (for restore_status_log() to follow).
	 */
	private static function restore_shipments( array $rows ) {
		global $wpdb;
		$table = PKST_DB::shipments_table();

		$done    = 0;
		$skipped = 0;
		$id_map  = array();

		foreach ( $rows as $row ) {
			if ( empty( $row['tracking_code'] ) || empty( $row['recipient_name'] ) ) {
				continue;
			}
			if ( PKST_Shipment::get_by_tracking_code( $row['tracking_code'] ) ) {
				++$skipped;
				continue;
			}

			$new_id = PKST_Shipment::create( $row );
			if ( is_wp_error( $new_id ) ) {
				++$skipped;
				continue;
			}
			++$done;

			// create() always stamps created_at/updated_at/delivered_at/POD
			// fields with "now" (correct for a brand-new shipment); a
			// restore instead wants the ORIGINAL historical values back,
			// so patch them in as one direct, defensively-sanitized
			// follow-up update rather than complicating create()'s own
			// "create a shipment now" contract with restore-only fields.
			$patch   = array();
			$formats = array();
			if ( ! empty( $row['created_at'] ) ) {
				$patch['created_at'] = PKST_Shipment::sanitize_datetime( $row['created_at'] );
				$formats[]            = '%s';
			}
			if ( ! empty( $row['updated_at'] ) ) {
				$patch['updated_at'] = PKST_Shipment::sanitize_datetime( $row['updated_at'] );
				$formats[]            = '%s';
			}
			if ( ! empty( $row['delivered_at'] ) ) {
				$patch['delivered_at'] = PKST_Shipment::sanitize_datetime( $row['delivered_at'] );
				$formats[]              = '%s';
			}
			if ( isset( $row['pod_receiver_name'] ) && '' !== $row['pod_receiver_name'] ) {
				$patch['pod_receiver_name'] = sanitize_text_field( $row['pod_receiver_name'] );
				$formats[]                   = '%s';
			}
			if ( isset( $row['pod_status'] ) && '' !== $row['pod_status'] ) {
				$patch['pod_status'] = sanitize_text_field( $row['pod_status'] );
				$formats[]             = '%s';
			}
			if ( isset( $row['pod_failure_reason'] ) && '' !== $row['pod_failure_reason'] ) {
				$patch['pod_failure_reason'] = sanitize_textarea_field( $row['pod_failure_reason'] );
				$formats[]                     = '%s';
			}
			if ( isset( $row['pod_signature_path'] ) && '' !== $row['pod_signature_path'] ) {
				$patch['pod_signature_path'] = sanitize_text_field( $row['pod_signature_path'] );
				$formats[]                     = '%s';
			}
			if ( isset( $row['pod_photo_path'] ) && '' !== $row['pod_photo_path'] ) {
				$patch['pod_photo_path'] = sanitize_text_field( $row['pod_photo_path'] );
				$formats[]                 = '%s';
			}

			if ( $patch ) {
				$wpdb->update( $table, $patch, array( 'id' => $new_id ), $formats, array( '%d' ) );
			}

			if ( isset( $row['id'] ) ) {
				$id_map[ (int) $row['id'] ] = (int) $new_id;
			}
		}

		return array( $done, $skipped, $id_map );
	}

	/**
	 * Re-applies the full status timeline onto the newly-restored
	 * shipments (create() already logged one fresh "ثبت مرسوله" entry per
	 * shipment; this adds the rest of their real history on top of it).
	 * Rows belonging to a shipment that was skipped as a duplicate are
	 * skipped too, since that shipment already has its own history.
	 */
	private static function restore_status_log( array $rows, array $id_map ) {
		foreach ( $rows as $row ) {
			$old_shipment_id = isset( $row['shipment_id'] ) ? (int) $row['shipment_id'] : 0;
			if ( ! isset( $id_map[ $old_shipment_id ] ) || empty( $row['status'] ) ) {
				continue;
			}
			PKST_Shipment::log_status(
				$id_map[ $old_shipment_id ],
				sanitize_key( $row['status'] ),
				isset( $row['note'] ) ? $row['note'] : '',
				isset( $row['lat'] ) && '' !== $row['lat'] ? (float) $row['lat'] : null,
				isset( $row['lng'] ) && '' !== $row['lng'] ? (float) $row['lng'] : null,
				isset( $row['changed_by'] ) ? absint( $row['changed_by'] ) : null
			);
		}
	}

	/** @return array{0: int, 1: int} done count, skipped (duplicate) count. */
	private static function restore_addresses( array $rows ) {
		$done    = 0;
		$skipped = 0;

		foreach ( $rows as $row ) {
			if ( empty( $row['user_id'] ) || empty( $row['label'] ) ) {
				continue;
			}
			$existing = PKST_Address::list_for_user( $row['user_id'] );
			$dup      = false;
			foreach ( $existing as $addr ) {
				if ( $addr['label'] === $row['label']
					&& abs( (float) $addr['lat'] - (float) ( $row['lat'] ?? 0 ) ) < 0.0000005
					&& abs( (float) $addr['lng'] - (float) ( $row['lng'] ?? 0 ) ) < 0.0000005
				) {
					$dup = true;
					break;
				}
			}
			if ( $dup ) {
				++$skipped;
				continue;
			}

			$result = PKST_Address::create( $row['user_id'], $row );
			if ( is_wp_error( $result ) ) {
				++$skipped;
				continue;
			}
			++$done;
		}

		return array( $done, $skipped );
	}

	private static function redirect_with_error( $message ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'pkst-backup',
					'pkst_notice' => 'error',
					'pkst_msg'    => rawurlencode( $message ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
