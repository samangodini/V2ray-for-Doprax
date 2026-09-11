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
		$labels = PKST_Status::labels();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=shipments-' . gmdate( 'Y-m-d-His' ) . '.csv' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		fputcsv(
			$out,
			array(
				__( 'کد رهگیری', 'peykherfei-shipment-tracking' ),
				__( 'گیرنده', 'peykherfei-shipment-tracking' ),
				__( 'شماره تماس', 'peykherfei-shipment-tracking' ),
				__( 'مقصد', 'peykherfei-shipment-tracking' ),
				__( 'وضعیت', 'peykherfei-shipment-tracking' ),
				__( 'پیک', 'peykherfei-shipment-tracking' ),
				__( 'تاریخ تحویل به پیک', 'peykherfei-shipment-tracking' ),
				__( 'تاریخ تحویل نهایی', 'peykherfei-shipment-tracking' ),
				__( 'تحویل‌گیرنده', 'peykherfei-shipment-tracking' ),
				__( 'علت عدم تحویل', 'peykherfei-shipment-tracking' ),
				__( 'تاریخ ثبت', 'peykherfei-shipment-tracking' ),
			)
		);

		foreach ( $result['items'] as $row ) {
			$courier = $row['courier_id'] ? get_userdata( $row['courier_id'] ) : null;

			fputcsv(
				$out,
				array(
					$row['tracking_code'],
					$row['recipient_name'],
					$row['recipient_phone'],
					$row['destination'],
					isset( $labels[ $row['status'] ] ) ? $labels[ $row['status'] ] : $row['status'],
					$courier ? $courier->display_name : '',
					$row['handed_to_courier_at'],
					$row['delivered_at'],
					$row['pod_receiver_name'],
					$row['pod_failure_reason'],
					$row['created_at'],
				)
			);
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
