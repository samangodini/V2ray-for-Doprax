<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Proof-of-delivery file handling (signature + photo). Files are stored
 * under wp-content/uploads/pkst/{shipment_id}/ with randomized names,
 * deliberately kept out of the WP Media Library so delivery documents
 * (which can show a stranger's signature/doorstep) don't clutter the
 * site-wide media grid or show up in unrelated media pickers.
 */
class PKST_POD {

	public static function base_dir() {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['basedir'] ) . 'pkst';
	}

	public static function base_url() {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['baseurl'] ) . 'pkst';
	}

	private static function shipment_dir( $shipment_id ) {
		$dir = trailingslashit( self::base_dir() ) . absint( $shipment_id );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			$index = trailingslashit( $dir ) . 'index.php';
			if ( ! file_exists( $index ) ) {
				file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
		}
		return $dir;
	}

	/**
	 * @param int    $shipment_id
	 * @param string $data_url  A `data:image/png;base64,...` string from the signature-pad canvas.
	 * @return string|WP_Error Relative path (from uploads basedir) on success.
	 */
	public static function save_signature( $shipment_id, $data_url ) {
		if ( ! is_string( $data_url ) || ! preg_match( '/^data:image\/png;base64,/', $data_url ) ) {
			return new WP_Error( 'pkst_invalid_signature', __( 'فرمت امضا نامعتبر است.', 'peykherfei-shipment-tracking' ) );
		}

		$binary = base64_decode( substr( $data_url, strpos( $data_url, ',' ) + 1 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( ! $binary || strlen( $binary ) > 2 * MB_IN_BYTES ) {
			return new WP_Error( 'pkst_invalid_signature', __( 'فایل امضا نامعتبر یا حجیم است.', 'peykherfei-shipment-tracking' ) );
		}

		$dir      = self::shipment_dir( $shipment_id );
		$filename = 'signature-' . wp_generate_password( 8, false ) . '.png';
		$path     = trailingslashit( $dir ) . $filename;

		if ( false === file_put_contents( $path, $binary ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return new WP_Error( 'pkst_write_failed', __( 'ذخیره فایل امضا با خطا مواجه شد.', 'peykherfei-shipment-tracking' ) );
		}

		return 'pkst/' . absint( $shipment_id ) . '/' . $filename;
	}

	/**
	 * @param int   $shipment_id
	 * @param array $file  A single entry from $_FILES.
	 * @return string|WP_Error Relative path (from uploads basedir) on success.
	 */
	public static function save_photo( $shipment_id, array $file ) {
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'pkst_invalid_upload', __( 'فایل تصویر ارسال نشده است.', 'peykherfei-shipment-tracking' ) );
		}

		if ( ! empty( $file['size'] ) && $file['size'] > 5 * MB_IN_BYTES ) {
			return new WP_Error( 'pkst_file_too_large', __( 'حجم تصویر نباید بیشتر از ۵ مگابایت باشد.', 'peykherfei-shipment-tracking' ) );
		}

		$filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		$allowed  = array( 'jpg', 'jpeg', 'png', 'webp' );
		if ( empty( $filetype['ext'] ) || ! in_array( strtolower( $filetype['ext'] ), $allowed, true ) ) {
			return new WP_Error( 'pkst_invalid_type', __( 'فقط فایل تصویری (jpg, png, webp) مجاز است.', 'peykherfei-shipment-tracking' ) );
		}

		$dir      = self::shipment_dir( $shipment_id );
		$filename = 'photo-' . wp_generate_password( 8, false ) . '.' . strtolower( $filetype['ext'] );
		$target   = trailingslashit( $dir ) . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $target ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_move_uploaded_file
			return new WP_Error( 'pkst_write_failed', __( 'ذخیره تصویر با خطا مواجه شد.', 'peykherfei-shipment-tracking' ) );
		}

		return 'pkst/' . absint( $shipment_id ) . '/' . $filename;
	}

	public static function url_for_path( $relative_path ) {
		if ( ! $relative_path ) {
			return '';
		}
		$upload = wp_upload_dir();
		return trailingslashit( $upload['baseurl'] ) . ltrim( $relative_path, '/' );
	}
}
