<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gregorian <-> Jalali (Shamsi) conversion, plus the formatting/rendering
 * helpers built on it. Uses the standard 33-year-cycle algorithm (the one
 * behind the widely-used jdf.php), which is simpler and easier to verify
 * by round-trip than the full astronomical version, and is accurate for
 * the entire modern range this plugin will ever need.
 */
class PKST_Jalali {

	public static function to_jalali( $gy, $gm, $gd ) {
		$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days  = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 ) + intdiv( $gy2 + 399, 400 ) + $gd + $g_d_m[ $gm - 1 ];

		$jy = -1595 + ( 33 * intdiv( $days, 12053 ) );
		$days %= 12053;
		$jy += 4 * intdiv( $days, 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$jy  += intdiv( $days - 1, 365 );
			$days = ( $days - 1 ) % 365;
		}

		if ( $days < 186 ) {
			$jm = 1 + intdiv( $days, 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + intdiv( $days - 186, 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}

		return array( $jy, $jm, $jd );
	}

	public static function to_gregorian( $jy, $jm, $jd ) {
		$jy   += 1595;
		$days  = -355668 + ( 365 * $jy ) + ( intdiv( $jy, 33 ) * 8 ) + intdiv( ( $jy % 33 ) + 3, 4 ) + $jd
			+ ( ( $jm < 7 ) ? ( $jm - 1 ) * 31 : ( ( $jm - 7 ) * 30 ) + 186 );

		$gy = 400 * intdiv( $days, 146097 );
		$days %= 146097;

		if ( $days > 36524 ) {
			$days--;
			$gy  += 100 * intdiv( $days, 36524 );
			$days = $days % 36524;
			if ( $days >= 365 ) {
				$days++;
			}
		}

		$gy += 4 * intdiv( $days, 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$gy  += intdiv( $days - 1, 365 );
			$days = ( $days - 1 ) % 365;
		}

		$gd = $days + 1;

		$is_leap = ( 0 === $gy % 4 && 0 !== $gy % 100 ) || 0 === $gy % 400;
		$sal_a   = array( 0, 31, $is_leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

		$gm = 12;
		for ( $i = 1; $i <= 12; $i++ ) {
			if ( $gd <= $sal_a[ $i ] ) {
				$gm = $i;
				break;
			}
			$gd -= $sal_a[ $i ];
		}

		return array( $gy, $gm, $gd );
	}

	/**
	 * Whether day 30 of month 12 actually exists in this Jalali year.
	 * Derived by round-tripping through to_gregorian()/to_jalali() rather
	 * than a separately-memorized leap-year formula, so it can't drift out
	 * of sync with the two conversion functions above.
	 */
	public static function is_leap_year( $jy ) {
		list( $gy, $gm, $gd ) = self::to_gregorian( $jy, 12, 30 );
		list( $jy2, $jm2, $jd2 ) = self::to_jalali( $gy, $gm, $gd );
		return 12 === $jm2 && 30 === $jd2;
	}

	public static function days_in_month( $jy, $jm ) {
		if ( $jm <= 6 ) {
			return 31;
		}
		if ( $jm <= 11 ) {
			return 30;
		}
		return self::is_leap_year( $jy ) ? 30 : 29;
	}

	public static function month_names() {
		return array(
			1  => 'فروردین',
			2  => 'اردیبهشت',
			3  => 'خرداد',
			4  => 'تیر',
			5  => 'مرداد',
			6  => 'شهریور',
			7  => 'مهر',
			8  => 'آبان',
			9  => 'آذر',
			10 => 'دی',
			11 => 'بهمن',
			12 => 'اسفند',
		);
	}

	public static function month_name( $jm ) {
		$names = self::month_names();
		return isset( $names[ $jm ] ) ? $names[ $jm ] : '';
	}

	public static function to_persian_digits( $string ) {
		static $en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		static $fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		return str_replace( $en, $fa, (string) $string );
	}

	public static function from_persian_digits( $string ) {
		static $fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		static $en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		return str_replace( $fa, $en, (string) $string );
	}

	/**
	 * @param string $mysql_datetime 'Y-m-d' or 'Y-m-d H:i:s'.
	 * @param bool   $with_time      Append the time-of-day if present.
	 */
	public static function format( $mysql_datetime, $with_time = true ) {
		if ( empty( $mysql_datetime ) || '0000-00-00 00:00:00' === $mysql_datetime || '0000-00-00' === $mysql_datetime ) {
			return '—';
		}

		$parts     = explode( ' ', trim( $mysql_datetime ) );
		$date_bits = array_map( 'intval', explode( '-', $parts[0] ) );
		if ( 3 !== count( $date_bits ) ) {
			return '—';
		}

		list( $jy, $jm, $jd ) = self::to_jalali( $date_bits[0], $date_bits[1], $date_bits[2] );
		$out = sprintf( '%04d/%02d/%02d', $jy, $jm, $jd );

		if ( $with_time && ! empty( $parts[1] ) ) {
			$out .= ' ' . substr( $parts[1], 0, 5 );
		}

		return self::to_persian_digits( $out );
	}

	public static function current_parts() {
		$now = explode( '-', current_time( 'Y-m-d' ) );
		return self::to_jalali( (int) $now[0], (int) $now[1], (int) $now[2] );
	}

	/**
	 * Renders سال/ماه/روز (+ optional ساعت/دقیقه) <select> dropdowns for
	 * $field_name, defaulting to $gregorian_datetime (or "now"). No JS
	 * calendar widget needed: three/five plain selects, values submitted
	 * as $field_name[y|m|d|h|i], read back with parse_select_input().
	 */
	public static function render_select_fields( $field_name, $gregorian_datetime = '', $with_time = false ) {
		if ( $gregorian_datetime ) {
			$bits = explode( ' ', trim( $gregorian_datetime ) );
			$d    = array_map( 'intval', explode( '-', $bits[0] ) );
			list( $jy, $jm, $jd ) = self::to_jalali( $d[0], $d[1], $d[2] );
			$hh = isset( $bits[1] ) ? (int) substr( $bits[1], 0, 2 ) : 0;
			$mi = isset( $bits[1] ) ? (int) substr( $bits[1], 3, 2 ) : 0;
		} else {
			list( $jy, $jm, $jd ) = self::current_parts();
			$hh = (int) current_time( 'H' );
			$mi = (int) current_time( 'i' );
		}

		ob_start();
		?>
		<span class="pkst-jalali-fields">
			<select name="<?php echo esc_attr( $field_name ); ?>[d]" class="pkst-jalali-day" aria-label="روز">
				<?php for ( $d = 1; $d <= 31; $d++ ) : ?>
					<option value="<?php echo esc_attr( $d ); ?>" <?php selected( $jd, $d ); ?>><?php echo esc_html( self::to_persian_digits( $d ) ); ?></option>
				<?php endfor; ?>
			</select>
			<select name="<?php echo esc_attr( $field_name ); ?>[m]" class="pkst-jalali-month" aria-label="ماه">
				<?php foreach ( self::month_names() as $num => $label ) : ?>
					<option value="<?php echo esc_attr( $num ); ?>" <?php selected( $jm, $num ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="<?php echo esc_attr( $field_name ); ?>[y]" class="pkst-jalali-year" aria-label="سال">
				<?php for ( $y = $jy - 3; $y <= $jy + 3; $y++ ) : ?>
					<option value="<?php echo esc_attr( $y ); ?>" <?php selected( $jy, $y ); ?>><?php echo esc_html( self::to_persian_digits( $y ) ); ?></option>
				<?php endfor; ?>
			</select>
			<?php if ( $with_time ) : ?>
				<select name="<?php echo esc_attr( $field_name ); ?>[h]" class="pkst-jalali-hour" aria-label="ساعت">
					<?php for ( $h = 0; $h <= 23; $h++ ) : ?>
						<option value="<?php echo esc_attr( $h ); ?>" <?php selected( $hh, $h ); ?>><?php echo esc_html( self::to_persian_digits( sprintf( '%02d', $h ) ) ); ?></option>
					<?php endfor; ?>
				</select>
				:
				<select name="<?php echo esc_attr( $field_name ); ?>[i]" class="pkst-jalali-minute" aria-label="دقیقه">
					<?php for ( $m = 0; $m <= 55; $m += 5 ) : ?>
						<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $mi >= $m && $mi < $m + 5, true ); ?>><?php echo esc_html( self::to_persian_digits( sprintf( '%02d', $m ) ) ); ?></option>
					<?php endfor; ?>
				</select>
			<?php endif; ?>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Reads back a render_select_fields() submission from $_POST and
	 * returns a MySQL 'Y-m-d' or 'Y-m-d H:i:s' string, or '' if the field
	 * wasn't present/valid.
	 */
	public static function parse_select_input( $field_name, $with_time = false ) {
		if ( empty( $_POST[ $field_name ] ) || ! is_array( $_POST[ $field_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}

		$raw = wp_unslash( $_POST[ $field_name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$jy  = isset( $raw['y'] ) ? absint( $raw['y'] ) : 0;
		$jm  = isset( $raw['m'] ) ? absint( $raw['m'] ) : 0;
		$jd  = isset( $raw['d'] ) ? absint( $raw['d'] ) : 0;

		if ( ! $jy || ! $jm || ! $jd ) {
			return '';
		}

		list( $gy, $gm, $gd ) = self::to_gregorian( $jy, $jm, $jd );
		$date = sprintf( '%04d-%02d-%02d', $gy, $gm, $gd );

		if ( $with_time ) {
			$hh   = isset( $raw['h'] ) ? absint( $raw['h'] ) : 0;
			$mi   = isset( $raw['i'] ) ? absint( $raw['i'] ) : 0;
			$date .= sprintf( ' %02d:%02d:00', $hh, $mi );
		}

		return $date;
	}
}
