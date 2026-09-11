<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap pkst-wrap" dir="rtl">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'همه مرسولات', 'peykherfei-shipment-tracking' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=pkst-shipment-add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'افزودن مرسوله', 'peykherfei-shipment-tracking' ); ?></a>
	<hr class="wp-header-end">

	<?php PKST_Admin::notice_from_query(); ?>

	<form method="get">
		<input type="hidden" name="page" value="pkst-shipments" />
		<?php $list_table->search_box( __( 'جستجو (کد، نام، شماره)', 'peykherfei-shipment-tracking' ), 'pkst-shipment' ); ?>
		<?php $list_table->display(); ?>
	</form>
</div>
