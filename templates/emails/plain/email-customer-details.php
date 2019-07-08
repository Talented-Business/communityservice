<?php
/**
 * Additional Student Details (plain)
 *
 * This is extra student data which can be filtered by plugins. It outputs below the activity item table.
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/plain/email-addresses.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo esc_html( cs_strtoupper( __( 'Student details', 'communityservice' ) ) ) . "\n\n";

foreach ( $fields as $field ) {
	echo wp_kses_post( $field['label'] ) . ': ' . wp_kses_post( $field['value'] ) . "\n";
}
