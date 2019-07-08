<?php
/**
 * Student new account email
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/plain/student-new-account.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '= ' . esc_html( $email_heading ) . " =\n\n";

/* translators: %s Student first name */
echo sprintf( esc_html__( 'Hi %s,', 'communityservice' ), esc_html( $user_login ) ) . "\n\n";
/* translators: %1$s: Site title, %2$s: Username, %3$s: My account link */
echo sprintf( esc_html__( 'Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view activities, change your password, and more at: %3$s', 'communityservice' ), esc_html( $blogname ), '<strong>' . esc_html( $user_login ) . '</strong>', esc_html( cs_get_page_permalink( 'myaccount' ) ) ) . "\n\n";

if ( 'yes' === get_option( 'communityservice_registration_generate_password' ) && $password_generated ) {
	/* translators: %s Auto generated password */
	echo sprintf( esc_html__( 'Your password has been automatically generated: %s.', 'communityservice' ), esc_html( $user_pass ) ) . "\n\n";
}

echo esc_html__( 'We look forward to seeing you soon.', 'communityservice' ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'communityservice_email_footer_text', get_option( 'communityservice_email_footer_text' ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
