<?php
/**
 * Student new account email
 *
 * This template can be overridden by copying it to yourtheme/communityservice/emails/student-new-account.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<?php do_action( 'communityservice_email_header', $email_heading, $email ); ?>

<?php /* translators: %s Student username */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'communityservice' ), esc_html( $user_login ) ); ?></p>
<?php /* translators: %1$s: Site title, %2$s: Username, %3$s: My account link */ ?>
<p><?php printf( __( 'Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view activities, change your password, and more at: %3$s', 'communityservice' ), esc_html( $blogname ), '<strong>' . esc_html( $user_login ) . '</strong>', make_clickable( esc_url( cs_get_page_permalink( 'myaccount' ) ) ) ); ?></p><?php // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped ?>

<?php if ( 'yes' === get_option( 'communityservice_registration_generate_password' ) && $password_generated ) : ?>
	<?php /* translators: %s Auto generated password */ ?>
	<p><?php printf( esc_html__( 'Your password has been automatically generated: %s', 'communityservice' ), '<strong>' . esc_html( $user_pass ) . '</strong>' ); ?></p>
<?php endif; ?>

<p><?php esc_html_e( 'We look forward to seeing you soon.', 'communityservice' ); ?></p>

<?php
do_action( 'communityservice_email_footer', $email );
