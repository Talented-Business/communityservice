<?php
/**
 * Student Reset Password email
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/plain/student-reset-password.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '= ' . esc_html( $email_heading ) . " =\n\n";

/* translators: %s: Student first name */
echo sprintf( esc_html__( 'Hi %s,', 'communityservice' ), esc_html( $user_login ) ) . "\n\n";
/* translators: %s: Store name */
echo sprintf( esc_html__( 'Someone has requested a new password for the following account on %s:', 'communityservice' ), esc_html( wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ) ) . "\n\n";
/* translators: %s: Student username */
echo sprintf( esc_html__( 'Username: %s', 'communityservice' ), esc_html( $user_login ) ) . "\n\n";
echo esc_html__( 'If you didn\'t make this request, just ignore this email. If you\'d like to proceed:', 'communityservice' ) . "\n\n";
echo esc_url( add_query_arg( array( 'key' => $reset_key, 'id' => $user_id ), cs_get_endpoint_url( 'lost-password', '', cs_get_page_permalink( 'myaccount' ) ) ) ) . "\n\n"; // phpcs:ignore
echo esc_html__( 'Thanks for reading.', 'communityservice' ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( apply_filters( 'communityservice_email_footer_text', get_option( 'communityservice_email_footer_text' ) ) );
