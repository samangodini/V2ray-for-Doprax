<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** Expects $shipments (array of rows) in scope. */
?>
<div class="pkst-front pkst-panel-customer">
	<div class="pkst-front-header">
		<span><?php echo esc_html( sprintf( __( 'خوش آمدید، %s', 'peykherfei-shipment-tracking' ), wp_get_current_user()->display_name ) ); ?></span>
		<a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>"><?php esc_html_e( 'خروج', 'peykherfei-shipment-tracking' ); ?></a>
	</div>

	<h3><?php esc_html_e( 'مرسولات من', 'peykherfei-shipment-tracking' ); ?></h3>

	<?php if ( empty( $shipments ) ) : ?>
		<p class="pkst-muted"><?php esc_html_e( 'در حال حاضر مرسوله‌ای برای شما ثبت نشده است.', 'peykherfei-shipment-tracking' ); ?></p>
	<?php else : ?>
		<?php foreach ( $shipments as $shipment ) : ?>
			<?php $status_log = PKST_Shipment::get_status_log( $shipment['id'] ); ?>
			<?php include __DIR__ . '/track-result.php'; ?>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
