<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared Leaflet + OpenStreetMap destination picker, used by both the
 * admin shipment form and the front-end saved-addresses panel. Leaflet +
 * OSM tiles + Nominatim geocoding are used specifically because they need
 * no API key/signup -- unlike Google Maps or Neshan, which would require
 * the site owner to go set up their own account before this works at all.
 * Only ever loaded on low-volume, authenticated screens (wp-admin, a
 * logged-in customer's own panel), never on the public tracking page, to
 * stay well inside Nominatim's fair-use request volume.
 */
class PKST_Map {

	const LEAFLET_VERSION = '1.9.4';

	/** Geographic center of Iran, used as the default view when no point is set yet. */
	const DEFAULT_LAT = 32.4279;
	const DEFAULT_LNG = 53.6880;

	public static function enqueue() {
		wp_enqueue_style( 'pkst-leaflet', 'https://cdn.jsdelivr.net/npm/leaflet@' . self::LEAFLET_VERSION . '/dist/leaflet.css', array(), self::LEAFLET_VERSION );
		wp_enqueue_script( 'pkst-leaflet', 'https://cdn.jsdelivr.net/npm/leaflet@' . self::LEAFLET_VERSION . '/dist/leaflet.js', array(), self::LEAFLET_VERSION, true );
		wp_enqueue_style( 'pkst-map-picker', PKST_PLUGIN_URL . 'assets/css/map-picker.css', array( 'pkst-leaflet' ), PKST_VERSION );
		wp_enqueue_script( 'pkst-map-picker', PKST_PLUGIN_URL . 'assets/js/map-picker.js', array( 'pkst-leaflet' ), PKST_VERSION, true );
	}

	/**
	 * @param array $args {
	 *     @type string $lat_field     Name attribute for the hidden latitude input.
	 *     @type string $lng_field     Name attribute for the hidden longitude input.
	 *     @type string $address_field Element ID of the text field to auto-fill via reverse geocoding.
	 *     @type string $lat
	 *     @type string $lng
	 *     @type string $height        CSS height of the map box.
	 * }
	 */
	public static function render_picker( array $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'lat_field'     => 'destination_lat',
				'lng_field'     => 'destination_lng',
				'address_field' => 'destination',
				'lat'           => '',
				'lng'           => '',
				'height'        => '260px',
			)
		);

		ob_start();
		?>
		<div class="pkst-map-picker">
			<div class="pkst-map-tools">
				<input type="text" class="pkst-map-search regular-text" placeholder="<?php esc_attr_e( 'جستجوی آدرس یا نام محل...', 'peykherfei-shipment-tracking' ); ?>" />
				<button type="button" class="button pkst-map-search-btn"><?php esc_html_e( 'جستجو', 'peykherfei-shipment-tracking' ); ?></button>
				<button type="button" class="button pkst-map-locate"><?php esc_html_e( 'موقعیت من', 'peykherfei-shipment-tracking' ); ?></button>
			</div>
			<div class="pkst-map" data-address-field="<?php echo esc_attr( $args['address_field'] ); ?>" data-default-lat="<?php echo esc_attr( self::DEFAULT_LAT ); ?>" data-default-lng="<?php echo esc_attr( self::DEFAULT_LNG ); ?>" style="height: <?php echo esc_attr( $args['height'] ); ?>;"></div>
			<p class="pkst-map-status description"></p>
			<input type="hidden" class="pkst-map-lat" name="<?php echo esc_attr( $args['lat_field'] ); ?>" value="<?php echo esc_attr( $args['lat'] ); ?>" />
			<input type="hidden" class="pkst-map-lng" name="<?php echo esc_attr( $args['lng_field'] ); ?>" value="<?php echo esc_attr( $args['lng'] ); ?>" />
		</div>
		<?php
		return ob_get_clean();
	}
}
