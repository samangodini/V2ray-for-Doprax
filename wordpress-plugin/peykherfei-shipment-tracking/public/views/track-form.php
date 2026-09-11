<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** Expects $code, $shipment, $status_log, $not_found in scope. */
?>
<div class="pkst-front pkst-track-form">
	<?php include __DIR__ . '/partial-header.php'; ?>

	<form method="get" action="<?php echo esc_url( get_permalink() ); ?>" class="pkst-inline-form">
		<input type="text" name="code" dir="ltr" placeholder="<?php esc_attr_e( 'کد رهگیری مرسوله را وارد کنید', 'peykherfei-shipment-tracking' ); ?>" value="<?php echo esc_attr( $code ); ?>" required />
		<button type="submit" class="pkst-btn pkst-btn-primary"><?php esc_html_e( 'رهگیری مرسوله', 'peykherfei-shipment-tracking' ); ?></button>
	</form>

	<?php if ( $not_found ) : ?>
		<p class="pkst-notice pkst-notice-danger"><?php esc_html_e( 'مرسوله‌ای با این کد رهگیری یافت نشد.', 'peykherfei-shipment-tracking' ); ?></p>
	<?php elseif ( $shipment ) : ?>
		<?php include __DIR__ . '/track-result.php'; ?>
	<?php endif; ?>
</div>
