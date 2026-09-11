<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** Expects $shipments (array of rows) in scope. */
?>
<div class="pkst-front pkst-panel-customer">
	<?php include __DIR__ . '/partial-header.php'; ?>

	<div class="pkst-front-header">
		<span><?php echo esc_html( sprintf( __( 'خوش آمدید، %s', 'peykherfei-shipment-tracking' ), wp_get_current_user()->display_name ) ); ?></span>
		<span>
			<a href="<?php echo esc_url( add_query_arg( 'pkst_action', 'addresses', get_permalink() ) ); ?>"><?php esc_html_e( 'آدرس‌های من', 'peykherfei-shipment-tracking' ); ?></a>
			·
			<?php if ( ! empty( $shipments ) ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pkst_export_my_shipments' ), 'pkst_export_my_shipments' ) ); ?>"><?php esc_html_e( 'خروجی اکسل', 'peykherfei-shipment-tracking' ); ?></a>
				·
			<?php endif; ?>
			<a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>"><?php esc_html_e( 'خروج', 'peykherfei-shipment-tracking' ); ?></a>
		</span>
	</div>

	<h3><?php echo PKST_Icons::svg( 'box', 18 ); ?> <?php esc_html_e( 'مرسولات من', 'peykherfei-shipment-tracking' ); ?></h3>

	<?php if ( empty( $shipments ) ) : ?>
		<p class="pkst-muted"><?php esc_html_e( 'در حال حاضر مرسوله‌ای برای شما ثبت نشده است.', 'peykherfei-shipment-tracking' ); ?></p>
	<?php else : ?>
		<?php foreach ( $shipments as $shipment ) : ?>
			<?php $status_log = PKST_Shipment::get_status_log( $shipment['id'] ); ?>
			<?php include __DIR__ . '/track-result.php'; ?>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
