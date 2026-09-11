<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
$tabs = array(
	'general' => __( 'عمومی', 'peykherfei-shipment-tracking' ),
	'api'     => __( 'وب‌سرویس API', 'peykherfei-shipment-tracking' ),
);
$g = function ( $key, $default = '' ) use ( $settings ) {
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
};
?>
<div class="wrap pkst-wrap" dir="rtl">
	<h1><?php echo PKST_Icons::svg( 'settings', 22 ); ?> <?php esc_html_e( 'تنظیمات سامانه مرسولات', 'peykherfei-shipment-tracking' ); ?></h1>
	<?php PKST_Admin::notice_from_query(); ?>

	<h2 class="nav-tab-wrapper">
		<?php foreach ( $tabs as $key => $label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pkst-settings', 'tab' => $key ), admin_url( 'admin.php' ) ) ); ?>" class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</h2>

	<?php if ( 'general' === $tab ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pkst-form">
			<input type="hidden" name="action" value="pkst_save_settings" />
			<?php wp_nonce_field( 'pkst_save_settings' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="company_name"><?php esc_html_e( 'نام شرکت', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" class="regular-text" name="company_name" id="company_name" value="<?php echo esc_attr( $g( 'company_name' ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="tracking_code_prefix"><?php esc_html_e( 'پیشوند کد رهگیری', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" dir="ltr" class="small-text" name="tracking_code_prefix" id="tracking_code_prefix" value="<?php echo esc_attr( $g( 'tracking_code_prefix' ) ); ?>" maxlength="6" /></td>
				</tr>
				<tr>
					<th><label for="overdue_hours"><?php esc_html_e( 'آستانه معوق‌شدن (ساعت)', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="number" min="1" class="small-text" name="overdue_hours" id="overdue_hours" value="<?php echo esc_attr( $g( 'overdue_hours' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'مرسولاتی که بیش از این مدت تحویل نشده باشند، در داشبورد «معوق» شمرده می‌شوند.', 'peykherfei-shipment-tracking' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'حذف اطلاعات هنگام حذف افزونه', 'peykherfei-shipment-tracking' ); ?></th>
					<td>
						<label><input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( '1', $g( 'delete_data_on_uninstall', '0' ) ); ?> /> <?php esc_html_e( 'در صورت حذف کامل افزونه از وردپرس، تمام مرسولات، تنظیمات و فایل‌های POD نیز حذف شوند', 'peykherfei-shipment-tracking' ); ?></label>
						<p class="description"><?php esc_html_e( 'در صورت عدم انتخاب (پیشنهادی)، اطلاعات حتی پس از حذف افزونه نگه‌داری می‌شود.', 'peykherfei-shipment-tracking' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

	<?php else : ?>
		<div class="pkst-panel">
			<h2><?php echo PKST_Icons::svg( 'settings', 18 ); ?> <?php esc_html_e( 'اتصال به سامانه‌های خارجی', 'peykherfei-shipment-tracking' ); ?></h2>
			<p><?php esc_html_e( 'برای اتصال سامانه‌های داخلی (مانند نرم‌افزار انبار یا فروش) جهت ثبت و به‌روزرسانی خودکار مرسولات از این وب‌سرویس استفاده کنید.', 'peykherfei-shipment-tracking' ); ?></p>

			<table class="widefat striped pkst-kv">
				<tr><th><?php esc_html_e( 'آدرس پایه REST API', 'peykherfei-shipment-tracking' ); ?></th><td><code dir="ltr"><?php echo esc_html( rest_url( 'pkst/v1' ) ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'کلید API', 'peykherfei-shipment-tracking' ); ?></th><td><code dir="ltr"><?php echo esc_html( $api_key ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'هدر احراز هویت', 'peykherfei-shipment-tracking' ); ?></th><td><code dir="ltr">X-PKST-API-Key: <?php echo esc_html( $api_key ); ?></code></td></tr>
			</table>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'کلید فعلی غیرفعال شده و سامانه‌های متصل باید کلید جدید را دریافت کنند. ادامه می‌دهید؟', 'peykherfei-shipment-tracking' ) ); ?>');">
				<input type="hidden" name="action" value="pkst_regenerate_api_key" />
				<?php wp_nonce_field( 'pkst_regenerate_api_key' ); ?>
				<?php submit_button( __( 'صدور کلید جدید', 'peykherfei-shipment-tracking' ), 'delete', 'submit', false ); ?>
			</form>

			<h3><?php esc_html_e( 'نقاط سرویس (Endpoints)', 'peykherfei-shipment-tracking' ); ?></h3>
			<ul class="pkst-endpoint-list">
				<li><code dir="ltr">GET /track/{tracking_code}</code> — <?php esc_html_e( 'رهگیری عمومی (بدون نیاز به کلید)', 'peykherfei-shipment-tracking' ); ?></li>
				<li><code dir="ltr">POST /shipments</code> — <?php esc_html_e( 'ثبت مرسوله جدید', 'peykherfei-shipment-tracking' ); ?></li>
				<li><code dir="ltr">GET /shipments/{id}</code> — <?php esc_html_e( 'دریافت اطلاعات مرسوله', 'peykherfei-shipment-tracking' ); ?></li>
				<li><code dir="ltr">POST /shipments/{id}/status</code> — <?php esc_html_e( 'به‌روزرسانی وضعیت', 'peykherfei-shipment-tracking' ); ?></li>
			</ul>
		</div>
	<?php endif; ?>
</div>
