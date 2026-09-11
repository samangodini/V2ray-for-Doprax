<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exports as UTF-8 CSV with a BOM: Excel opens this natively (including
 * correctly-rendered Persian text) without needing a heavy XLSX-writing
 * dependency that many shared WordPress hosts can't run reliably.
 */
class PKST_Export {

	public static function handle_csv_export() {
		if ( ! current_user_can( 'pkst_manage_shipments' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}

		check_admin_referer( 'pkst_export_csv' );

		$args = array(
			'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'status'    => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
			'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
			'per_page'  => 100000,
			'page'      => 1,
		);

		$result = PKST_Shipment::query( $args );

		self::stream_shipments_csv( $result['items'], 'shipments-' . gmdate( 'Y-m-d-His' ) . '.csv' );
	}

	/**
	 * Lets a logged-in customer download their own shipment history --
	 * the exact same set of rows their panel shows them (get_for_customer():
	 * shipments explicitly linked to their account, plus any whose
	 * recipient phone matches the phone on file), never any other
	 * customer's data.
	 */
	public static function handle_customer_csv_export() {
		if ( ! PKST_Roles::current_user_is_customer() ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}
		check_admin_referer( 'pkst_export_my_shipments' );

		$items = PKST_Shipment::get_for_customer( get_current_user_id(), 100000 );

		self::stream_shipments_csv( $items, 'my-shipments-' . gmdate( 'Y-m-d-His' ) . '.csv' );
	}

	private static function stream_shipments_csv( array $items, $filename ) {
		$labels = PKST_Status::labels();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		fputcsv(
			$out,
			array(
				__( 'کد رهگیری', 'peykherfei-shipment-tracking' ),
				__( 'گیرنده', 'peykherfei-shipment-tracking' ),
				__( 'شماره تماس', 'peykherfei-shipment-tracking' ),
				__( 'مقصد', 'peykherfei-shipment-tracking' ),
				__( 'قیمت (تومان)', 'peykherfei-shipment-tracking' ),
				__( 'وضعیت', 'peykherfei-shipment-tracking' ),
				__( 'پیک', 'peykherfei-shipment-tracking' ),
				__( 'تاریخ تحویل به پیک', 'peykherfei-shipment-tracking' ),
				__( 'تاریخ تحویل نهایی', 'peykherfei-shipment-tracking' ),
				__( 'تحویل‌گیرنده', 'peykherfei-shipment-tracking' ),
				__( 'علت عدم تحویل', 'peykherfei-shipment-tracking' ),
				__( 'تاریخ ثبت', 'peykherfei-shipment-tracking' ),
			)
		);

		foreach ( $items as $row ) {
			$courier = $row['courier_id'] ? get_userdata( $row['courier_id'] ) : null;

			fputcsv(
				$out,
				array(
					$row['tracking_code'],
					$row['recipient_name'],
					$row['recipient_phone'],
					$row['destination'],
					isset( $row['price'] ) ? $row['price'] : '',
					isset( $labels[ $row['status'] ] ) ? $labels[ $row['status'] ] : $row['status'],
					$courier ? $courier->display_name : '',
					PKST_Jalali::format( $row['handed_to_courier_at'] ),
					PKST_Jalali::format( $row['delivered_at'] ),
					$row['pod_receiver_name'],
					$row['pod_failure_reason'],
					PKST_Jalali::format( $row['created_at'] ),
				)
			);
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/** Courier + customer accounts (id, name, email, phone, role, status), for the admin's own records. */
	public static function handle_users_csv_export() {
		if ( ! current_user_can( 'pkst_manage_users' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'peykherfei-shipment-tracking' ) );
		}
		check_admin_referer( 'pkst_export_users_csv' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=users-' . gmdate( 'Y-m-d-His' ) . '.csv' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		fputcsv(
			$out,
			array(
				__( 'شناسه', 'peykherfei-shipment-tracking' ),
				__( 'نام', 'peykherfei-shipment-tracking' ),
				__( 'نقش', 'peykherfei-shipment-tracking' ),
				__( 'ایمیل', 'peykherfei-shipment-tracking' ),
				__( 'شماره تماس', 'peykherfei-shipment-tracking' ),
				__( 'وسیله نقلیه', 'peykherfei-shipment-tracking' ),
				__( 'وضعیت', 'peykherfei-shipment-tracking' ),
				__( 'تاریخ عضویت', 'peykherfei-shipment-tracking' ),
			)
		);

		$role_labels = array(
			PKST_Roles::COURIER  => __( 'پیک', 'peykherfei-shipment-tracking' ),
			PKST_Roles::CUSTOMER => __( 'مشتری', 'peykherfei-shipment-tracking' ),
		);

		foreach ( array( PKST_Roles::COURIER, PKST_Roles::CUSTOMER ) as $role ) {
			foreach ( PKST_User_Manager::list_by_role( $role ) as $user ) {
				fputcsv(
					$out,
					array(
						$user->ID,
						$user->display_name,
						$role_labels[ $role ],
						$user->user_email,
						PKST_User_Manager::get_phone( $user->ID ),
						PKST_Roles::COURIER === $role ? PKST_User_Manager::get_vehicle( $user->ID ) : '',
						PKST_User_Manager::is_active( $user->ID ) ? __( 'فعال', 'peykherfei-shipment-tracking' ) : __( 'غیرفعال', 'peykherfei-shipment-tracking' ),
						PKST_Jalali::format( $user->user_registered ),
					)
				);
			}
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
