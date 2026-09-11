<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
$tabs = array(
	'general'   => __( 'عمومی', 'peykherfei-shipment-tracking' ),
	'sms'       => __( 'پیامک', 'peykherfei-shipment-tracking' ),
	'templates' => __( 'متن پیامک‌ها', 'peykherfei-shipment-tracking' ),
	'api'       => __( 'وب‌سرویس API', 'peykherfei-shipment-tracking' ),
);
$g = function ( $key, $default = '' ) use ( $settings ) {
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
};
?>
<div class="wrap pkst-wrap" dir="rtl">
	<h1><?php esc_html_e( 'تنظیمات سامانه مرسولات', 'peykherfei-shipment-tracking' ); ?></h1>
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
					<th><label for="track_page_url"><?php esc_html_e( 'آدرس صفحه رهگیری عمومی', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td>
						<input type="url" dir="ltr" class="regular-text" name="track_page_url" id="track_page_url" value="<?php echo esc_attr( $g( 'track_page_url' ) ); ?>" placeholder="https://peykherfei.com/tracking/" />
						<p class="description"><?php esc_html_e( 'آدرس صفحه‌ای که شورت‌کد [pkst_track] در آن قرار دارد؛ برای درج لینک رهگیری در پیامک‌ها ({tracking_url}) استفاده می‌شود.', 'peykherfei-shipment-tracking' ); ?></p>
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

	<?php elseif ( 'sms' === $tab ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pkst-form">
			<input type="hidden" name="action" value="pkst_save_settings" />
			<?php wp_nonce_field( 'pkst_save_settings' ); ?>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'فعال‌سازی پیامک', 'peykherfei-shipment-tracking' ); ?></th>
					<td><label><input type="checkbox" name="sms_enabled" value="1" <?php checked( '1', $g( 'sms_enabled', '1' ) ); ?> /> <?php esc_html_e( 'ارسال خودکار پیامک در مراحل مرسوله فعال باشد', 'peykherfei-shipment-tracking' ); ?></label></td>
				</tr>
				<tr>
					<th><label for="sms_gateway"><?php esc_html_e( 'سرویس‌دهنده پیامک', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td>
						<select name="sms_gateway" id="sms_gateway">
							<?php foreach ( $gateways as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $g( 'sms_gateway' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="sms_sender_number"><?php esc_html_e( 'شماره/خط ارسال‌کننده', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" dir="ltr" class="regular-text" name="sms_sender_number" id="sms_sender_number" value="<?php echo esc_attr( $g( 'sms_sender_number' ) ); ?>" /></td>
				</tr>
			</table>

			<div class="pkst-gateway-fields" data-gateway="kavenegar">
				<h3><?php esc_html_e( 'کاوه‌نگار', 'peykherfei-shipment-tracking' ); ?></h3>
				<table class="form-table">
					<tr><th><label for="kavenegar_api_key"><?php esc_html_e( 'کلید API', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" dir="ltr" class="regular-text" name="kavenegar_api_key" id="kavenegar_api_key" value="<?php echo esc_attr( $g( 'kavenegar_api_key' ) ); ?>" /></td></tr>
				</table>
			</div>
			<div class="pkst-gateway-fields" data-gateway="melipayamak">
				<h3><?php esc_html_e( 'ملی‌پیامک', 'peykherfei-shipment-tracking' ); ?></h3>
				<table class="form-table">
					<tr><th><label for="melipayamak_api_key"><?php esc_html_e( 'کلید API', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" dir="ltr" class="regular-text" name="melipayamak_api_key" id="melipayamak_api_key" value="<?php echo esc_attr( $g( 'melipayamak_api_key' ) ); ?>" /></td></tr>
				</table>
			</div>
			<div class="pkst-gateway-fields" data-gateway="ippanel">
				<h3><?php esc_html_e( 'آی‌پی‌پنل', 'peykherfei-shipment-tracking' ); ?></h3>
				<table class="form-table">
					<tr><th><label for="ippanel_api_key"><?php esc_html_e( 'کلید API', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" dir="ltr" class="regular-text" name="ippanel_api_key" id="ippanel_api_key" value="<?php echo esc_attr( $g( 'ippanel_api_key' ) ); ?>" /></td></tr>
				</table>
			</div>
			<div class="pkst-gateway-fields" data-gateway="smsir">
				<h3><?php esc_html_e( 'sms.ir', 'peykherfei-shipment-tracking' ); ?></h3>
				<table class="form-table">
					<tr><th><label for="smsir_api_key"><?php esc_html_e( 'کلید API', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" dir="ltr" class="regular-text" name="smsir_api_key" id="smsir_api_key" value="<?php echo esc_attr( $g( 'smsir_api_key' ) ); ?>" /></td></tr>
					<tr><th><label for="smsir_line_number"><?php esc_html_e( 'شماره خط', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" dir="ltr" class="regular-text" name="smsir_line_number" id="smsir_line_number" value="<?php echo esc_attr( $g( 'smsir_line_number' ) ); ?>" /></td></tr>
				</table>
			</div>
			<div class="pkst-gateway-fields" data-gateway="custom">
				<h3><?php esc_html_e( 'وب‌سرویس سفارشی', 'peykherfei-shipment-tracking' ); ?></h3>
				<p class="description"><?php esc_html_e( 'برای اتصال به هر پنل پیامکی دیگر. از {phone} و {message} به‌عنوان جای‌گزین شماره و متن پیامک استفاده کنید.', 'peykherfei-shipment-tracking' ); ?></p>
				<table class="form-table">
					<tr><th><label for="custom_gateway_url"><?php esc_html_e( 'آدرس (URL)', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><input type="text" dir="ltr" class="large-text" name="custom_gateway_url" id="custom_gateway_url" value="<?php echo esc_attr( $g( 'custom_gateway_url' ) ); ?>" /></td></tr>
					<tr><th><label for="custom_gateway_method"><?php esc_html_e( 'متد', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td>
						<select name="custom_gateway_method" id="custom_gateway_method">
							<option value="POST" <?php selected( $g( 'custom_gateway_method', 'POST' ), 'POST' ); ?>>POST</option>
							<option value="GET" <?php selected( $g( 'custom_gateway_method' ), 'GET' ); ?>>GET</option>
						</select>
					</td></tr>
					<tr><th><label for="custom_gateway_headers"><?php esc_html_e( 'هدرها (هر خط یک مورد)', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><textarea dir="ltr" class="large-text code" rows="3" name="custom_gateway_headers" id="custom_gateway_headers" placeholder="Authorization: Bearer xxxx"><?php echo esc_textarea( $g( 'custom_gateway_headers' ) ); ?></textarea></td></tr>
					<tr><th><label for="custom_gateway_body"><?php esc_html_e( 'قالب بدنه درخواست (JSON)', 'peykherfei-shipment-tracking' ); ?></label></th>
					<td><textarea dir="ltr" class="large-text code" rows="3" name="custom_gateway_body" id="custom_gateway_body"><?php echo esc_textarea( $g( 'custom_gateway_body' ) ); ?></textarea></td></tr>
				</table>
			</div>

			<?php submit_button(); ?>
		</form>

		<div class="pkst-panel">
			<h2><?php esc_html_e( 'تست اتصال پیامک', 'peykherfei-shipment-tracking' ); ?></h2>
			<p>
				<input type="text" dir="ltr" id="pkst-test-phone" class="regular-text" placeholder="09xxxxxxxxx" />
				<button type="button" class="button button-secondary" id="pkst-test-sms-btn"><?php esc_html_e( 'ارسال پیامک آزمایشی', 'peykherfei-shipment-tracking' ); ?></button>
			</p>
			<div id="pkst-test-sms-result"></div>
		</div>

	<?php elseif ( 'templates' === $tab ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pkst-form">
			<input type="hidden" name="action" value="pkst_save_templates" />
			<?php wp_nonce_field( 'pkst_save_templates' ); ?>

			<p class="description">
				<?php esc_html_e( 'جای‌گزین‌های قابل استفاده:', 'peykherfei-shipment-tracking' ); ?>
				<code dir="ltr">{tracking_code}</code>, <code dir="ltr">{recipient_name}</code>, <code dir="ltr">{destination}</code>, <code dir="ltr">{company_name}</code>, <code dir="ltr">{failure_reason}</code>, <code dir="ltr">{status_text}</code>, <code dir="ltr">{tracking_url}</code>
			</p>
			<p class="description"><?php esc_html_e( 'می‌توانید متن‌های مورد تأیید شرکت را عیناً در این فیلدها وارد کنید.', 'peykherfei-shipment-tracking' ); ?></p>

			<?php foreach ( PKST_Status::all() as $status ) : ?>
				<?php $tpl = isset( $templates[ $status ] ) ? $templates[ $status ] : array( 'enabled' => '0', 'text' => '' ); ?>
				<div class="pkst-panel">
					<h3>
						<label>
							<input type="checkbox" name="tpl_enabled_<?php echo esc_attr( $status ); ?>" value="1" <?php checked( '1', $tpl['enabled'] ); ?> />
							<?php echo esc_html( PKST_Status::label( $status ) ); ?>
						</label>
					</h3>
					<textarea class="large-text" rows="2" name="tpl_text_<?php echo esc_attr( $status ); ?>"><?php echo esc_textarea( $tpl['text'] ); ?></textarea>
				</div>
			<?php endforeach; ?>

			<?php submit_button( __( 'ذخیره متن پیامک‌ها', 'peykherfei-shipment-tracking' ) ); ?>
		</form>

	<?php else : ?>
		<div class="pkst-panel">
			<h2><?php esc_html_e( 'اتصال به سامانه‌های خارجی', 'peykherfei-shipment-tracking' ); ?></h2>
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
