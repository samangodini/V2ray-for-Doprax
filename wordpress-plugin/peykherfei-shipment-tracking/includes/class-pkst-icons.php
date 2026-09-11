<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Small inline-SVG icon set shared by the admin and public UI. Inline SVG
 * (not an icon font or external sprite) so there is no extra HTTP request
 * and the icons pick up `currentColor`, which is what lets them follow the
 * light/dark theme tokens automatically. Markup is static and hardcoded
 * here (never built from user input), so callers can echo it unescaped.
 */
class PKST_Icons {

	private static $paths = array(
		'box'        => '<path d="M12 2.5 3.5 7v10L12 21.5 20.5 17V7Z"/><path d="M3.5 7 12 11.5 20.5 7"/><path d="M12 11.5V21.5"/>',
		'users'      => '<circle cx="9" cy="8" r="3.3"/><path d="M2.7 19c.7-3.2 3.2-5 6.3-5s5.6 1.8 6.3 5"/><circle cx="17.5" cy="8.5" r="2.6"/><path d="M15.7 14.2c2.4.3 4.2 1.9 4.8 4.8"/>',
		'settings'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 13.5a7.6 7.6 0 0 0 0-3l2-1.5-2-3.4-2.3.9a7.6 7.6 0 0 0-2.6-1.5L14 2h-4l-.5 2.4a7.6 7.6 0 0 0-2.6 1.5l-2.3-.9-2 3.4 2 1.5a7.6 7.6 0 0 0 0 3l-2 1.6 2 3.4 2.3-.9c.8.7 1.7 1.2 2.6 1.5L10 22h4l.5-2.4a7.6 7.6 0 0 0 2.6-1.5l2.3.9 2-3.4Z"/>',
		'backup'     => '<path d="M4 7a8 5 0 0 0 16 0 8 5 0 0 0-16 0Z"/><path d="M4 7v5a8 5 0 0 0 16 0V7"/><path d="M4 12v5a8 5 0 0 0 16 0v-5"/>',
		'download'   => '<path d="M12 3v13"/><path d="m6.5 11 5.5 5.5L17.5 11"/><path d="M4 20.5h16"/>',
		'upload'     => '<path d="M12 20.5v-13"/><path d="m6.5 12 5.5-5.5L17.5 12"/><path d="M4 20.5h16"/>',
		'edit'       => '<path d="M4 20.5h4L19.5 9a2.5 2.5 0 0 0-4-4L4 16.5Z"/><path d="m14 6 4 4"/>',
		'trash'      => '<path d="M5 7h14"/><path d="M9.5 7V4.8c0-.4.4-.8.8-.8h3.4c.4 0 .8.4.8.8V7"/><path d="M6.5 7 7.3 19a1.6 1.6 0 0 0 1.6 1.5h6.2a1.6 1.6 0 0 0 1.6-1.5L17.5 7"/><path d="M10.2 11v6M13.8 11v6"/>',
		'plus'       => '<path d="M12 4.5v15"/><path d="M4.5 12h15"/>',
		'phone'      => '<path d="M5 4h3.2l1.3 4.2-2 1.7a13 13 0 0 0 6.6 6.6l1.7-2 4.2 1.3V19a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>',
		'tag'        => '<path d="M11.7 3.3 20 11.6a1.8 1.8 0 0 1 0 2.6l-5.8 5.8a1.8 1.8 0 0 1-2.6 0L3.3 12V4a.7.7 0 0 1 .7-.7Z"/><circle cx="8" cy="8" r="1.4"/>',
		'pin'        => '<path d="M12 21.5s7-6.3 7-11.8a7 7 0 1 0-14 0c0 5.5 7 11.8 7 11.8Z"/><circle cx="12" cy="9.5" r="2.4"/>',
		'clock'      => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3.2 2"/>',
		'chart'      => '<path d="M4 20.5h16"/><path d="M7 20.5v-6.5"/><path d="M12 20.5V7"/><path d="M17 20.5v-9.5"/>',
		'check'      => '<path d="m4.5 12.5 5 5L19.5 7"/>',
		'x'          => '<path d="m5.5 5.5 13 13"/><path d="m18.5 5.5-13 13"/>',
		'toggle-on'  => '<rect x="2.5" y="7" width="19" height="10" rx="5"/><circle cx="16" cy="12" r="3.4" fill="currentColor" stroke="none"/>',
		'toggle-off' => '<rect x="2.5" y="7" width="19" height="10" rx="5"/><circle cx="8" cy="12" r="3.4" fill="currentColor" stroke="none"/>',
		'search'     => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m20 20-4.3-4.3"/>',
		'truck'      => '<path d="M2.5 6.5h10v9h-10Z"/><path d="M12.5 10h4l3 3v2.5h-7Z"/><circle cx="6.5" cy="17.5" r="1.7"/><circle cx="16" cy="17.5" r="1.7"/>',
		'file'       => '<path d="M7 3.5h7l4 4v13H7Z"/><path d="M14 3.5v4h4"/>',
		'arrow-left' => '<path d="M19 12H5"/><path d="m11 6-6 6 6 6"/>',
	);

	/**
	 * @param string $name One of the keys above.
	 * @param int    $size Width/height in px.
	 */
	public static function svg( $name, $size = 18 ) {
		if ( ! isset( self::$paths[ $name ] ) ) {
			return '';
		}
		$size = absint( $size );
		return sprintf(
			'<svg class="pkst-icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
			$size,
			self::$paths[ $name ]
		);
	}
}
