<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** Expects $counts (from PKST_Shipment::counts()) in scope. */
?>
<div class="wrap pkst-wrap" dir="rtl">
	<h1><?php echo PKST_Icons::svg( 'backup', 22 ); ?> <?php esc_html_e( 'پشتیبان‌گیری و خروجی اطلاعات', 'peykherfei-shipment-tracking' ); ?></h1>
	<?php PKST_Admin::notice_from_query(); ?>

	<div class="pkst-columns">
		<div class="pkst-col-main">

			<div class="pkst-panel pkst-backup-card">
				<h2><?php echo PKST_Icons::svg( 'download', 18 ); ?> <?php esc_html_e( 'دانلود بک‌آپ کامل', 'peykherfei-shipment-tracking' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'یک فایل کامل شامل همه مرسولات، تاریخچه وضعیت‌ها، آدرس‌های ذخیره‌شده مشتریان و تنظیمات سامانه دانلود می‌شود. این فایل را در جای امنی (مثل گوگل‌درایو یا سیستم خودتان) نگه دارید.', 'peykherfei-shipment-tracking' ); ?>
				</p>
				<p class="pkst-backup-stats">
					<span><strong><?php echo esc_html( number_format_i18n( $counts['total'] ) ); ?></strong> <?php esc_html_e( 'مرسوله', 'peykherfei-shipment-tracking' ); ?></span>
				</p>
				<a class="button button-primary button-hero" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pkst_backup_export' ), 'pkst_backup_export' ) ); ?>">
					<?php echo PKST_Icons::svg( 'download', 16 ); ?> <?php esc_html_e( 'دانلود بک‌آپ (JSON)', 'peykherfei-shipment-tracking' ); ?>
				</a>
			</div>

			<div class="pkst-panel pkst-backup-card">
				<h2><?php echo PKST_Icons::svg( 'upload', 18 ); ?> <?php esc_html_e( 'بازیابی از فایل بک‌آپ', 'peykherfei-shipment-tracking' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'بازیابی کاملاً افزایشی و امن است: مرسولاتی که کد رهگیری‌شان از قبل در سامانه موجود باشد، دوباره اضافه نمی‌شوند و رد می‌شوند. اطلاعات فعلی شما هرگز پاک یا جایگزین نخواهد شد.', 'peykherfei-shipment-tracking' ); ?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="pkst_backup_import" />
					<?php wp_nonce_field( 'pkst_backup_import' ); ?>
					<p>
						<input type="file" name="backup_file" accept="application/json,.json" required />
					</p>
					<p>
						<label><input type="checkbox" name="restore_settings" value="1" /> <?php esc_html_e( 'تنظیمات سامانه را هم از فایل بک‌آپ بازیابی کن (تنظیمات فعلی جایگزین می‌شود)', 'peykherfei-shipment-tracking' ); ?></label>
					</p>
					<?php submit_button( __( 'شروع بازیابی', 'peykherfei-shipment-tracking' ), 'secondary' ); ?>
				</form>
			</div>

		</div>

		<div class="pkst-col-side">
			<div class="pkst-panel">
				<h2><?php echo PKST_Icons::svg( 'file', 18 ); ?> <?php esc_html_e( 'خروجی‌های اکسل', 'peykherfei-shipment-tracking' ); ?></h2>
				<p class="description"><?php esc_html_e( 'برای خروجی اکسل مرسولات (با امکان فیلتر بر اساس وضعیت/تاریخ) به صفحه «همه مرسولات» مراجعه کنید.', 'peykherfei-shipment-tracking' ); ?></p>
				<p>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=pkst-shipments' ) ); ?>"><?php echo PKST_Icons::svg( 'box', 14 ); ?> <?php esc_html_e( 'رفتن به فهرست مرسولات', 'peykherfei-shipment-tracking' ); ?></a>
				</p>
				<hr />
				<p class="description"><?php esc_html_e( 'فهرست کامل کاربران (پیک‌ها و مشتریان) با شماره تماس و وضعیت.', 'peykherfei-shipment-tracking' ); ?></p>
				<p>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pkst_export_users_csv' ), 'pkst_export_users_csv' ) ); ?>"><?php echo PKST_Icons::svg( 'download', 14 ); ?> <?php esc_html_e( 'خروجی اکسل کاربران', 'peykherfei-shipment-tracking' ); ?></a>
				</p>
			</div>
		</div>
	</div>
</div>
