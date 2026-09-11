<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$max_daily = $daily ? max( $daily ) : 0;
?>
<div class="wrap pkst-wrap" dir="rtl">
	<h1><?php esc_html_e( 'داشبورد مرسولات', 'peykherfei-shipment-tracking' ); ?></h1>
	<?php PKST_Admin::notice_from_query(); ?>

	<div class="pkst-stat-grid">
		<div class="pkst-stat-card">
			<span class="pkst-stat-number"><?php echo esc_html( number_format_i18n( $counts['total'] ) ); ?></span>
			<span class="pkst-stat-label"><?php esc_html_e( 'کل مرسولات', 'peykherfei-shipment-tracking' ); ?></span>
		</div>
		<div class="pkst-stat-card pkst-stat-success">
			<span class="pkst-stat-number"><?php echo esc_html( number_format_i18n( $counts[ PKST_Status::DELIVERED ] ) ); ?></span>
			<span class="pkst-stat-label"><?php esc_html_e( 'تحویل‌شده', 'peykherfei-shipment-tracking' ); ?></span>
		</div>
		<div class="pkst-stat-card pkst-stat-info">
			<span class="pkst-stat-number"><?php echo esc_html( number_format_i18n( $counts[ PKST_Status::IN_TRANSIT ] + $counts[ PKST_Status::PICKED_UP ] + $counts[ PKST_Status::ARRIVED ] ) ); ?></span>
			<span class="pkst-stat-label"><?php esc_html_e( 'در حال ارسال', 'peykherfei-shipment-tracking' ); ?></span>
		</div>
		<div class="pkst-stat-card pkst-stat-warning">
			<span class="pkst-stat-number"><?php echo esc_html( number_format_i18n( $counts['overdue'] ) ); ?></span>
			<span class="pkst-stat-label"><?php esc_html_e( 'معوق', 'peykherfei-shipment-tracking' ); ?></span>
		</div>
		<div class="pkst-stat-card pkst-stat-danger">
			<span class="pkst-stat-number"><?php echo esc_html( number_format_i18n( $counts[ PKST_Status::FAILED ] ) ); ?></span>
			<span class="pkst-stat-label"><?php esc_html_e( 'برگشتی', 'peykherfei-shipment-tracking' ); ?></span>
		</div>
		<div class="pkst-stat-card">
			<span class="pkst-stat-number"><?php echo esc_html( PKST_Reports::format_minutes( $avg_minutes ) ); ?></span>
			<span class="pkst-stat-label"><?php esc_html_e( 'میانگین زمان تحویل', 'peykherfei-shipment-tracking' ); ?></span>
		</div>
	</div>

	<div class="pkst-panel">
		<h2><?php esc_html_e( 'روند ثبت مرسولات (۱۴ روز اخیر)', 'peykherfei-shipment-tracking' ); ?></h2>
		<div class="pkst-bar-chart">
			<?php foreach ( $daily as $date => $value ) : ?>
				<div class="pkst-bar-col">
					<div class="pkst-bar" style="height: <?php echo esc_attr( $max_daily ? max( 4, round( $value / $max_daily * 100 ) ) : 4 ); ?>%;" title="<?php echo esc_attr( $value ); ?>">
						<span><?php echo esc_html( $value ); ?></span>
					</div>
					<?php
					$d_bits = array_map( 'intval', explode( '-', $date ) );
					list( , $jm, $jd ) = PKST_Jalali::to_jalali( $d_bits[0], $d_bits[1], $d_bits[2] );
					?>
					<div class="pkst-bar-label"><?php echo esc_html( PKST_Jalali::to_persian_digits( sprintf( '%02d/%02d', $jm, $jd ) ) ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="pkst-panel">
		<h2><?php esc_html_e( 'عملکرد پیک‌ها', 'peykherfei-shipment-tracking' ); ?></h2>
		<?php if ( empty( $courier_perf ) ) : ?>
			<p class="description"><?php esc_html_e( 'هنوز مرسوله‌ای به پیکی تخصیص داده نشده است.', 'peykherfei-shipment-tracking' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'پیک', 'peykherfei-shipment-tracking' ); ?></th>
						<th><?php esc_html_e( 'کل مرسولات', 'peykherfei-shipment-tracking' ); ?></th>
						<th><?php esc_html_e( 'تحویل‌شده', 'peykherfei-shipment-tracking' ); ?></th>
						<th><?php esc_html_e( 'برگشتی', 'peykherfei-shipment-tracking' ); ?></th>
						<th><?php esc_html_e( 'میانگین زمان تحویل', 'peykherfei-shipment-tracking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $courier_perf as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['name'] ); ?></td>
							<td><?php echo esc_html( $row['total'] ); ?></td>
							<td><?php echo esc_html( $row['delivered'] ); ?></td>
							<td><?php echo esc_html( $row['failed'] ); ?></td>
							<td><?php echo esc_html( PKST_Reports::format_minutes( $row['avg_minutes'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pkst-shipment-add' ) ); ?>" class="button button-primary"><?php esc_html_e( 'افزودن مرسوله جدید', 'peykherfei-shipment-tracking' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pkst-shipments' ) ); ?>" class="button"><?php esc_html_e( 'مشاهده همه مرسولات', 'peykherfei-shipment-tracking' ); ?></a>
	</p>
</div>
