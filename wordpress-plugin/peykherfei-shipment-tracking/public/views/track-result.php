<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** Expects $shipment (array) and $status_log (array) in scope. */
?>
<div class="pkst-shipment-card pkst-track-result">
	<div class="pkst-track-result-head">
		<div>
			<strong dir="ltr"><?php echo esc_html( $shipment['tracking_code'] ); ?></strong>
			<div class="pkst-muted"><?php echo esc_html( $shipment['destination'] ); ?></div>
		</div>
		<span class="pkst-badge <?php echo esc_attr( PKST_Status::badge_class( $shipment['status'] ) ); ?>"><?php echo esc_html( PKST_Status::label( $shipment['status'] ) ); ?></span>
	</div>

	<ul class="pkst-timeline">
		<?php foreach ( $status_log as $entry ) : ?>
			<li class="pkst-timeline-item <?php echo esc_attr( PKST_Status::badge_class( $entry['status'] ) ); ?>">
				<div class="pkst-timeline-dot"></div>
				<div class="pkst-timeline-body">
					<strong><?php echo esc_html( PKST_Status::label( $entry['status'] ) ); ?></strong>
					<span class="pkst-timeline-date"><?php echo esc_html( mysql2date( 'Y/m/d H:i', $entry['created_at'] ) ); ?></span>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( PKST_Status::FAILED === $shipment['status'] && $shipment['pod_failure_reason'] ) : ?>
		<p class="pkst-notice pkst-notice-danger"><?php echo esc_html( $shipment['pod_failure_reason'] ); ?></p>
	<?php endif; ?>
</div>
