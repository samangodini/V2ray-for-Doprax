<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface PKST_SMS_Gateway_Interface {

	/**
	 * @param string $to      Recipient phone number (already normalized).
	 * @param string $message Final message text (placeholders already replaced).
	 * @return array{success: bool, response: string}
	 */
	public function send( $to, $message );
}
