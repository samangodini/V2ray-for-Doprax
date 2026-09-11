<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** Expects $addresses, $saved, $deleted, $error, $error_msg in scope. */
?>
<div class="pkst-front pkst-panel-addresses">
	<?php include __DIR__ . '/partial-header.php'; ?>

	<div class="pkst-front-header">
		<span><?php echo esc_html( sprintf( __( 'خوش آمدید، %s', 'peykherfei-shipment-tracking' ), wp_get_current_user()->display_name ) ); ?></span>
		<a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>"><?php esc_html_e( 'خروج', 'peykherfei-shipment-tracking' ); ?></a>
	</div>

	<p class="pkst-back-link"><a href="<?php echo esc_url( remove_query_arg( array( 'pkst_action', 'pkst_notice', 'pkst_msg' ) ) ); ?>">&rarr; <?php esc_html_e( 'بازگشت به پنل', 'peykherfei-shipment-tracking' ); ?></a></p>

	<?php if ( $saved ) : ?>
		<p class="pkst-notice pkst-notice-success"><?php esc_html_e( 'آدرس با موفقیت ذخیره شد.', 'peykherfei-shipment-tracking' ); ?></p>
	<?php elseif ( $deleted ) : ?>
		<p class="pkst-notice pkst-notice-success"><?php esc_html_e( 'آدرس حذف شد.', 'peykherfei-shipment-tracking' ); ?></p>
	<?php elseif ( $error ) : ?>
		<p class="pkst-notice pkst-notice-danger"><?php echo esc_html( $error_msg ? $error_msg : __( 'خطایی رخ داد.', 'peykherfei-shipment-tracking' ) ); ?></p>
	<?php endif; ?>

	<h3><?php esc_html_e( 'آدرس‌های ذخیره‌شده من', 'peykherfei-shipment-tracking' ); ?></h3>

	<?php if ( empty( $addresses ) ) : ?>
		<p class="pkst-muted"><?php esc_html_e( 'هنوز آدرسی ذخیره نکرده‌اید.', 'peykherfei-shipment-tracking' ); ?></p>
	<?php else : ?>
		<?php foreach ( $addresses as $addr ) : ?>
			<?php
			$delete_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => 'pkst_delete_address',
						'address_id' => $addr['id'],
					),
					admin_url( 'admin-post.php' )
				),
				'pkst_delete_address_' . $addr['id']
			);
			?>
			<div class="pkst-shipment-card">
				<div class="pkst-shipment-card-main">
					<strong><?php echo esc_html( $addr['label'] ); ?></strong>
					<div class="pkst-muted"><?php echo esc_html( $addr['address_text'] ); ?></div>
				</div>
				<a class="pkst-btn" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'این آدرس حذف شود؟', 'peykherfei-shipment-tracking' ) ); ?>');"><?php esc_html_e( 'حذف', 'peykherfei-shipment-tracking' ); ?></a>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>

	<h3><?php esc_html_e( 'افزودن آدرس جدید', 'peykherfei-shipment-tracking' ); ?></h3>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pkst-form">
		<input type="hidden" name="action" value="pkst_save_address" />
		<?php wp_nonce_field( 'pkst_save_address' ); ?>

		<p>
			<label for="label"><?php esc_html_e( 'عنوان (مثلاً خانه، محل کار)', 'peykherfei-shipment-tracking' ); ?></label>
			<input type="text" name="label" id="label" required />
		</p>
		<p>
			<label for="address_text"><?php esc_html_e( 'آدرس', 'peykherfei-shipment-tracking' ); ?></label>
			<textarea name="address_text" id="address_text" rows="2" required></textarea>
		</p>

		<?php
		echo PKST_Map::render_picker( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- builds its own escaped markup.
			array(
				'lat_field'     => 'lat',
				'lng_field'     => 'lng',
				'address_field' => 'address_text',
			)
		);
		?>

		<p style="margin-top: 16px;">
			<button type="submit" class="pkst-btn pkst-btn-primary"><?php esc_html_e( 'ذخیره آدرس', 'peykherfei-shipment-tracking' ); ?></button>
		</p>
	</form>
</div>
