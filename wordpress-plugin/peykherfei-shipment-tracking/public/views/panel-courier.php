<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** Expects $open_shipments, $closed_shipments, $saved in scope. */

$card = function ( $s, $with_action ) {
	?>
	<div class="pkst-shipment-card">
		<div class="pkst-shipment-card-main">
			<div class="pkst-track-result-head">
				<strong dir="ltr"><?php echo esc_html( $s['tracking_code'] ); ?></strong>
				<span class="pkst-badge <?php echo esc_attr( PKST_Status::badge_class( $s['status'] ) ); ?>"><?php echo esc_html( PKST_Status::label( $s['status'] ) ); ?></span>
			</div>
			<div><?php echo esc_html( $s['recipient_name'] ); ?> — <span dir="ltr"><?php echo esc_html( $s['recipient_phone'] ); ?></span></div>
			<div class="pkst-muted"><?php echo esc_html( $s['destination'] ); ?></div>
		</div>
		<?php if ( $with_action ) : ?>
			<a class="pkst-btn pkst-btn-primary" href="<?php echo esc_url( add_query_arg( array( 'pkst_action' => 'update', 'shipment_id' => $s['id'] ), get_permalink() ) ); ?>"><?php esc_html_e( 'به‌روزرسانی وضعیت', 'peykherfei-shipment-tracking' ); ?></a>
		<?php endif; ?>
	</div>
	<?php
};
?>
<div class="pkst-front pkst-panel-courier">
	<?php include __DIR__ . '/partial-header.php'; ?>

	<div class="pkst-front-header">
		<span><?php echo esc_html( sprintf( __( 'خوش آمدید، %s', 'peykherfei-shipment-tracking' ), wp_get_current_user()->display_name ) ); ?></span>
		<a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>"><?php esc_html_e( 'خروج', 'peykherfei-shipment-tracking' ); ?></a>
	</div>

	<?php if ( $saved ) : ?>
		<p class="pkst-notice pkst-notice-success"><?php esc_html_e( 'وضعیت مرسوله با موفقیت ثبت شد.', 'peykherfei-shipment-tracking' ); ?></p>
	<?php endif; ?>

	<h3><?php echo PKST_Icons::svg( 'truck', 18 ); ?> <?php esc_html_e( 'مرسولات در حال انجام', 'peykherfei-shipment-tracking' ); ?></h3>
	<?php if ( empty( $open_shipments ) ) : ?>
		<p class="pkst-muted"><?php esc_html_e( 'در حال حاضر مرسوله بازی برای شما تخصیص داده نشده است.', 'peykherfei-shipment-tracking' ); ?></p>
	<?php else : ?>
		<?php foreach ( $open_shipments as $s ) : $card( $s, true ); endforeach; ?>
	<?php endif; ?>

	<?php if ( ! empty( $closed_shipments ) ) : ?>
		<h3><?php echo PKST_Icons::svg( 'clock', 18 ); ?> <?php esc_html_e( 'تاریخچه اخیر', 'peykherfei-shipment-tracking' ); ?></h3>
		<?php foreach ( $closed_shipments as $s ) : $card( $s, false ); endforeach; ?>
	<?php endif; ?>
</div>
