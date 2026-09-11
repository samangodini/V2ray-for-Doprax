<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** Expects $error (bool) in scope. */
?>
<div class="pkst-front pkst-login">
	<?php include __DIR__ . '/partial-header.php'; ?>

	<h3><?php esc_html_e( 'ورود به پنل کاربری', 'peykherfei-shipment-tracking' ); ?></h3>

	<?php if ( $error ) : ?>
		<p class="pkst-notice pkst-notice-danger"><?php esc_html_e( 'نام کاربری یا رمز عبور اشتباه است.', 'peykherfei-shipment-tracking' ); ?></p>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( get_permalink() ); ?>" class="pkst-form">
		<input type="hidden" name="pkst_login_action" value="1" />
		<?php wp_nonce_field( 'pkst_login', 'pkst_login_nonce' ); ?>

		<p>
			<label for="pkst-log"><?php esc_html_e( 'نام کاربری یا ایمیل', 'peykherfei-shipment-tracking' ); ?></label>
			<input type="text" name="log" id="pkst-log" required />
		</p>
		<p>
			<label for="pkst-pwd"><?php esc_html_e( 'رمز عبور', 'peykherfei-shipment-tracking' ); ?></label>
			<input type="password" name="pwd" id="pkst-pwd" required />
		</p>
		<button type="submit" class="pkst-btn pkst-btn-primary"><?php esc_html_e( 'ورود', 'peykherfei-shipment-tracking' ); ?></button>
	</form>

	<p class="pkst-muted"><?php esc_html_e( 'اطلاعات ورود توسط مدیر سامانه برای شما ایجاد و ارسال شده است.', 'peykherfei-shipment-tracking' ); ?></p>
</div>
