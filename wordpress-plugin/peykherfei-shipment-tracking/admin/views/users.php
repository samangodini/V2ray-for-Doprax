<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap pkst-wrap" dir="rtl">
	<h1><?php esc_html_e( 'کاربران و پیک‌ها', 'peykherfei-shipment-tracking' ); ?></h1>
	<?php PKST_Admin::notice_from_query(); ?>

	<?php if ( ! empty( $new_user_credentials ) ) : ?>
		<div class="notice notice-success pkst-new-user-credentials">
			<p><strong><?php esc_html_e( 'کاربر ایجاد شد. این اطلاعات فقط همین یک‌بار نمایش داده می‌شود — همین حالا برای کاربر ارسال کنید:', 'peykherfei-shipment-tracking' ); ?></strong></p>
			<p>
				<?php esc_html_e( 'نام کاربری:', 'peykherfei-shipment-tracking' ); ?>
				<code dir="ltr"><?php echo esc_html( $new_user_credentials['username'] ); ?></code>
				&nbsp;&nbsp;
				<?php esc_html_e( 'رمز عبور:', 'peykherfei-shipment-tracking' ); ?>
				<code dir="ltr"><?php echo esc_html( $new_user_credentials['password'] ); ?></code>
			</p>
		</div>
	<?php endif; ?>

	<div class="pkst-columns">
		<div class="pkst-col-main">

			<div class="pkst-panel">
				<h2><?php esc_html_e( 'پیک‌ها', 'peykherfei-shipment-tracking' ); ?></h2>
				<?php if ( empty( $couriers ) ) : ?>
					<p class="description"><?php esc_html_e( 'هنوز پیکی ثبت نشده است.', 'peykherfei-shipment-tracking' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead><tr>
							<th><?php esc_html_e( 'نام', 'peykherfei-shipment-tracking' ); ?></th>
							<th><?php esc_html_e( 'ایمیل', 'peykherfei-shipment-tracking' ); ?></th>
							<th><?php esc_html_e( 'تلفن', 'peykherfei-shipment-tracking' ); ?></th>
							<th><?php esc_html_e( 'مرسولات باز', 'peykherfei-shipment-tracking' ); ?></th>
							<th><?php esc_html_e( 'وضعیت', 'peykherfei-shipment-tracking' ); ?></th>
							<th></th>
						</tr></thead>
						<tbody>
						<?php foreach ( $couriers as $courier ) : ?>
							<?php
							$open = PKST_Shipment::query(
								array(
									'courier_id' => $courier->ID,
									'per_page'   => 1,
								)
							);
							$active = PKST_User_Manager::is_active( $courier->ID );
							$toggle_url = wp_nonce_url(
								add_query_arg(
									array(
										'action'  => 'pkst_toggle_user_active',
										'user_id' => $courier->ID,
									),
									admin_url( 'admin-post.php' )
								),
								'pkst_toggle_active_' . $courier->ID
							);
							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'action'  => 'pkst_delete_user',
										'user_id' => $courier->ID,
									),
									admin_url( 'admin-post.php' )
								),
								'pkst_delete_user_' . $courier->ID
							);
							?>
							<tr>
								<td><?php echo esc_html( $courier->display_name ); ?></td>
								<td><?php echo esc_html( $courier->user_email ); ?></td>
								<td dir="ltr"><?php echo esc_html( PKST_User_Manager::get_phone( $courier->ID ) ); ?></td>
								<td><?php echo esc_html( $open['total'] ); ?></td>
								<td><?php echo $active ? '<span class="pkst-badge pkst-badge-success">' . esc_html__( 'فعال', 'peykherfei-shipment-tracking' ) . '</span>' : '<span class="pkst-badge pkst-badge-neutral">' . esc_html__( 'غیرفعال', 'peykherfei-shipment-tracking' ) . '</span>'; ?></td>
								<td>
									<a class="button button-small" href="<?php echo esc_url( $toggle_url ); ?>"><?php echo $active ? esc_html__( 'غیرفعال کردن', 'peykherfei-shipment-tracking' ) : esc_html__( 'فعال کردن', 'peykherfei-shipment-tracking' ); ?></a>
									<a class="button button-small pkst-btn-delete" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'این پیک برای همیشه حذف شود؟', 'peykherfei-shipment-tracking' ) ); ?>');"><?php esc_html_e( 'حذف', 'peykherfei-shipment-tracking' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<div class="pkst-panel">
				<h2><?php esc_html_e( 'مشتریان', 'peykherfei-shipment-tracking' ); ?></h2>
				<?php if ( empty( $customers ) ) : ?>
					<p class="description"><?php esc_html_e( 'هنوز مشتری‌ای ثبت نشده است.', 'peykherfei-shipment-tracking' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead><tr>
							<th><?php esc_html_e( 'نام', 'peykherfei-shipment-tracking' ); ?></th>
							<th><?php esc_html_e( 'ایمیل', 'peykherfei-shipment-tracking' ); ?></th>
							<th><?php esc_html_e( 'تلفن', 'peykherfei-shipment-tracking' ); ?></th>
							<th></th>
						</tr></thead>
						<tbody>
						<?php foreach ( $customers as $customer ) : ?>
							<?php
							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'action'  => 'pkst_delete_user',
										'user_id' => $customer->ID,
									),
									admin_url( 'admin-post.php' )
								),
								'pkst_delete_user_' . $customer->ID
							);
							?>
							<tr>
								<td><?php echo esc_html( $customer->display_name ); ?></td>
								<td><?php echo esc_html( $customer->user_email ); ?></td>
								<td dir="ltr"><?php echo esc_html( PKST_User_Manager::get_phone( $customer->ID ) ); ?></td>
								<td><a class="button button-small pkst-btn-delete" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'این مشتری برای همیشه حذف شود؟', 'peykherfei-shipment-tracking' ) ); ?>');"><?php esc_html_e( 'حذف', 'peykherfei-shipment-tracking' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<div class="pkst-col-side">
			<div class="pkst-panel">
				<h2><?php esc_html_e( 'ایجاد کاربر جدید', 'peykherfei-shipment-tracking' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="pkst_create_user" />
					<?php wp_nonce_field( 'pkst_create_user' ); ?>

					<p>
						<label for="role"><?php esc_html_e( 'نوع کاربر', 'peykherfei-shipment-tracking' ); ?></label><br>
						<select name="role" id="pkst-user-role" class="widefat">
							<option value="<?php echo esc_attr( PKST_Roles::CUSTOMER ); ?>"><?php esc_html_e( 'مشتری', 'peykherfei-shipment-tracking' ); ?></option>
							<option value="<?php echo esc_attr( PKST_Roles::COURIER ); ?>"><?php esc_html_e( 'پیک', 'peykherfei-shipment-tracking' ); ?></option>
						</select>
					</p>
					<p>
						<label for="name"><?php esc_html_e( 'نام و نام خانوادگی', 'peykherfei-shipment-tracking' ); ?></label>
						<input type="text" class="widefat" name="name" id="name" required />
					</p>
					<p>
						<label for="email"><?php esc_html_e( 'ایمیل', 'peykherfei-shipment-tracking' ); ?></label>
						<input type="email" class="widefat" name="email" id="email" required />
					</p>
					<p>
						<label for="phone"><?php esc_html_e( 'شماره تماس', 'peykherfei-shipment-tracking' ); ?></label>
						<input type="text" dir="ltr" class="widefat" name="phone" id="phone" />
					</p>
					<p>
						<label for="pkst-password"><?php esc_html_e( 'رمز عبور', 'peykherfei-shipment-tracking' ); ?></label>
						<span class="pkst-password-row">
							<input type="text" dir="ltr" class="widefat" name="password" id="pkst-password" autocomplete="off" />
							<button type="button" class="button" id="pkst-generate-password"><?php esc_html_e( 'تولید خودکار', 'peykherfei-shipment-tracking' ); ?></button>
						</span>
						<span class="description"><?php esc_html_e( 'خالی بگذارید تا خودکار ساخته شود.', 'peykherfei-shipment-tracking' ); ?></span>
					</p>
					<p id="pkst-vehicle-field">
						<label for="vehicle"><?php esc_html_e( 'نوع وسیله نقلیه', 'peykherfei-shipment-tracking' ); ?></label>
						<input type="text" class="widefat" name="vehicle" id="vehicle" />
					</p>
					<p>
						<label><input type="checkbox" name="notify" value="1" checked /> <?php esc_html_e( 'ارسال ایمیل اطلاعات ورود به کاربر', 'peykherfei-shipment-tracking' ); ?></label>
					</p>

					<?php submit_button( __( 'ایجاد کاربر', 'peykherfei-shipment-tracking' ) ); ?>
				</form>
			</div>
		</div>
	</div>
</div>
