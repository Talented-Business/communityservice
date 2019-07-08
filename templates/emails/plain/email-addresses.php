<?php
/**
 * Email Addresses (plain)
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/plain/email-addresses.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "\n" . esc_html( cs_strtoupper( __( 'Billing address', 'communityservice' ) ) ) . "\n\n";
echo preg_replace( '#<br\s*/?>#i', "\n", $activity->get_formatted_billing_address() ) . "\n"; // WPCS: XSS ok.

if ( $activity->get_billing_phone() ) {
	echo $activity->get_billing_phone() . "\n"; // WPCS: XSS ok.
}

if ( $activity->get_billing_email() ) {
	echo $activity->get_billing_email() . "\n"; // WPCS: XSS ok.
}

if ( ! cs_ship_to_billing_address_only() && $activity->needs_shipping_address() ) {
	$shipping = $activity->get_formatted_shipping_address();

	if ( $shipping ) {
		echo "\n" . esc_html( cs_strtoupper( __( 'Shipping address', 'communityservice' ) ) ) . "\n\n";
		echo preg_replace( '#<br\s*/?>#i', "\n", $shipping ) . "\n"; // WPCS: XSS ok.
	}
}
