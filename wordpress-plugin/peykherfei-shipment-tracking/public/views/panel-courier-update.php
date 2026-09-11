<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** Expects $shipment in scope. */
?>
<div class="pkst-front pkst-panel-courier-update">
	<p><a href="<?php echo esc_url( remove_query_arg( array( 'pkst_action', 'shipment_id' ) ) ); ?>">&rarr; <?php esc_html_e( 'بازگشت به لیست مرسولات', 'peykherfei-shipment-tracking' ); ?></a></p>

	<h3 dir="ltr"><?php echo esc_html( $shipment['tracking_code'] ); ?></h3>
	<p><?php echo esc_html( $shipment['recipient_name'] ); ?> — <span dir="ltr"><?php echo esc_html( $shipment['recipient_phone'] ); ?></span></p>
	<p class="pkst-muted"><?php echo esc_html( $shipment['destination'] ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" id="pkst-courier-status-form" class="pkst-form">
		<input type="hidden" name="action" value="pkst_courier_update_status" />
		<input type="hidden" name="shipment_id" value="<?php echo esc_attr( $shipment['id'] ); ?>" />
		<input type="hidden" name="lat" id="pkst-geo-lat" value="" />
		<input type="hidden" name="lng" id="pkst-geo-lng" value="" />
		<?php wp_nonce_field( 'pkst_courier_update_' . $shipment['id'] ); ?>

		<p>
			<label for="pkst-status-select"><?php esc_html_e( 'وضعیت جدید', 'peykherfei-shipment-tracking' ); ?></label><br>
			<select name="status" id="pkst-status-select">
				<?php foreach ( PKST_Status::labels() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $shipment['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="pkst-note"><?php esc_html_e( 'یادداشت (اختیاری)', 'peykherfei-shipment-tracking' ); ?></label><br>
			<textarea name="note" id="pkst-note" rows="2"></textarea>
		</p>

		<div id="pkst-pod-fields" class="pkst-pod-fields">
			<p>
				<label for="pod_receiver_name"><?php esc_html_e( 'نام تحویل‌گیرنده', 'peykherfei-shipment-tracking' ); ?></label><br>
				<input type="text" name="pod_receiver_name" id="pod_receiver_name" />
			</p>
			<p>
				<label for="pod_failure_reason"><?php esc_html_e( 'علت عدم تحویل (در صورت برگشتی)', 'peykherfei-shipment-tracking' ); ?></label><br>
				<textarea name="pod_failure_reason" id="pod_failure_reason" rows="2"></textarea>
			</p>
			<p>
				<label><?php esc_html_e( 'امضای گیرنده', 'peykherfei-shipment-tracking' ); ?></label><br>
				<canvas id="pkst-signature-pad" width="300" height="140" class="pkst-signature-pad"></canvas><br>
				<button type="button" class="pkst-btn" id="pkst-clear-signature"><?php esc_html_e( 'پاک کردن امضا', 'peykherfei-shipment-tracking' ); ?></button>
				<input type="hidden" name="pod_signature_data" id="pod_signature_data" />
			</p>
			<p>
				<label for="pod_photo"><?php esc_html_e( 'تصویر تحویل (اختیاری)', 'peykherfei-shipment-tracking' ); ?></label><br>
				<input type="file" name="pod_photo" id="pod_photo" accept="image/*" capture="environment" />
			</p>
		</div>

		<button type="submit" class="pkst-btn pkst-btn-primary"><?php esc_html_e( 'ثبت وضعیت', 'peykherfei-shipment-tracking' ); ?></button>
	</form>
</div>
