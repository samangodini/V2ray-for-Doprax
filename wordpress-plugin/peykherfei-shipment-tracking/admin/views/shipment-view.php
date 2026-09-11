<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$labels = PKST_Status::labels();
$courier = $shipment['courier_id'] ? get_userdata( $shipment['courier_id'] ) : null;
$customer = $shipment['customer_user_id'] ? get_userdata( $shipment['customer_user_id'] ) : null;
?>
<div class="wrap pkst-wrap" dir="rtl">
	<h1 class="wp-heading-inline">
		<?php
		/* translators: %s: shipment tracking code */
		echo esc_html( sprintf( __( 'مرسوله %s', 'peykherfei-shipment-tracking' ), $shipment['tracking_code'] ) );
		?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=pkst-shipment-add&id=' . $shipment['id'] ) ); ?>" class="page-title-action"><?php esc_html_e( 'ویرایش اطلاعات', 'peykherfei-shipment-tracking' ); ?></a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=pkst-shipments' ) ); ?>" class="page-title-action"><?php esc_html_e( 'بازگشت به لیست', 'peykherfei-shipment-tracking' ); ?></a>
	<hr class="wp-header-end">

	<?php PKST_Admin::notice_from_query(); ?>

	<div class="pkst-columns">
		<div class="pkst-col-main">

			<div class="pkst-panel">
				<h2><?php esc_html_e( 'اطلاعات مرسوله', 'peykherfei-shipment-tracking' ); ?>
					<span class="pkst-badge <?php echo esc_attr( PKST_Status::badge_class( $shipment['status'] ) ); ?>"><?php echo esc_html( PKST_Status::label( $shipment['status'] ) ); ?></span>
				</h2>
				<table class="widefat striped pkst-kv">
					<tr><th><?php esc_html_e( 'گیرنده', 'peykherfei-shipment-tracking' ); ?></th><td><?php echo esc_html( $shipment['recipient_name'] ); ?> — <span dir="ltr"><?php echo esc_html( $shipment['recipient_phone'] ); ?></span></td></tr>
					<tr><th><?php esc_html_e( 'مقصد', 'peykherfei-shipment-tracking' ); ?></th><td>
						<?php echo esc_html( $shipment['destination'] ); ?>
						<?php if ( ! empty( $shipment['destination_lat'] ) && ! empty( $shipment['destination_lng'] ) ) : ?>
							— <a href="<?php echo esc_url( PKST_Geolocation::google_maps_url( $shipment['destination_lat'], $shipment['destination_lng'] ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'مشاهده روی نقشه', 'peykherfei-shipment-tracking' ); ?></a>
						<?php endif; ?>
					</td></tr>
					<?php if ( PKST_Shipment::format_price( $shipment['price'] ?? null ) ) : ?>
					<tr><th><?php esc_html_e( 'قیمت', 'peykherfei-shipment-tracking' ); ?></th><td><span class="pkst-price"><?php echo esc_html( PKST_Shipment::format_price( $shipment['price'] ?? null ) ); ?></span></td></tr>
					<?php endif; ?>
					<?php if ( $shipment['origin'] ) : ?>
					<tr><th><?php esc_html_e( 'مبدأ', 'peykherfei-shipment-tracking' ); ?></th><td><?php echo esc_html( $shipment['origin'] ); ?></td></tr>
					<?php endif; ?>
					<?php if ( $shipment['sender_name'] ) : ?>
					<tr><th><?php esc_html_e( 'فرستنده', 'peykherfei-shipment-tracking' ); ?></th><td><?php echo esc_html( $shipment['sender_name'] ); ?> <span dir="ltr"><?php echo esc_html( $shipment['sender_phone'] ); ?></span></td></tr>
					<?php endif; ?>
					<tr><th><?php esc_html_e( 'پیک', 'peykherfei-shipment-tracking' ); ?></th><td><?php echo $courier ? esc_html( $courier->display_name ) : esc_html__( 'تخصیص‌نیافته', 'peykherfei-shipment-tracking' ); ?></td></tr>
					<?php if ( $customer ) : ?>
					<tr><th><?php esc_html_e( 'حساب مشتری', 'peykherfei-shipment-tracking' ); ?></th><td><?php echo esc_html( $customer->display_name ); ?></td></tr>
					<?php endif; ?>
					<tr><th><?php esc_html_e( 'تحویل به پیک', 'peykherfei-shipment-tracking' ); ?></th><td><?php echo $shipment['handed_to_courier_at'] ? esc_html( PKST_Jalali::format( $shipment['handed_to_courier_at'] ) ) : '—'; ?></td></tr>
					<?php if ( $shipment['delivered_at'] ) : ?>
					<tr><th><?php esc_html_e( 'تاریخ تحویل نهایی', 'peykherfei-shipment-tracking' ); ?></th><td><?php echo esc_html( PKST_Jalali::format( $shipment['delivered_at'] ) ); ?></td></tr>
					<?php endif; ?>
					<?php if ( $shipment['pod_receiver_name'] ) : ?>
					<tr><th><?php esc_html_e( 'تحویل‌گیرنده', 'peykherfei-shipment-tracking' ); ?></th><td><?php echo esc_html( $shipment['pod_receiver_name'] ); ?></td></tr>
					<?php endif; ?>
					<?php if ( $shipment['pod_failure_reason'] ) : ?>
					<tr><th><?php esc_html_e( 'علت عدم تحویل', 'peykherfei-shipment-tracking' ); ?></th><td><?php echo esc_html( $shipment['pod_failure_reason'] ); ?></td></tr>
					<?php endif; ?>
					<?php if ( $shipment['pod_signature_path'] ) : ?>
					<tr><th><?php esc_html_e( 'امضای تحویل', 'peykherfei-shipment-tracking' ); ?></th><td><img class="pkst-pod-thumb" src="<?php echo esc_url( PKST_POD::url_for_path( $shipment['pod_signature_path'] ) ); ?>" alt="" /></td></tr>
					<?php endif; ?>
					<?php if ( $shipment['pod_photo_path'] ) : ?>
					<tr><th><?php esc_html_e( 'تصویر تحویل', 'peykherfei-shipment-tracking' ); ?></th><td><a href="<?php echo esc_url( PKST_POD::url_for_path( $shipment['pod_photo_path'] ) ); ?>" target="_blank" rel="noopener noreferrer"><img class="pkst-pod-thumb" src="<?php echo esc_url( PKST_POD::url_for_path( $shipment['pod_photo_path'] ) ); ?>" alt="" /></a></td></tr>
					<?php endif; ?>
					<?php if ( $courier_location ) : ?>
					<tr>
						<th><?php esc_html_e( 'آخرین موقعیت پیک', 'peykherfei-shipment-tracking' ); ?></th>
						<td>
							<?php
							/* translators: %s: relative time, e.g. "5 minutes ago" */
							echo esc_html( sprintf( __( 'به‌روزرسانی %s پیش', 'peykherfei-shipment-tracking' ), human_time_diff( strtotime( $courier_location['at'] ), strtotime( current_time( 'mysql' ) ) ) ) );
							?>
							—
							<a href="<?php echo esc_url( PKST_Geolocation::google_maps_url( $courier_location['lat'], $courier_location['lng'] ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'نقشه گوگل', 'peykherfei-shipment-tracking' ); ?></a>
							·
							<a href="<?php echo esc_url( PKST_Geolocation::neshan_url( $courier_location['lat'], $courier_location['lng'] ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'نشان', 'peykherfei-shipment-tracking' ); ?></a>
						</td>
					</tr>
					<?php endif; ?>
				</table>
			</div>

			<div class="pkst-panel">
				<h2><?php esc_html_e( 'مسیر رهگیری', 'peykherfei-shipment-tracking' ); ?></h2>
				<ul class="pkst-timeline">
					<?php foreach ( $status_log as $entry ) : ?>
						<li class="pkst-timeline-item <?php echo esc_attr( PKST_Status::badge_class( $entry['status'] ) ); ?>">
							<div class="pkst-timeline-dot"></div>
							<div class="pkst-timeline-body">
								<strong><?php echo esc_html( PKST_Status::label( $entry['status'] ) ); ?></strong>
								<span class="pkst-timeline-date"><?php echo esc_html( PKST_Jalali::format( $entry['created_at'] ) ); ?></span>
								<?php if ( $entry['note'] ) : ?><p><?php echo esc_html( $entry['note'] ); ?></p><?php endif; ?>
								<?php if ( $entry['lat'] && $entry['lng'] ) : ?>
									<p><a href="<?php echo esc_url( PKST_Geolocation::google_maps_url( $entry['lat'], $entry['lng'] ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'مشاهده موقعیت ثبت‌شده', 'peykherfei-shipment-tracking' ); ?></a></p>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

		</div>

		<div class="pkst-col-side">
			<div class="pkst-panel">
				<h2><?php esc_html_e( 'به‌روزرسانی وضعیت', 'peykherfei-shipment-tracking' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" id="pkst-status-form">
					<input type="hidden" name="action" value="pkst_update_status" />
					<input type="hidden" name="id" value="<?php echo esc_attr( $shipment['id'] ); ?>" />
					<?php wp_nonce_field( 'pkst_update_status_' . $shipment['id'] ); ?>

					<p>
						<label for="status"><?php esc_html_e( 'وضعیت جدید', 'peykherfei-shipment-tracking' ); ?></label><br>
						<select name="status" id="pkst-status-select" class="widefat">
							<?php foreach ( $labels as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $shipment['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<p>
						<label for="note"><?php esc_html_e( 'یادداشت (اختیاری)', 'peykherfei-shipment-tracking' ); ?></label>
						<textarea name="note" id="note" class="widefat" rows="2"></textarea>
					</p>

					<div id="pkst-pod-fields" class="pkst-pod-fields">
						<p>
							<label for="pod_receiver_name"><?php esc_html_e( 'نام تحویل‌گیرنده', 'peykherfei-shipment-tracking' ); ?></label>
							<input type="text" class="widefat" name="pod_receiver_name" id="pod_receiver_name" value="<?php echo esc_attr( $shipment['pod_receiver_name'] ); ?>" />
						</p>
						<p>
							<label for="pod_failure_reason"><?php esc_html_e( 'علت عدم تحویل (در صورت برگشتی)', 'peykherfei-shipment-tracking' ); ?></label>
							<textarea class="widefat" name="pod_failure_reason" id="pod_failure_reason" rows="2"><?php echo esc_textarea( $shipment['pod_failure_reason'] ); ?></textarea>
						</p>
						<p>
							<label><?php esc_html_e( 'امضای گیرنده', 'peykherfei-shipment-tracking' ); ?></label><br>
							<canvas id="pkst-signature-pad" width="300" height="140" class="pkst-signature-pad"></canvas><br>
							<button type="button" class="button" id="pkst-clear-signature"><?php esc_html_e( 'پاک کردن امضا', 'peykherfei-shipment-tracking' ); ?></button>
							<input type="hidden" name="pod_signature_data" id="pod_signature_data" />
						</p>
						<p>
							<label for="pod_photo"><?php esc_html_e( 'تصویر تحویل (اختیاری)', 'peykherfei-shipment-tracking' ); ?></label>
							<input type="file" name="pod_photo" id="pod_photo" accept="image/*" />
						</p>
					</div>

					<?php submit_button( __( 'ثبت وضعیت', 'peykherfei-shipment-tracking' ), 'primary', 'submit', false ); ?>
				</form>
			</div>
		</div>
	</div>
</div>
