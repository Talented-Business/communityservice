<?php
/**
 * Admin new blog email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/plain/admin-new-blog.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '= ' . esc_html( $email_heading ) . " =\n\n";

/* translators: %s: Student billing full name */
echo sprintf( esc_html__( 'You’ve received the following blog from administrator:', 'communityservice' ), "" ) . "\n\n";
echo "You can read new blog here ".get_permalink( $blog->ID );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";


echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";


echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( apply_filters( 'communityservice_email_footer_text', get_option( 'communityservice_email_footer_text' ) ) );
