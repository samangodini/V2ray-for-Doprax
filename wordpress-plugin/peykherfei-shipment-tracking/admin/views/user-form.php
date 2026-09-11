<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** Expects $edit_user (WP_User) and $reset_password (string|false) in scope. */
$role    = PKST_User_Manager::role_of( $edit_user );
$is_courier = PKST_Roles::COURIER === $role;
?>
<div class="wrap pkst-wrap" dir="rtl">
	<h1><?php echo PKST_Icons::svg( 'edit', 22 ); ?> <?php esc_html_e( 'ویرایش کاربر', 'peykherfei-shipment-tracking' ); ?></h1>
	<p class="pkst-back-link">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pkst-users' ) ); ?>"><?php echo PKST_Icons::svg( 'arrow-left', 14 ); ?> <?php esc_html_e( 'بازگشت به فهرست کاربران', 'peykherfei-shipment-tracking' ); ?></a>
	</p>
	<?php PKST_Admin::notice_from_query(); ?>

	<?php if ( ! empty( $reset_password ) ) : ?>
		<div class="notice notice-success pkst-new-user-credentials">
			<p><strong><?php esc_html_e( 'رمز عبور تغییر کرد. این رمز فقط همین یک‌بار نمایش داده می‌شود — همین حالا برای کاربر ارسال کنید:', 'peykherfei-shipment-tracking' ); ?></strong></p>
			<p><code dir="ltr"><?php echo esc_html( $reset_password ); ?></code></p>
		</div>
	<?php endif; ?>

	<div class="pkst-panel pkst-panel-form">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="pkst_update_user" />
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $edit_user->ID ); ?>" />
			<?php wp_nonce_field( 'pkst_update_user_' . $edit_user->ID ); ?>

			<table class="form-table">
				<tr>
					<th><label for="role"><?php esc_html_e( 'نوع کاربر', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td>
						<select name="role" id="pkst-user-role">
							<option value="<?php echo esc_attr( PKST_Roles::CUSTOMER ); ?>" <?php selected( PKST_Roles::CUSTOMER, $role ); ?>><?php esc_html_e( 'مشتری', 'peykherfei-shipment-tracking' ); ?></option>
							<option value="<?php echo esc_attr( PKST_Roles::COURIER ); ?>" <?php selected( PKST_Roles::COURIER, $role ); ?>><?php esc_html_e( 'پیک', 'peykherfei-shipment-tracking' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="name"><?php esc_html_e( 'نام و نام خانوادگی', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" class="regular-text" name="name" id="name" value="<?php echo esc_attr( $edit_user->display_name ); ?>" required /></td>
				</tr>
				<tr>
					<th><label for="email"><?php esc_html_e( 'ایمیل', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="email" class="regular-text" dir="ltr" name="email" id="email" value="<?php echo esc_attr( $edit_user->user_email ); ?>" required /></td>
				</tr>
				<tr>
					<th><label for="phone"><?php esc_html_e( 'شماره تماس', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" dir="ltr" class="regular-text" name="phone" id="phone" value="<?php echo esc_attr( PKST_User_Manager::get_phone( $edit_user->ID ) ); ?>" /></td>
				</tr>
				<tr id="pkst-vehicle-field" <?php echo $is_courier ? '' : 'style="display:none;"'; ?>>
					<th><label for="vehicle"><?php esc_html_e( 'نوع وسیله نقلیه', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" class="regular-text" name="vehicle" id="vehicle" value="<?php echo esc_attr( PKST_User_Manager::get_vehicle( $edit_user->ID ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="pkst-password"><?php esc_html_e( 'تغییر رمز عبور', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td>
						<span class="pkst-password-row">
							<input type="text" dir="ltr" class="regular-text" name="password" id="pkst-password" autocomplete="off" />
							<button type="button" class="button" id="pkst-generate-password"><?php esc_html_e( 'تولید خودکار', 'peykherfei-shipment-tracking' ); ?></button>
						</span>
						<p class="description"><?php esc_html_e( 'برای حفظ رمز فعلی، این فیلد را خالی بگذارید.', 'peykherfei-shipment-tracking' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="active"><?php esc_html_e( 'وضعیت حساب', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td>
						<label><input type="checkbox" name="active" id="active" value="1" <?php checked( PKST_User_Manager::is_active( $edit_user->ID ) ); ?> /> <?php esc_html_e( 'حساب فعال باشد', 'peykherfei-shipment-tracking' ); ?></label>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'ذخیره تغییرات', 'peykherfei-shipment-tracking' ) ); ?>
		</form>
	</div>
</div>
