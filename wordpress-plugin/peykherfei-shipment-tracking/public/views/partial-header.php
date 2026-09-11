<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Shared brand bar for every [pkst_panel]/[pkst_track] view: brand name +
 * the light/dark toggle. Included (not templated through PKST_Public::render())
 * so it always shares the including file's scope without extra plumbing.
 */
?>
<div class="pkst-brand-bar">
	<span class="pkst-brand"><?php echo esc_html( PKST_Settings::get( 'company_name', 'پیک خرفه' ) ); ?></span>
	<button type="button" id="pkst-theme-toggle" class="pkst-theme-toggle" aria-label="<?php esc_attr_e( 'تغییر حالت روشن/تاریک', 'peykherfei-shipment-tracking' ); ?>" title="<?php esc_attr_e( 'حالت روشن/تاریک', 'peykherfei-shipment-tracking' ); ?>">
		<svg class="pkst-icon pkst-icon-sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
			<circle cx="12" cy="12" r="4.2" fill="currentColor" />
			<g stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
				<line x1="12" y1="2.5" x2="12" y2="5" />
				<line x1="12" y1="19" x2="12" y2="21.5" />
				<line x1="2.5" y1="12" x2="5" y2="12" />
				<line x1="19" y1="12" x2="21.5" y2="12" />
				<line x1="4.9" y1="4.9" x2="6.6" y2="6.6" />
				<line x1="17.4" y1="17.4" x2="19.1" y2="19.1" />
				<line x1="4.9" y1="19.1" x2="6.6" y2="17.4" />
				<line x1="17.4" y1="6.6" x2="19.1" y2="4.9" />
			</g>
		</svg>
		<svg class="pkst-icon pkst-icon-moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
			<path fill="currentColor" d="M20.6 15.3A8.6 8.6 0 1 1 10.2 3.4a.8.8 0 0 1 1 1.1A7 7 0 0 0 19.5 14.3a.8.8 0 0 1 1.1 1Z" />
		</svg>
	</button>
</div>
