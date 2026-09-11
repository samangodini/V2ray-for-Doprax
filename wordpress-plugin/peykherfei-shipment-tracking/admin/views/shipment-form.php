<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$is_edit = ! empty( $shipment );
$v       = function ( $key, $default = '' ) use ( $shipment ) {
	return $shipment[ $key ] ?? $default;
};
$handed_value = $is_edit && $shipment['handed_to_courier_at'] ? mysql2date( 'Y-m-d\TH:i', $shipment['handed_to_courier_at'] ) : '';
?>
<div class="wrap pkst-wrap" dir="rtl">
	<h1><?php echo $is_edit ? esc_html__( 'ویرایش مرسوله', 'peykherfei-shipment-tracking' ) : esc_html__( 'افزودن مرسوله جدید', 'peykherfei-shipment-tracking' ); ?></h1>
	<?php PKST_Admin::notice_from_query(); ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pkst-form">
		<input type="hidden" name="action" value="pkst_save_shipment" />
		<input type="hidden" name="id" value="<?php echo esc_attr( $is_edit ? $shipment['id'] : 0 ); ?>" />
		<?php wp_nonce_field( 'pkst_save_shipment' ); ?>

		<?php if ( $is_edit ) : ?>
			<p><strong><?php esc_html_e( 'کد رهگیری:', 'peykherfei-shipment-tracking' ); ?></strong> <code><?php echo esc_html( $shipment['tracking_code'] ); ?></code></p>
		<?php endif; ?>

		<table class="form-table">
			<tr>
				<th><label for="recipient_name"><?php esc_html_e( 'نام گیرنده', 'peykherfei-shipment-tracking' ); ?> *</label></th>
				<td><input type="text" class="regular-text" id="recipient_name" name="recipient_name" value="<?php echo esc_attr( $v( 'recipient_name' ) ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="recipient_phone"><?php esc_html_e( 'شماره تماس گیرنده', 'peykherfei-shipment-tracking' ); ?> *</label></th>
				<td><input type="text" dir="ltr" class="regular-text" id="recipient_phone" name="recipient_phone" value="<?php echo esc_attr( $v( 'recipient_phone' ) ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="destination"><?php esc_html_e( 'مقصد', 'peykherfei-shipment-tracking' ); ?> *</label></th>
				<td><textarea id="destination" name="destination" class="large-text" rows="2" required><?php echo esc_textarea( $v( 'destination' ) ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="origin"><?php esc_html_e( 'مبدأ', 'peykherfei-shipment-tracking' ); ?></label></th>
				<td><input type="text" class="regular-text" id="origin" name="origin" value="<?php echo esc_attr( $v( 'origin' ) ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="description"><?php esc_html_e( 'شرح مرسوله', 'peykherfei-shipment-tracking' ); ?></label></th>
				<td><input type="text" class="regular-text" id="description" name="description" value="<?php echo esc_attr( $v( 'description' ) ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="sender_name"><?php esc_html_e( 'نام فرستنده', 'peykherfei-shipment-tracking' ); ?></label></th>
				<td><input type="text" class="regular-text" id="sender_name" name="sender_name" value="<?php echo esc_attr( $v( 'sender_name' ) ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="sender_phone"><?php esc_html_e( 'شماره تماس فرستنده', 'peykherfei-shipment-tracking' ); ?></label></th>
				<td><input type="text" dir="ltr" class="regular-text" id="sender_phone" name="sender_phone" value="<?php echo esc_attr( $v( 'sender_phone' ) ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="courier_id"><?php esc_html_e( 'پیک', 'peykherfei-shipment-tracking' ); ?></label></th>
				<td>
					<select id="courier_id" name="courier_id">
						<option value="0"><?php esc_html_e( '— تخصیص بعداً —', 'peykherfei-shipment-tracking' ); ?></option>
						<?php foreach ( $couriers as $courier ) : ?>
							<option value="<?php echo esc_attr( $courier->ID ); ?>" <?php selected( (int) $v( 'courier_id' ), $courier->ID ); ?>><?php echo esc_html( $courier->display_name ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( empty( $couriers ) ) : ?>
						<p class="description"><?php esc_html_e( 'هنوز پیکی ثبت نشده است.', 'peykherfei-shipment-tracking' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=pkst-users' ) ); ?>"><?php esc_html_e( 'افزودن پیک', 'peykherfei-shipment-tracking' ); ?></a></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="customer_user_id"><?php esc_html_e( 'حساب مشتری مرتبط', 'peykherfei-shipment-tracking' ); ?></label></th>
				<td>
					<select id="customer_user_id" name="customer_user_id">
						<option value="0"><?php esc_html_e( '— بدون حساب کاربری —', 'peykherfei-shipment-tracking' ); ?></option>
						<?php foreach ( $customers as $customer ) : ?>
							<option value="<?php echo esc_attr( $customer->ID ); ?>" <?php selected( (int) $v( 'customer_user_id' ), $customer->ID ); ?>><?php echo esc_html( $customer->display_name . ' (' . $customer->user_email . ')' ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'در صورت عدم انتخاب، مشتری در صورت داشتن حساب با همین شماره تماس، مرسوله را در پنل خود می‌بیند.', 'peykherfei-shipment-tracking' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="handed_to_courier_at"><?php esc_html_e( 'تاریخ و ساعت تحویل به پیک', 'peykherfei-shipment-tracking' ); ?></label></th>
				<td><input type="datetime-local" id="handed_to_courier_at" name="handed_to_courier_at" value="<?php echo esc_attr( $handed_value ); ?>" /></td>
			</tr>
		</table>

		<?php submit_button( $is_edit ? __( 'به‌روزرسانی مرسوله', 'peykherfei-shipment-tracking' ) : __( 'ثبت مرسوله', 'peykherfei-shipment-tracking' ) ); ?>
	</form>
</div>
